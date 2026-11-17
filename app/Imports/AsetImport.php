<?php

namespace App\Imports;

use App\Models\Cabang;
use App\Models\DetailAset;
use App\Models\Item;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsetImport
{
    private const HEADER = [
        'nama'     => '/^nama\s*aset/',
        'kategori' => '/^kategori/',
        'cabang'   => '/^cabang/',
        'merk'     => '/^merk/',
        'harga'    => '/^harga/',
        'unit'     => '/^unit/',
        'tgl'      => '/^(tgl|tanggal)\s*pengadaan/',
        'ruangan'  => '/^ruang/',
        'ba'       => '/berita\s*acara|\(b\.?a\.?\)/',
    ];

    private const HEADER_STATUS = [
        'baik'   => '/^baik$/',
        'ringan' => '/^ringan$/',
        'sedang' => '/^sedang$/',
        'berat'  => '/^berat$/',
    ];

    private const WAJIB = ['nama', 'harga', 'unit', 'tgl', 'ruangan', 'baik', 'ringan', 'sedang', 'berat'];

    private const BATAS_KOLOM = 30;
    private const KATEGORI_PER_HURUF = [
        'A' => 'Tanah dan Bangunan',
        'B' => 'Furniture',
        'C' => 'Elektronik',
        'D' => 'Kendaraan Operasional',
        'E' => 'Alat Rumah Tangga',
        'F' => 'Alat Logistik',
        'G' => 'Dekorasi',
        'H' => 'Alat Kantor',
    ];

    private const KOREKSI_CABANG = [
        'labohan bajo' => 'Labuan Bajo',
    ];

    private array $cabangCache = [];

    private bool $modelSudahDicek = false;

    public int $totalDibaca = 0;

    public int $totalMasuk = 0;

    public int $totalDuplikat = 0;

    /** @var array<int, string> */
    public array $contohDuplikat = [];

    public static function dataTemplateImport(): array
    {
        return [
            ['Kode Aset', 'Nama Aset', 'Kategori', 'Cabang', 'Merk / Tipe', 'Harga Beli (IDR)', 'Unit', 'Tgl Pengadaan', 'Status Aset', '', '', '', 'Ruangan', 'No. Berita Acara (B.A)'],
            ['', '', '', '', '', '', '', '', 'Baik', 'Rusak', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', 'Ringan', 'Sedang', 'Berat', '', ''],
            ['C.01', 'Laptop Operasional', 'Elektronik', 'Juanda', 'Lenovo ThinkPad', 8500000, 1, '2026-11-16', 'V', '', '', '', 'Ruang IT', 'BA.001'],
            ['B.01', 'Meja Kerja Staff', 'Furniture', 'Sidoarjo', 'Olympic', 1500000, 1, '2026-11-16', 'V', '', '', '', 'Ruang Admin', 'BA.002'],
        ];
    }

    public static function buatTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris DMK');

        $rows = self::dataTemplateImport();
        $sheet->fromArray($rows, null, 'A1');

        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:B3');
        $sheet->mergeCells('C1:C3');
        $sheet->mergeCells('D1:D3');
        $sheet->mergeCells('E1:E3');
        $sheet->mergeCells('F1:F3');
        $sheet->mergeCells('G1:G3');
        $sheet->mergeCells('H1:H3');
        $sheet->mergeCells('I1:L1');
        $sheet->mergeCells('I2:I3');
        $sheet->mergeCells('J2:L2');
        $sheet->mergeCells('M1:M3');
        $sheet->mergeCells('N1:N3');

        $sheet->getStyle('A1:N3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A4F91']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);

        $sheet->getStyle('A4:N5')->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet->getStyle('F4:F5')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G4:L5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $lebarKolom = [
            'A' => 18, 'B' => 24, 'C' => 18, 'D' => 16, 'E' => 18,
            'F' => 18, 'G' => 9, 'H' => 15, 'I' => 9, 'J' => 9,
            'K' => 9, 'L' => 9, 'M' => 16, 'N' => 22,
        ];
        foreach ($lebarKolom as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $panduan = $spreadsheet->createSheet();
        $panduan->setTitle('Panduan Pengisian');
        $panduan->fromArray([
            ['PANDUAN IMPORT DATA ASET (MULTI-CABANG / GLOBAL)'],
            ['1. Anda bisa mengimport seluruh cabang dalam 1 file sekaligus dengan mengisi kolom "Cabang" di setiap baris.'],
            ['2. Khusus Excel (.xlsx/.xls), jika ingin memisah per tab/sheet, beri nama sheet berawalan "Inventaris", misal: "Inventaris DMK (Juanda)".'],
            ['3. Kolom wajib: Kode Aset, Nama Aset, Cabang (jika nama sheet tidak memakai tanda kurung), Harga Beli, Unit, Tgl Pengadaan, Ruangan, serta kolom status (Baik / Ringan / Sedang / Berat).'],
            ['4. Format Kode Aset diawali huruf kategori + titik + nomor urut (contoh: C.01 atau C.01/20261116/1).'],
            ['5. Pemetaan huruf kategori otomatis: A=Tanah dan Bangunan, B=Furniture, C=Elektronik, D=Kendaraan Operasional, E=Alat Rumah Tangga, F=Alat Logistik, G=Dekorasi, H=Alat Kantor. Huruf lain mengambil dari kolom "Kategori".'],
            ['6. Isi huruf "V" atau angka "1" pada salah satu kolom status (Baik, Ringan, Sedang, atau Berat).'],
            ['7. Format Tgl Pengadaan: YYYY-MM-DD (contoh: 2026-11-16) atau tanggal Excel standar.'],
            ['8. Hapus atau timpa 2 baris contoh pada sheet "Inventaris DMK" sebelum mengunggah data asli (sheet panduan ini otomatis diabaikan sistem).'],
        ], null, 'A1');
        $panduan->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $panduan->getColumnDimension('A')->setWidth(115);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function import(string $path, string $ekstensi = 'xlsx', ?string $namaFileAsli = null): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $ext   = strtolower(trim($ekstensi));
        $isCsv = ($ext === 'csv');

        if ($isCsv) {
            $reader = IOFactory::createReader('Csv');
            if ($reader instanceof Csv) {
                $reader->setInputEncoding(Csv::GUESS_ENCODING);
            }
        } else {
            $reader = IOFactory::createReader($ext === 'xls' ? 'Xls' : 'Xlsx');
        }
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);

        $cabangDariNamaFile = $isCsv && $namaFileAsli !== null
            ? $this->namaCabangDariSheet(pathinfo($namaFileAsli, PATHINFO_FILENAME))
            : null;

        try {
            return $this->importSpreadsheet($spreadsheet, $isCsv, $cabangDariNamaFile);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * Memproses objek Spreadsheet yang sudah dimuat ke database dengan deteksi data duplikat.
     *
     * @return array<string, int>
     */
    public function importSpreadsheet(Spreadsheet $spreadsheet, bool $isCsv = false, ?string $cabangDariNamaFile = null): array
    {
        $ringkasan = [];
        $this->totalDibaca = 0;
        $this->totalMasuk = 0;
        $this->totalDuplikat = 0;
        $this->contohDuplikat = [];

        DB::transaction(function () use ($spreadsheet, $isCsv, $cabangDariNamaFile, &$ringkasan) {
            $semua = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                if (!$isCsv && !preg_match('/^\s*Inventaris\b/i', $sheet->getTitle())) {
                    continue;
                }
                foreach ($this->bacaSheet($sheet, $cabangDariNamaFile, $isCsv) as $baris) {
                    $semua[] = $baris;
                }
            }

            $this->totalDibaca = count($semua);
            if (empty($semua)) {
                return;
            }

            $baru = $this->saringBarisBaruDanTandaiDuplikat($semua);
            if (empty($baru)) {
                return;
            }

            $urutan = $this->tentukanUrutanPerCabang($baru);

            DetailAset::$autoSinkronUnit = false;
            try {
                foreach ($baru as $i => $baris) {
                    $this->simpanBaris($baris, $urutan[$i], $ringkasan);
                    $this->totalMasuk++;
                }
                DetailAset::sinkronkanSemuaUnit();
            } finally {
                DetailAset::$autoSinkronUnit = true;
            }
        });

        return $ringkasan;
    }

    private function bacaSheet(Worksheet $sheet, ?string $cabangDariNamaFile = null, bool $isCsv = false): array
    {
        $hasil = [];
        $peta  = $this->petaKolom($sheet);
        $cabangSheet   = $isCsv ? $cabangDariNamaFile : $this->namaCabangDariSheet($sheet->getTitle());
        $barisTerakhir = $sheet->getHighestDataRow();

        for ($baris = $peta['_header'] + 1; $baris <= $barisTerakhir; $baris++) {
            $r = [];
            foreach ($peta as $kunci => $kolom) {
                if ($kunci !== '_header') {
                    $r[$kunci] = $this->nilaiSel($sheet, $kolom, $baris);
                }
            }

            $kode = $this->parseKode($r['kode']);
            if (!$kode) {
                continue;
            }

            $namaAset = $this->rapikanSpasi($r['nama']);
            if ($namaAset === '') {
                continue;
            }

            $namaCabang = $this->rapikanNamaCabang($r['cabang'] ?? null) ?? $cabangSheet;
            if ($namaCabang === null) {
                throw new \RuntimeException(
                    $isCsv
                        ? 'File CSV baris ' . $baris . ': kolom Cabang kosong. Tambahkan kolom "Cabang" pada file CSV.'
                        : 'Sheet "' . $sheet->getTitle() . '" baris ' . $baris . ': kolom Cabang masih kosong.'
                );
            }

            $hasil[] = [
                'sheet'       => $sheet->getTitle(),
                'baris'       => $baris,
                'r'           => $r,
                'kode'        => $kode,
                'nama_aset'   => $namaAset,
                'nama_cabang' => $namaCabang,
                'urutan_asli' => $this->urutanDariKode($r['kode']),
            ];
        }

        return $hasil;
    }

    private function petaKolom(Worksheet $sheet): array
    {
        $barisHeader = null;
        for ($b = 1; $b <= 20 && $barisHeader === null; $b++) {
            for ($k = 0; $k < self::BATAS_KOLOM; $k++) {
                if (preg_match(self::HEADER['nama'], $this->teksHeader($this->nilaiSel($sheet, $k, $b)))) {
                    $barisHeader = $b;
                    break;
                }
            }
        }
        if ($barisHeader === null) {
            throw new \RuntimeException('Sheet "' . $sheet->getTitle() . '": baris header (kolom "Nama Aset") tidak ditemukan.');
        }

        $peta = ['_header' => $barisHeader];

        foreach (self::HEADER as $kunci => $pola) {
            for ($k = 0; $k < self::BATAS_KOLOM; $k++) {
                if (preg_match($pola, $this->teksHeader($this->nilaiSel($sheet, $k, $barisHeader)))) {
                    $peta[$kunci] = $k;
                    break;
                }
            }
        }

        foreach (self::HEADER_STATUS as $kunci => $pola) {
            for ($b = $barisHeader; $b <= $barisHeader + 2 && !isset($peta[$kunci]); $b++) {
                for ($k = 0; $k < self::BATAS_KOLOM; $k++) {
                    if (preg_match($pola, $this->teksHeader($this->nilaiSel($sheet, $k, $b)))) {
                        $peta[$kunci] = $k;
                        break;
                    }
                }
            }
        }

        $peta['kode'] = 0;
        for ($k = 0; $k < self::BATAS_KOLOM; $k++) {
            if (preg_match('/^kode\s*aset/', $this->teksHeader($this->nilaiSel($sheet, $k, $barisHeader)))) {
                $peta['kode'] = $k;
                break;
            }
        }

        $hilang = array_diff(self::WAJIB, array_keys($peta));
        if (!empty($hilang)) {
            throw new \RuntimeException('Sheet "' . $sheet->getTitle() . '": kolom ' . implode(', ', $hilang) . ' tidak ditemukan di header.');
        }

        return $peta;
    }

    private function teksHeader($nilai): string
    {
        return strtolower($this->rapikanSpasi($nilai));
    }

    private function tentukanUrutanPerCabang(array $semua): array
    {
        $perCabang = [];
        foreach ($semua as $i => $baris) {
            $perCabang[strtolower($baris['nama_cabang'])][] = $i;
        }

        $hasil = [];
        foreach ($perCabang as $daftarIndeks) {
            $namaCabang = $semua[$daftarIndeks[0]]['nama_cabang'];
            $cabang     = $this->ambilCabang($namaCabang);
            $terakhir   = $this->urutanTerakhir($cabang);

            $asli     = array_map(fn ($i) => $semua[$i]['urutan_asli'], $daftarIndeks);
            $lengkap  = $terakhir === 0
                && !in_array(null, $asli, true)
                && count(array_unique($asli)) === count($asli);

            Log::info('Import aset - cabang ' . $namaCabang . ' (' . count($daftarIndeks) . ' baris): '
                . ($lengkap ? 'memakai urutan dari kode Excel' : 'diurutkan dari atas'
                    . ($terakhir > 0 ? ', dilanjutkan dari nomor ' . ($terakhir + 1) : '')));

            $berikut = $terakhir;
            foreach ($daftarIndeks as $i) {
                $hasil[$i] = $lengkap ? $semua[$i]['urutan_asli'] : ++$berikut;
            }
        }

        return $hasil;
    }

    private function simpanBaris(array $b, int $urutan, array &$ringkasan): void
    {
        $r    = $b['r'];
        $kode = $b['kode'];

        $cabang = $this->ambilCabang($b['nama_cabang']);
        $ringkasan[$cabang->nama_cabang] = ($ringkasan[$cabang->nama_cabang] ?? 0) + 1;

        $namaKategori = self::KATEGORI_PER_HURUF[$kode['huruf']]
            ?? $this->namaKategoriValid($r['kategori'] ?? null)
            ?? ('Kategori ' . $kode['huruf']);

        $kategori = Kategori::firstOrCreate(
            ['kode_kategori' => $kode['huruf']],
            ['nama_kategori' => $namaKategori]
        );

        $item = Item::firstOrCreate(
            ['nama_aset' => $b['nama_aset']],
            [
                'kategori_id' => $kategori->id,
                'awal_serial' => '-',
                'urutan'      => $kode['urutan'],
                'warna_seri'  => '#10b981',
            ]
        );

        $statusAset = $this->resolveStatusAset($r);

        $tanggal = $this->parseTanggal($r['tgl']);
        $kodeLengkap = sprintf(
            '%s/%s/%d',
            $kode['dasar'],
            date('dmY', strtotime($tanggal ?? 'now')),
            $urutan
        );

        $detail = DetailAset::create([
            'kode_aset'          => $kodeLengkap,
            'item_id'            => $item->id,
            'kategori_id'        => $kategori->id,
            'cabang_id'          => $cabang->getKey(),
            'merk'               => $this->rapikanSpasi($r['merk'] ?? null) ?: '-',
            'harga_beli'         => $this->parseAngka($r['harga']),
            'unit'               => max(1, (int) $this->parseAngka($r['unit'] ?? 1)),
            'tanggal_pengadaan'  => $tanggal,
            'Ruangan'            => $this->rapikanSpasi($r['ruangan']) ?: '-',
            'no_berita_acara'    => $this->parseBeritaAcara($r['ba'] ?? null),
            'status_aset'        => $statusAset,

            'nomor_seri'         => '-',
            'vendor_pengadaan'   => '-',
            'lokasi_saat_ini'    => $cabang->nama_cabang,
            'penanggung_jawab'   => '-',
            'status_berjalan'    => DetailAset::validasiAtauSelaraskanTipeAset($statusAset, null, true),
            'status_maintenance' => '-',
            'teknisi'            => '-',
            'biaya_servis'       => 0,
            'servis_terakhir'    => null,
            'servis_berikutnya'  => null,
            'riwayat_mutasi'     => '-',
            'tanggal_mutasi'     => null,
            'status_peleburan'   => '-',
            'tanggal_peleburan'  => null,
            'metode_peleburan'   => '-',
            'nilai_jual'         => 0,
            'lampiran'           => null,
        ]);

        if (!$this->modelSudahDicek) {
            $this->modelSudahDicek = true;
            $this->pastikanCabangTersimpan($detail);
        }
    }

    private function resolveStatusAset(array $r): string
    {
        if (!empty($r['baik'])) {
            return 'Baik';
        }
        if (!empty($r['ringan'])) {
            return 'Rusak Ringan';
        }
        if (!empty($r['sedang'])) {
            return 'Rusak Sedang';
        }
        if (!empty($r['berat'])) {
            return 'Rusak Berat';
        }
        return 'Tersedia';
    }

    /**
     * Membandingkan baris file import terhadap data aset yang sudah ada di database.
     * Menggunakan hitung kemunculan per sidik jari lengkap agar file dengan N unit identik tetap masuk
     * saat import pertama, namun langsung ditandai duplikat ("data sudah ada") jika diimport ulang.
     *
     * @return array<int, array<string, mixed>>
     */
    private function saringBarisBaruDanTandaiDuplikat(array $semua): array
    {
        $dbCounts = [];
        $rowsDb = DB::table('detail_aset as da')
            ->leftJoin('items as it', 'da.item_id', '=', 'it.id')
            ->leftJoin('cabangs as cb', 'da.cabang_id', '=', 'cb.id')
            ->select([
                'da.kode_aset',
                'da.merk',
                'da.harga_beli',
                'da.tanggal_pengadaan',
                'da.Ruangan',
                'da.no_berita_acara',
                'da.status_aset',
                'it.nama_aset',
                'cb.nama_cabang',
            ])
            ->get();

        foreach ($rowsDb as $row) {
            $kodeBagian = explode('/', (string) ($row->kode_aset ?? ''))[0];
            $kodeParsed = $this->parseKode($kodeBagian);
            $kodeDasar = $kodeParsed ? $kodeParsed['dasar'] : strtoupper(trim($kodeBagian));

            $sig = $this->formatSidikJariAset(
                $kodeDasar,
                (string) ($row->nama_aset ?? ''),
                $this->rapikanNamaCabang($row->nama_cabang ?? '') ?? (string) ($row->nama_cabang ?? ''),
                (string) ($row->merk ?? ''),
                (float) ($row->harga_beli ?? 0),
                $row->tanggal_pengadaan ? substr((string) $row->tanggal_pengadaan, 0, 10) : '',
                (string) ($row->Ruangan ?? ''),
                (string) ($row->no_berita_acara ?? ''),
                (string) ($row->status_aset ?? 'Tersedia')
            );
            $dbCounts[$sig] = ($dbCounts[$sig] ?? 0) + 1;
        }

        $baru = [];
        $kemunculanFile = [];

        foreach ($semua as $baris) {
            $r = $baris['r'];
            $sig = $this->formatSidikJariAset(
                $baris['kode']['dasar'],
                $baris['nama_aset'],
                $baris['nama_cabang'],
                $this->rapikanSpasi($r['merk'] ?? null) ?: '-',
                (float) $this->parseAngka($r['harga']),
                $this->parseTanggal($r['tgl']) ?? '',
                DetailAset::normalisasiRuangan($this->rapikanSpasi($r['ruangan'])),
                $this->parseBeritaAcara($r['ba'] ?? null),
                $this->resolveStatusAset($r)
            );

            $kemunculanFile[$sig] = ($kemunculanFile[$sig] ?? 0) + 1;
            if ($kemunculanFile[$sig] <= ($dbCounts[$sig] ?? 0)) {
                $this->totalDuplikat++;
                $label = $baris['nama_aset'] . ' - ' . $baris['nama_cabang'];
                if (count($this->contohDuplikat) < 5 && !in_array($label, $this->contohDuplikat, true)) {
                    $this->contohDuplikat[] = $label;
                }
                continue;
            }

            $baru[] = $baris;
        }

        return $baru;
    }

    private function formatSidikJariAset(
        string $kodeDasar,
        string $namaAset,
        string $namaCabang,
        string $merk,
        float $hargaBeli,
        string $tanggalPengadaan,
        string $ruangan,
        string $noBeritaAcara,
        string $statusAset
    ): string {
        $norm = fn (string $s): string => mb_strtolower($this->rapikanSpasi($s), 'UTF-8');

        return implode('|', [
            strtoupper(trim($kodeDasar)),
            $norm($namaAset),
            $norm($namaCabang),
            $norm($merk !== '' ? $merk : '-'),
            number_format($hargaBeli, 2, '.', ''),
            trim($tanggalPengadaan),
            mb_strtolower(DetailAset::normalisasiRuangan($this->rapikanSpasi($ruangan)), 'UTF-8'),
            $norm($noBeritaAcara !== '' ? $noBeritaAcara : '-'),
            $norm($statusAset !== '' ? $statusAset : 'Tersedia'),
        ]);
    }

    private function ambilCabang(string $nama): Cabang
    {
        return $this->cabangCache[strtolower($nama)] ??= Cabang::firstOrCreate(['nama_cabang' => $nama]);
    }

    private function urutanTerakhir(Cabang $cabang): int
    {
        $maks = 0;
        foreach (DetailAset::where('cabang_id', $cabang->getKey())->pluck('kode_aset') as $kode) {
            if (str_contains((string) $kode, '/')) {
                $maks = max($maks, (int) substr(strrchr((string) $kode, '/'), 1));
            }
        }

        return $maks;
    }

    private function urutanDariKode($mentah): ?int
    {
        if (preg_match('#^[A-Z]\s*[.,]\s*[0-9O]+/(?:\d{1,2}/\d{4}|\d{6,8})/\s*(\d+)$#i', trim((string) $mentah), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function pastikanCabangTersimpan(DetailAset $detail): void
    {
        if ($detail->cabang_id === null) {
            throw new \RuntimeException('cabang_id tidak ikut tersimpan. Tambahkan cabang_id ke $fillable di app/Models/DetailAset.php.');
        }
        if (!method_exists($detail, 'cabang')) {
            throw new \RuntimeException('Model DetailAset belum punya relasi cabang(). Tambahkan method cabang() berisi return $this->belongsTo(Cabang::class, cabang_id).');
        }
        if ($detail->cabang === null) {
            throw new \RuntimeException('Relasi cabang() di DetailAset tidak menemukan data cabang. Periksa kunci relasinya dan isi tabel cabangs.');
        }
    }

    private function namaCabangDariSheet(string $judul): ?string
    {
        if (!preg_match('/\((.+)\)\s*$/', $judul, $m)) {
            return null;
        }

        return $this->rapikanNamaCabang($m[1]);
    }

    private function rapikanNamaCabang($teks): ?string
    {
        $nama = $this->rapikanSpasi($teks);
        if ($nama === '' || $nama === '-' || str_starts_with($nama, '=') || str_starts_with($nama, '#')) {
            return null;
        }

        return self::KOREKSI_CABANG[strtolower($nama)] ?? $nama;
    }

    private function nilaiSel(Worksheet $sheet, int $kolom, int $baris)
    {
        $alamat = Coordinate::stringFromColumnIndex($kolom + 1) . $baris;
        if (!$sheet->cellExists($alamat)) {
            return null;
        }

        $cell  = $sheet->getCell($alamat);
        $nilai = $cell->getValue();

        if ($nilai instanceof RichText) {
            $nilai = $nilai->getPlainText();
        }
        if (is_string($nilai) && str_starts_with($nilai, '=')) {
            $nilai = method_exists($cell, 'getOldCalculatedValue') ? $cell->getOldCalculatedValue() : null;
        }
        if (is_string($nilai) && str_starts_with($nilai, '#')) {
            $nilai = null;
        }

        return $nilai;
    }

    private function parseKode($mentah): ?array
    {
        $kode = strtoupper(trim((string) $mentah));
        if ($kode === '' || str_contains($kode, 'KODE ASET')) {
            return null;
        }
        if (!preg_match('/^([A-Z])\s*[.,]\s*([0-9O]+)(?:\D|$)/', $kode, $m)) {
            return null;
        }

        $nomor = str_replace('O', '0', $m[2]);

        return [
            'huruf'  => $m[1],
            'nomor'  => $nomor,
            'dasar'  => $m[1] . '.' . $nomor,
            'urutan' => (int) $nomor,
        ];
    }

    private function parseAngka($nilai)
    {
        if (is_numeric($nilai)) {
            return $nilai;
        }
        $bersih = preg_replace('/[^0-9]/', '', (string) $nilai);

        return $bersih === '' ? 0 : (int) $bersih;
    }

    private function parseTanggal($nilai): ?string
    {
        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d');
        }
        if (is_numeric($nilai)) {
            return Date::excelToDateTimeObject($nilai)->format('Y-m-d');
        }

        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $teks, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $ts = strtotime($teks);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseBeritaAcara($nilai): string
    {
        $teks = trim((string) $nilai);
        if ($teks === '' || str_starts_with($teks, '#') || str_starts_with($teks, '=')) {
            return '-';
        }

        return $teks;
    }

    private function rapikanSpasi($teks): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $teks));
    }

    private function namaKategoriValid($teks): ?string
    {
        $bersih = $this->rapikanSpasi($teks);
        if ($bersih === '' || str_starts_with($bersih, '=') || str_starts_with($bersih, '#')) {
            return null;
        }

        return mb_convert_case($bersih, MB_CASE_TITLE, 'UTF-8');
    }
}