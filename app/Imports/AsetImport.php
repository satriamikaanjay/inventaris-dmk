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
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import inventaris semua cabang dari satu file Excel.
 * - Sheet yang dibaca: yang judulnya diawali "Inventaris" (mis. "Inventaris DMK (Juanda)").
 *   Sheet lain ("Code Barang", "Daftar Inventaris Kantor", dst.) dilewati.
 * - Kolom dibaca berdasarkan NAMA HEADER (bukan posisi), jadi urutan kolom boleh berbeda.
 * - Cabang diambil dari kolom "Cabang" tiap baris, lalu otomatis dibuat di tabel cabangs bila
 *   belum ada. Kalau sheet tidak punya kolom "Cabang", dipakai nama di dalam kurung pada judul
 *   sheet (mis. "Inventaris DMK (Juanda)" -> Juanda).
 * - Kategori ditentukan dari HURUF KODE sesuai sheet "Code Barang".
 * - Nomor urut di ujung kode (KODE/DDMMYYYY/URUTAN) dihitung PER CABANG:
 *     • cabang yang kodenya sudah lengkap di Excel (mis. B.18/06/2023/ 1) memakai urutan dari Excel;
 *     • cabang yang kodenya belum lengkap diurutkan dari atas ke bawah (1, 2, 3, ...).
 */
class AsetImport
{
    // Pola nama header (huruf kecil) untuk mengenali kolom. Dicari di baris header tabel.
    private const HEADER = [
        'nama'     => '/^nama\s*aset/',
        'kategori' => '/^kategori/',
        'cabang'   => '/^cabang/',
        'merk'     => '/^merk/',
        'harga'    => '/^harga/',                 // "Harga Beli (IDR)"; "Sumber Harga" tidak ikut karena diawali "sumber"
        'unit'     => '/^unit/',
        'tgl'      => '/^(tgl|tanggal)\s*pengadaan/',
        'ruangan'  => '/^ruang/',
        'ba'       => '/berita\s*acara|\(b\.?a\.?\)/',
    ];

    // Sub-header status kondisi (dicari di 2 baris di bawah baris header)
    private const HEADER_STATUS = [
        'baik'   => '/^baik$/',
        'ringan' => '/^ringan$/',
        'sedang' => '/^sedang$/',
        'berat'  => '/^berat$/',
    ];

    // Kolom yang wajib ada (kategori, cabang, merk, ba boleh tidak ada)
    private const WAJIB = ['nama', 'harga', 'unit', 'tgl', 'ruangan', 'baik', 'ringan', 'sedang', 'berat'];

    private const BATAS_KOLOM = 30;   // jumlah kolom yang dipindai saat mencari header

    // Huruf kode -> kategori (sumber: sheet "Code Barang"). Satu huruf = satu kategori.
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

    // Salah ketik nama cabang -> nama yang benar
    private const KOREKSI_CABANG = [
        'labohan bajo' => 'Labuan Bajo',
    ];

    /** @var array<string,Cabang> cache cabang per nama (huruf kecil) */
    private array $cabangCache = [];

    /** Model DetailAset baru dicek sekali, pada baris pertama yang tersimpan. */
    private bool $modelSudahDicek = false;

    /**
     * @return array<string,int>  [nama cabang => jumlah aset yang diimport]
     */
    public function import(string $path, string $ekstensi = 'xlsx'): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $reader = IOFactory::createReader(strtolower($ekstensi) === 'xls' ? 'Xls' : 'Xlsx');
        $reader->setReadEmptyCells(false);          // hemat memori: abaikan sel kosong yang hanya berformat
        $spreadsheet = $reader->load($path);

        $ringkasan = [];

