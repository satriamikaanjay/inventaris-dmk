<?php

namespace App\Http\Controllers;

use App\Models\DetailAset;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveSyncController extends Controller
{
    public function version(Request $request): JsonResponse
    {
        $scope = strtolower(trim((string) $request->query('scope', 'global')));
        $scopes = [
            'aset'      => $this->hashAset(),
            'master'    => $this->hashMaster(),
            'lokasi'    => $this->hashLokasi(),
            'pengajuan' => $this->hashPengajuan(),
            'users'     => $this->hashUsers(),
            'reset_req' => $this->hashResetRequests(),
        ];

        $qrHash = 'none';
        if (in_array($scope, ['barcode', 'global'], true)) {
            $qrHash = $this->hashQrCodes();
        }
        $scopes['qr'] = $qrHash;

        $scopeParts = match ($scope) {
            'dashboard'          => [$scopes['aset'], $scopes['master'], $scopes['users']],
            'data_aset'          => [$scopes['aset'], $scopes['master'], $this->fileSignature(storage_path('app/lampiran_meta.json'))],
            'barcode'            => [$scopes['aset'], $scopes['master'], $scopes['qr']],
            'tracking_pengadaan' => [$scopes['aset'], $scopes['master']],
            'laporan'            => [$scopes['aset'], $scopes['master'], $scopes['pengajuan']],
            'notifikasi'         => [$scopes['aset'], $scopes['master'], $scopes['reset_req']],
            'kategori'           => [$scopes['master']],
            'cabang'             => [$scopes['master'], $scopes['aset']],
            'lokasi'             => [$scopes['lokasi']],
            'kelola_akun'        => [$scopes['users']],
            'pengajuan',
            'riwayat',
            'roles'              => [$scopes['aset'], $scopes['master'], $scopes['reset_req']],
            default              => array_values($scopes),
        };

        $notifCount = $this->computeSidebarNotifCount();
        $sidebarVersion = sha1($scopes['aset'] . '|' . $scopes['reset_req'] . '|' . $notifCount);

        return response()
            ->json([
                'version'         => sha1(implode('|', $scopeParts)),
                'sidebar_version' => $sidebarVersion,
                'notif_count'     => $notifCount,
                'scope'           => $scope,
                'scopes'          => $scopes,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function hashAset(): string
    {
        $sig = $this->aggregateSignature(
            'detail_aset',
            "COUNT(*) AS c, COALESCE(SUM(aset_id), 0) AS sid, COALESCE(MAX(updated_at), '') AS mu, "
            . "COALESCE(SUM(CRC32(CONCAT_WS('#', aset_id, item_id, kode_aset, kategori_id, cabang_id, merk, nomor_seri, unit, tanggal_pengadaan, harga_beli, Ruangan, vendor_pengadaan, lokasi_saat_ini, penanggung_jawab, status_aset, status_berjalan, status_maintenance, servis_terakhir, biaya_servis, teknisi, servis_berikutnya, riwayat_mutasi, tanggal_mutasi, status_peleburan, tanggal_peleburan, metode_peleburan, nilai_jual, lampiran, no_berita_acara, updated_at))), 0) AS chk"
        );
        $sig .= '|' . $this->fileSignature(storage_path('app/lampiran_meta.json'));
        return sha1($sig);
    }

    private function hashMaster(): string
    {
        $parts = [
            $this->aggregateSignature('kategoris', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nama_kategori, kode_kategori, updated_at))), 0) AS chk"),
            $this->aggregateSignature('items', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, kategori_id, urutan, nama_aset, awal_serial, warna_seri, updated_at))), 0) AS chk"),
            $this->aggregateSignature('cabangs', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nama_cabang, keterangan, updated_at))), 0) AS chk"),
            $this->aggregateSignature('status_asets', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nama_status, warna_status, updated_at))), 0) AS chk"),
        ];
        return sha1(implode('|', $parts));
    }

    private function hashLokasi(): string
    {
        if (!Schema::hasTable('lokasis')) {
            return 'no-table';
        }
        return sha1($this->aggregateSignature('lokasis', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nama_lokasi, kode_lokasi, updated_at))), 0) AS chk"));
    }

    private function hashPengajuan(): string
    {
        if (!Schema::hasTable('pengajuan_asets')) {
            return 'no-table';
        }
        return sha1($this->aggregateSignature(
            'pengajuan_asets',
            "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nomor_pengajuan, jenis, aset_id, kode_aset, nama_aset, kategori_nama, unit_jumlah, pemohon, status, catatan_admin, updated_at))), 0) AS chk"
        ));
    }

    private function hashUsers(): string
    {
        $parts = [
            $this->aggregateSignature('users', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', users_id, nama_lengkap, email, role_id, role, updated_at))), 0) AS chk"),
            $this->aggregateSignature('roles', "COUNT(*) AS c, COALESCE(SUM(CRC32(CONCAT_WS('#', id, nama_role, deskripsi, updated_at))), 0) AS chk"),
        ];
        return sha1(implode('|', $parts));
    }

    private function hashQrCodes(): string
    {
        $dir = storage_path('app/public/qrcodes');
        if (!is_dir($dir)) {
            return 'empty';
        }
        $count = 0;
        $maxMtime = 0;
        $sumSize = 0;
        $files = @scandir($dir) ?: [];
        foreach ($files as $fn) {
            if ($fn === '.' || $fn === '..' || !str_ends_with($fn, '.svg')) {
                continue;
            }
            $count++;
            $path = $dir . DIRECTORY_SEPARATOR . $fn;
            $mt = (int) @filemtime($path);
            $sz = (int) @filesize($path);
            if ($mt > $maxMtime) {
                $maxMtime = $mt;
            }
            $sumSize += $sz + $mt;
        }
        return sha1("{$count}|{$maxMtime}|{$sumSize}");
    }

    private function hashResetRequests(): string
    {
        return $this->fileSignature(storage_path('app/password_reset_requests.json'));
    }

    private function aggregateSignature(string $table, string $selectRaw): string
    {
        try {
            if (!Schema::hasTable($table)) {
                return "{$table}:missing";
            }
            $row = DB::table($table)->selectRaw($selectRaw)->first();
            return $table . ':' . json_encode($row);
        } catch (\Throwable $e) {
            return "{$table}:err";
        }
    }

    private function fileSignature(string $path): string
    {
        if (!is_file($path)) {
            return 'absent';
        }
        clearstatcache(true, $path);
        return ((int) @filemtime($path)) . ':' . ((int) @filesize($path));
    }

    private function computeSidebarNotifCount(): int
    {
        try {
            $batasMendekatiNotif = Carbon::today()->addDays(30)->toDateString();
            $count = DetailAset::where(function ($sub) use ($batasMendekatiNotif) {
                $sub->where(function ($s1) use ($batasMendekatiNotif) {
                    $s1->whereNotNull('servis_berikutnya')
                       ->where('servis_berikutnya', '!=', '-')
                       ->where('servis_berikutnya', '<=', $batasMendekatiNotif);
                })->orWhere('status_aset', 'like', '%Rusak%');
            })->count();

            $roleLower = strtolower(trim((string) (Auth::user()->role ?? '')));
            if ($roleLower === 'master_admin' || $roleLower === 'masteradmin') {
                $resetReqFile = storage_path('app/password_reset_requests.json');
                if (is_file($resetReqFile)) {
                    $list = json_decode((string) @file_get_contents($resetReqFile), true);
                    if (is_array($list)) {
                        $count += count(array_filter($list, fn ($r) => ($r['status'] ?? 'pending') === 'pending'));
                    }
                }
            }

            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
