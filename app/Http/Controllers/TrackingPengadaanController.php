<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\DetailAset;
use App\Models\Kategori;
use App\Models\StatusAset;
use App\Support\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TrackingPengadaanController extends Controller
{
    private const BULAN_INDO = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user ? $user->role : null;
        $isMasterAdmin = RolePermission::isMasterAdmin($role);
        $bolehLihat = $isMasterAdmin || RolePermission::can($role, 'tracking_pengadaan', 'view');

        if (!$bolehLihat && ($request->ajax() || $request->wantsJson() || $request->filled('export'))) {
            return RolePermission::denyResponse($request, 'Anda tidak memiliki izin melihat Tracking Pengadaan. Hubungi Master Admin.');
        }

        $routeName = $isMasterAdmin ? 'tracking_pengadaan.index' : 'admin.tracking_pengadaan.index';
        $cabangs = Cabang::orderBy('nama_cabang')->get(['id', 'nama_cabang']);
        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori', 'kode_kategori']);
        $statusWarnaMap = StatusAset::all()
            ->pluck('warna_status', 'nama_status')
            ->filter()
            ->all();

        $filter = $this->ambilFilter($request);

        if (!$bolehLihat) {
            return view('tracking_pengadaan', [
                'isMasterAdmin'   => $isMasterAdmin,
                'routeName'       => $routeName,
                'cabangs'         => $cabangs,
                'kategoris'       => $kategoris,
                'statusWarnaMap'  => $statusWarnaMap,
                'filter'          => $filter,
                'ringkasan'       => $this->ringkasanKosong(),
                'rekapTanggal'    => [],
                'detailPaginated' => new LengthAwarePaginator([], 0, 50, 1, ['path' => $request->url(), 'query' => $request->query()]),
                'labelDetailAktif'=> 'Semua Tanggal Terfilter',
            ]);
        }

        $semuaBaris = DetailAset::with([
                'item:id,nama_aset',
                'kategori:id,nama_kategori,kode_kategori',
                'cabang:id,nama_cabang',
            ])
            ->orderByDesc('aset_id')
            ->get([
                'aset_id', 'item_id', 'kategori_id', 'cabang_id',
                'kode_aset', 'merk', 'unit', 'harga_beli', 'tanggal_pengadaan',
                'Ruangan', 'no_berita_acara', 'status_aset',
            ])
            ->map(fn (DetailAset $row) => $this->formatBarisAset($row));

        $terfilter = $this->terapkanFilter($semuaBaris, $filter);
        $rekapTanggal = $this->susunRekapTanggal($terfilter);

        if ($request->input('export') === 'csv') {
            return $this->eksporCsvRekap($rekapTanggal, $terfilter);
        }

        $detailTarget = $terfilter;
        $labelDetailAktif = 'Semua Tanggal Terfilter';

        if ($filter['detail_tanggal'] !== '') {
            $kunciDetail = $filter['detail_tanggal'];
            $detailTarget = $terfilter->filter(function (array $item) use ($kunciDetail) {
                $k = $item['ymd'] ?? '__NO_DATE__';
                return $k === $kunciDetail;
            })->values();

            if ($kunciDetail === '__NO_DATE__') {
                $labelDetailAktif = 'Tanpa Tanggal Pengadaan Valid';
            } elseif (isset($rekapTanggal[$kunciDetail])) {
                $infoTgl = $rekapTanggal[$kunciDetail];
                $labelDetailAktif = $infoTgl['label_tanggal'] . ' (Kode: ' . $infoTgl['dmy'] . ')';
            } else {
                $labelDetailAktif = $this->formatTanggalIndo($kunciDetail);
            }
        }

        $perPage = 50;
        $page = max(1, (int) $request->input('page', 1));
        $slice = $detailTarget->forPage($page, $perPage)->values();
        $detailPaginated = new LengthAwarePaginator(
            $slice,
            $detailTarget->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('tracking_pengadaan', [
            'isMasterAdmin'    => $isMasterAdmin,
            'routeName'        => $routeName,
            'cabangs'          => $cabangs,
            'kategoris'        => $kategoris,
            'statusWarnaMap'   => $statusWarnaMap,
            'filter'           => $filter,
            'ringkasan'        => $this->hitungRingkasan($terfilter, $rekapTanggal),
            'rekapTanggal'     => array_values($rekapTanggal),
            'detailPaginated'  => $detailPaginated,
            'labelDetailAktif' => $labelDetailAktif,
        ]);
    }

    private function ambilFilter(Request $request): array
    {
        return [
            'tanggal'        => $this->normalisasiInputTanggal($request->input('tanggal')),
            'dari'           => $this->normalisasiInputTanggal($request->input('dari')),
            'sampai'         => $this->normalisasiInputTanggal($request->input('sampai')),
            'cabang_id'      => trim((string) $request->input('cabang_id', '')),
            'kategori_id'    => trim((string) $request->input('kategori_id', '')),
            'q'              => trim((string) $request->input('q', '')),
            'detail_tanggal' => trim((string) $request->input('detail_tanggal', '')),
        ];
    }

    private function normalisasiInputTanggal($mentah): string
    {
        $teks = trim((string) ($mentah ?? ''));
        if ($teks === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $teks, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $teks;
        }
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $teks, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return '';
    }

    private function ekstrakInfoTanggalKode(?string $kodeAset, ?string $tglPengadaan): array
    {
        $kode = trim((string) $kodeAset);
        $parts = explode('/', $kode);
        $prefix = trim($parts[0] ?? '-');
        $tengah = trim($parts[1] ?? '');
        $buntut = count($parts) > 2 ? implode('/', array_slice($parts, 2)) : '';

        if ($tengah !== '') {
            if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $tengah, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return [
                    'ymd'    => "{$m[3]}-{$m[2]}-{$m[1]}",
                    'dmy'    => $tengah,
                    'prefix' => $prefix !== '' ? $prefix : '-',
                    'buntut' => $buntut,
                    'sumber' => 'kode_aset',
                ];
            }
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $tengah, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return [
                    'ymd'    => "{$m[3]}-{$m[2]}-{$m[1]}",
                    'dmy'    => "{$m[1]}{$m[2]}{$m[3]}",
                    'prefix' => $prefix !== '' ? $prefix : '-',
                    'buntut' => $buntut,
                    'sumber' => 'kode_aset',
                ];
            }
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tengah, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return [
                    'ymd'    => $tengah,
                    'dmy'    => "{$m[3]}{$m[2]}{$m[1]}",
                    'prefix' => $prefix !== '' ? $prefix : '-',
                    'buntut' => $buntut,
                    'sumber' => 'kode_aset',
                ];
            }
        }

        $fallback = substr(trim((string) $tglPengadaan), 0, 10);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fallback, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return [
                'ymd'    => $fallback,
                'dmy'    => "{$m[3]}{$m[2]}{$m[1]}",
                'prefix' => $prefix !== '' ? $prefix : '-',
                'buntut' => $buntut,
                'sumber' => 'tanggal_pengadaan',
            ];
        }

        return [
            'ymd'    => null,
            'dmy'    => '-',
            'prefix' => $prefix !== '' ? $prefix : '-',
            'buntut' => $buntut,
            'sumber' => 'tidak_terdeteksi',
        ];
    }

    private function formatBarisAset(DetailAset $row): array
    {
        $info = $this->ekstrakInfoTanggalKode($row->kode_aset, $row->tanggal_pengadaan);
        $ymd = $info['ymd'];

        return [
            'aset_id'           => (int) $row->aset_id,
            'kode_aset'         => (string) ($row->kode_aset ?: '-'),
            'kode_prefix'       => $info['prefix'],
            'kode_buntut'       => $info['buntut'],
            'dmy'               => $info['dmy'],
            'ymd'               => $ymd,
            'sumber_tanggal'    => $info['sumber'],
            'label_tanggal'     => $ymd ? $this->formatTanggalIndo($ymd) : 'Tanpa Tanggal Valid',
            'nama_aset'         => (string) ($row->item->nama_aset ?? 'Tanpa Nama'),
            'kategori_id'       => $row->kategori_id !== null ? (string) $row->kategori_id : '',
            'nama_kategori'     => (string) ($row->kategori->nama_kategori ?? '-'),
            'cabang_id'         => $row->cabang_id !== null ? (string) $row->cabang_id : '',
            'nama_cabang'       => (string) ($row->cabang->nama_cabang ?? '-'),
            'ruangan'           => ($row->Ruangan && $row->Ruangan !== '-') ? (string) $row->Ruangan : '-',
            'merk'              => ($row->merk && $row->merk !== '-') ? (string) $row->merk : '-',
            'harga_beli'        => (float) ($row->harga_beli ?? 0),
            'unit_cabang'       => max(1, (int) ($row->unit ?? 1)),
            'no_berita_acara'   => ($row->no_berita_acara && $row->no_berita_acara !== '-') ? (string) $row->no_berita_acara : '-',
            'status_aset'       => (string) ($row->status_aset ?: 'Tersedia'),
        ];
    }

    private function terapkanFilter(Collection $rows, array $filter): Collection
    {
        return $rows->filter(function (array $row) use ($filter) {
            if ($filter['tanggal'] !== '' && $row['ymd'] !== $filter['tanggal']) {
                return false;
            }
            if ($filter['dari'] !== '' && ($row['ymd'] === null || $row['ymd'] < $filter['dari'])) {
                return false;
            }
            if ($filter['sampai'] !== '' && ($row['ymd'] === null || $row['ymd'] > $filter['sampai'])) {
                return false;
            }
            if ($filter['cabang_id'] !== '' && $row['cabang_id'] !== $filter['cabang_id']) {
                return false;
            }
            if ($filter['kategori_id'] !== '' && $row['kategori_id'] !== $filter['kategori_id']) {
                return false;
            }
            if ($filter['q'] !== '') {
                $needle = mb_strtolower($filter['q'], 'UTF-8');
                $haystack = mb_strtolower(implode(' ', [
                    $row['kode_aset'],
                    $row['dmy'],
                    $row['ymd'] ?? '',
                    $row['label_tanggal'],
                    $row['nama_aset'],
                    $row['nama_kategori'],
                    $row['nama_cabang'],
                    $row['ruangan'],
                    $row['merk'],
                    $row['no_berita_acara'],
                ]), 'UTF-8');
                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }
            return true;
        })->values();
    }

    private function susunRekapTanggal(Collection $rows): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $kunci = $row['ymd'] ?? '__NO_DATE__';
            if (!isset($buckets[$kunci])) {
                $buckets[$kunci] = [
                    'key'           => $kunci,
                    'ymd'           => $row['ymd'],
                    'dmy'           => $row['dmy'],
                    'label_tanggal' => $row['label_tanggal'],
                    'jumlah_aset'   => 0,
                    'total_nilai'   => 0.0,
                    'per_kategori'  => [],
                    'per_cabang'    => [],
                    'per_item'      => [],
                ];
            }

            $buckets[$kunci]['jumlah_aset']++;
            $buckets[$kunci]['total_nilai'] += $row['harga_beli'];

            $kat = $row['nama_kategori'];
            $cab = $row['nama_cabang'];
            $itemLabel = $row['nama_aset'] . ' (' . $row['kode_prefix'] . ')';

            $buckets[$kunci]['per_kategori'][$kat] = ($buckets[$kunci]['per_kategori'][$kat] ?? 0) + 1;
            $buckets[$kunci]['per_cabang'][$cab]   = ($buckets[$kunci]['per_cabang'][$cab] ?? 0) + 1;
            $buckets[$kunci]['per_item'][$itemLabel] = ($buckets[$kunci]['per_item'][$itemLabel] ?? 0) + 1;
        }

        foreach ($buckets as &$b) {
            arsort($b['per_kategori']);
            arsort($b['per_cabang']);
            arsort($b['per_item']);
        }
        unset($b);

        uksort($buckets, function (string $a, string $b) {
            if ($a === '__NO_DATE__') {
                return 1;
            }
            if ($b === '__NO_DATE__') {
                return -1;
            }
            return strcmp($b, $a);
        });

        return $buckets;
    }

    private function hitungRingkasan(Collection $terfilter, array $rekapTanggal): array
    {
        $validTanggal = array_values(array_filter($rekapTanggal, fn ($b) => $b['ymd'] !== null));
        $terbanyak = null;
        foreach ($validTanggal as $b) {
            if ($terbanyak === null || $b['jumlah_aset'] > $terbanyak['jumlah_aset']) {
                $terbanyak = $b;
            }
        }
        $terbaru = $validTanggal[0] ?? null;

        return [
            'total_aset'        => $terfilter->count(),
            'total_nilai'       => (float) $terfilter->sum('harga_beli'),
            'jumlah_tanggal'    => count($validTanggal),
            'tanggal_terbanyak' => $terbanyak ? [
                'label'  => $terbanyak['label_tanggal'],
                'dmy'    => $terbanyak['dmy'],
                'jumlah' => $terbanyak['jumlah_aset'],
            ] : null,
            'tanggal_terbaru'   => $terbaru ? [
                'label'  => $terbaru['label_tanggal'],
                'dmy'    => $terbaru['dmy'],
                'jumlah' => $terbaru['jumlah_aset'],
            ] : null,
        ];
    }

    private function ringkasanKosong(): array
    {
        return [
            'total_aset'        => 0,
            'total_nilai'       => 0.0,
            'jumlah_tanggal'    => 0,
            'tanggal_terbanyak' => null,
            'tanggal_terbaru'   => null,
        ];
    }

    private function formatTanggalIndo(string $ymd): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) {
            return $ymd;
        }
        $bulan = self::BULAN_INDO[(int) $m[2]] ?? $m[2];
        return $m[3] . ' ' . $bulan . ' ' . $m[1];
    }

    private function eksporCsvRekap(array $rekapTanggal, Collection $terfilter)
    {
        $amanCsv = static function ($nilai): string {
            $teks = (string) $nilai;
            if ($teks !== '' && preg_match('/^[=+\-@\t\r]/', $teks)) {
                return "'" . $teks;
            }
            return $teks;
        };

        $filename = 'tracking_pengadaan_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rekapTanggal, $terfilter, $amanCsv) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['REKAP PENGADAAN ASET PER TANGGAL']);
            fputcsv($out, ['Tanggal Pengadaan', 'Kode Tanggal (DDMMYYYY)', 'Jumlah Pengadaan (Unit)', 'Rincian Item & Kode', 'Rincian Kategori', 'Cabang', 'Total Nilai Beli (Rp)']);

            foreach ($rekapTanggal as $r) {
                $rincianItem = collect($r['per_item'])->map(fn ($c, $n) => "{$n}: {$c}")->implode(', ');
                $rincianKat  = collect($r['per_kategori'])->map(fn ($c, $n) => "{$n}: {$c}")->implode(', ');
                $rincianCab  = collect($r['per_cabang'])->map(fn ($c, $n) => "{$n}: {$c}")->implode(', ');

                fputcsv($out, [
                    $amanCsv($r['label_tanggal']),
                    $amanCsv($r['dmy']),
                    $r['jumlah_aset'],
                    $amanCsv($rincianItem),
                    $amanCsv($rincianKat),
                    $amanCsv($rincianCab),
                    (int) round($r['total_nilai']),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['RINCIAN DETAIL ASET HASIL PENGADAAN']);
            fputcsv($out, ['Kode Aset', 'Tgl Kode (DDMMYYYY)', 'Tanggal Pengadaan', 'Nama Aset', 'Kategori', 'Cabang', 'Ruangan', 'Merk', 'Harga Beli (Rp)', 'Total Unit Cabang Saat Ini', 'No. Berita Acara', 'Status']);

            foreach ($terfilter as $row) {
                fputcsv($out, [
                    $amanCsv($row['kode_aset']),
                    $amanCsv($row['dmy']),
                    $amanCsv($row['label_tanggal']),
                    $amanCsv($row['nama_aset']),
                    $amanCsv($row['nama_kategori']),
                    $amanCsv($row['nama_cabang']),
                    $amanCsv($row['ruangan']),
                    $amanCsv($row['merk']),
                    (int) round($row['harga_beli']),
                    $row['unit_cabang'],
                    $amanCsv($row['no_berita_acara']),
                    $amanCsv($row['status_aset']),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