        DB::transaction(function () use ($spreadsheet, &$ringkasan) {
            // 1. Baca semua baris dari semua sheet inventaris
            $semua = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                if (!preg_match('/^\s*Inventaris\b/i', $sheet->getTitle())) {
                    continue;   // lewati "Code Barang", "Daftar Inventaris Kantor", dst.
                }
                foreach ($this->bacaSheet($sheet) as $baris) {
                    $semua[] = $baris;
                }
            }

            // 2. Tentukan nomor urut tiap aset (dihitung per cabang)
            $urutan = $this->tentukanUrutanPerCabang($semua);

            // 3. Simpan
            foreach ($semua as $i => $baris) {
                $this->simpanBaris($baris, $urutan[$i], $ringkasan);
            }
        });

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $ringkasan;
    }

    /** Baca semua baris data satu sheet (baris judul/header/kosong dilewati). */
    private function bacaSheet(Worksheet $sheet): array
    {
        $hasil = [];
        $peta  = $this->petaKolom($sheet);
        // Cadangan bila sel Cabang kosong / sheet tidak punya kolom Cabang: nama di dalam kurung pada judul sheet
        $cabangSheet   = $this->namaCabangDariSheet($sheet->getTitle());
        $barisTerakhir = $sheet->getHighestDataRow();

        for ($baris = $peta['_header'] + 1; $baris <= $barisTerakhir; $baris++) {
            $r = [];
            foreach ($peta as $kunci => $kolom) {
                if ($kunci !== '_header') {
                    $r[$kunci] = $this->nilaiSel($sheet, $kolom, $baris);
                }
            }

            // Baris judul / sub-header / kosong tidak punya kode berpola "X.NN" -> dilewati otomatis
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
                    'Sheet "' . $sheet->getTitle() . '" baris ' . $baris . ': kolom Cabang masih kosong.'
                );
            }

            $hasil[] = [
                'sheet'       => $sheet->getTitle(),
                'baris'       => $baris,
                'r'           => $r,
                'kode'        => $kode,
                'nama_aset'   => $namaAset,
                'nama_cabang' => $namaCabang,
                'urutan_asli' => $this->urutanDariKode($r['kode']),   // angka paling kanan, bila ada
            ];
        }

        return $hasil;
    }

    /**
     * Cari posisi tiap kolom dari NAMA HEADER-nya.
     * @return array<string,int>  mis. ['kode'=>0,'nama'=>1,'kategori'=>2,'cabang'=>3,...,'_header'=>7]
     */
    private function petaKolom(Worksheet $sheet): array
    {
        // 1. baris header = baris yang punya sel "Nama Aset"
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

        // 2. kolom-kolom utama (di baris header)
        foreach (self::HEADER as $kunci => $pola) {
            for ($k = 0; $k < self::BATAS_KOLOM; $k++) {
                if (preg_match($pola, $this->teksHeader($this->nilaiSel($sheet, $k, $barisHeader)))) {
                    $peta[$kunci] = $k;
                    break;
                }
            }
        }

        // 3. kolom status kondisi (di 2 baris di bawah header)
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

        // 4. kolom kode: yang berjudul "Kode Aset"; bila tidak ada (mis. sheet Kediri berjudul "NO") pakai kolom pertama
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

    /**
     * Nomor urut aset PER CABANG.
     *  - Semua baris cabang itu punya urutan di kodenya (mis. "B.18/06/2023/ 1") dan tidak ada yang
     *    kembar  -> pakai urutan dari Excel.
     *  - Selain itu (kode belum lengkap)  -> urut dari atas ke bawah: 1, 2, 3, ...
     *  - Cabang yang sudah punya data di database -> lanjut dari nomor terakhir cabang itu.
     *
     * @return array<int,int>  [indeks baris => nomor urut]
     */
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

    /** Simpan satu baris Excel ke database. */
    private function simpanBaris(array $b, int $urutan, array &$ringkasan): void
    {
        $r    = $b['r'];
        $kode = $b['kode'];

        // 0. CABANG -> otomatis masuk tabel cabangs
        $cabang = $this->ambilCabang($b['nama_cabang']);
        $ringkasan[$cabang->nama_cabang] = ($ringkasan[$cabang->nama_cabang] ?? 0) + 1;

        // 1. KATEGORI berdasarkan huruf kode
        $namaKategori = self::KATEGORI_PER_HURUF[$kode['huruf']]
            ?? $this->namaKategoriValid($r['kategori'] ?? null)
            ?? ('Kategori ' . $kode['huruf']);

        $kategori = Kategori::firstOrCreate(
            ['kode_kategori' => $kode['huruf']],
            ['nama_kategori' => $namaKategori]
        );

        // 2. MASTER ITEM (dicari berdasarkan nama aset)
        $item = Item::firstOrCreate(
            ['nama_aset' => $b['nama_aset']],
            [
                'kategori_id' => $kategori->id,
                'awal_serial' => '-',
                'urutan'      => $kode['urutan'],
                'warna_seri'  => '#10b981',
            ]
        );

        // 3. STATUS (kolom J-M)
        $statusAset = 'Tersedia';
        if (!empty($r['baik'])) {
            $statusAset = 'Baik';
        } elseif (!empty($r['ringan'])) {
            $statusAset = 'Rusak Ringan';
        } elseif (!empty($r['sedang'])) {
            $statusAset = 'Rusak Sedang';
        } elseif (!empty($r['berat'])) {
            $statusAset = 'Rusak Berat';
        }

        // 4. KODE LENGKAP: [KODE]/[DDMMYYYY TGL PENGADAAN]/[URUTAN DI CABANG]  mis. C.01/25062023/3
        $tanggal = $this->parseTanggal($r['tgl']);
        $kodeLengkap = sprintf(
            '%s/%s/%d',
            $kode['dasar'],
            date('dmY', strtotime($tanggal ?? 'now')),
            $urutan
        );

        // 5. SIMPAN
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

            // Kolom web yang tidak ada di Excel
            'nomor_seri'         => '-',
            'vendor_pengadaan'   => '-',
            'lokasi_saat_ini'    => $cabang->nama_cabang,   // awalnya = cabang asal
            'penanggung_jawab'   => '-',
            'status_berjalan'    => 'Tidak Ada',
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

    private function ambilCabang(string $nama): Cabang
    {
        return $this->cabangCache[strtolower($nama)] ??= Cabang::firstOrCreate(['nama_cabang' => $nama]);
    }

    /** Nomor urut terbesar (angka paling kanan pada kode_aset) yang sudah ada di database untuk cabang ini. */
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

    /**
     * Urutan aset yang sudah tertulis di kode Excel (angka paling kanan), atau null bila kode belum lengkap.
     *   "B.18/06/2023/ 1"   -> 1      (KODE/BULAN/TAHUN/URUTAN)
     *   "C.01/10202026/3"   -> 3      (KODE/TGLBULANTAHUN/URUTAN)
     *   "C.31/Ruang Admin/06/2023", "B.01/FINANCE", "B.01"  -> null
     */
    private function urutanDariKode($mentah): ?int
    {
        if (preg_match('#^[A-Z]\s*[.,]\s*[0-9O]+/(?:\d{1,2}/\d{4}|\d{6,8})/\s*(\d+)$#i', trim((string) $mentah), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Supaya kolom Cabang tidak kosong secara diam-diam: kalau cabang_id tidak ikut
     * tersimpan atau relasinya belum ada, import dibatalkan dengan pesan yang jelas.
     */
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

    /** "Inventaris DMK (Juanda)" => "Juanda"; judul tanpa kurung => null. */
    private function namaCabangDariSheet(string $judul): ?string
    {
        if (!preg_match('/\((.+)\)\s*$/', $judul, $m)) {
            return null;
        }

        return $this->rapikanNamaCabang($m[1]);
    }

    /** Rapikan spasi + koreksi salah ketik; sel kosong/rumus/error => null. */
    private function rapikanNamaCabang($teks): ?string
    {
        $nama = $this->rapikanSpasi($teks);
        if ($nama === '' || $nama === '-' || str_starts_with($nama, '=') || str_starts_with($nama, '#')) {
            return null;
        }

        return self::KOREKSI_CABANG[strtolower($nama)] ?? $nama;
    }

    /**
     * Baca nilai sel. Kalau selnya rumus, pakai hasil yang tersimpan di file
     * (bukan menghitung ulang, karena rumusnya bisa merujuk file Excel lain).
     */
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
            $nilai = null;   // #NAME?, #REF!, dst.
        }

        return $nilai;
    }

    /**
     * "B.01/FINANCE" => huruf B, nomor 01, dasar B.01
     * Menerima juga "C.31/Ruang Admin/06/2023" dan "B.18/06/2023/ 1".
     * Salah ketik dikoreksi: "C,04" => "C.04", "E.O9" => "E.09".
     */
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

    /** "Rp2.690.000" => 2690000 ; angka biasa dikembalikan apa adanya. */
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
        // 15/07/2026 atau 15-07-2026 (hari/bulan/tahun)
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $teks, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $ts = strtotime($teks);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    /** Nilai error/rumus rusak dianggap kosong. */
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

    /** Nama kategori cadangan (hanya dipakai bila huruf kode tidak ada di KATEGORI_PER_HURUF). */
    private function namaKategoriValid($teks): ?string
    {
        $bersih = $this->rapikanSpasi($teks);
        if ($bersih === '' || str_starts_with($bersih, '=') || str_starts_with($bersih, '#')) {
            return null;
        }

        return mb_convert_case($bersih, MB_CASE_TITLE, 'UTF-8');
    }
}