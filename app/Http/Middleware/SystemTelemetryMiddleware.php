<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SystemTelemetryMiddleware
{
    private const MAX_ENTRIES = 150;

    public function handle(Request $request, Closure $next): Response
    {
        $path = '/' . ltrim($request->path(), '/');

        if ($path !== '/dev-console' && !str_starts_with($path, '/dev-console/')) {
            $lockFile = storage_path('app/web_emergency_lock.json');
            if (is_file($lockFile)) {
                $lockData = json_decode((string) @file_get_contents($lockFile), true);
                if (is_array($lockData) && !empty($lockData['locked'])) {
                    if ($request->is('api/*') || $path === '/api' || $request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Web sedang maintenance. Sistem dikunci sementara oleh Developer.',
                        ], 503);
                    }
                    return response()->view('maintenance', ['lockInfo' => $lockData], 503);
                }
            }
        }

        $this->recordOnlinePresence($request, $path);

        if ($this->shouldSkipTelemetry($request, $path)) {
            return $next($request);
        }

        $start = microtime(true);
        $response = $next($request);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        try {
            $status = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;
            $channel = ($request->is('api/*') || $path === '/api') ? 'mobile' : 'web';
            $entry = [
                'channel'     => $channel,
                'method'      => $request->method(),
                'path'        => $path,
                'status'      => $status,
                'duration_ms' => $durationMs,
                'is_error'    => $status >= 400,
                'ip'          => (string) $request->ip(),
                'timestamp'   => date('Y-m-d H:i:s'),
            ];

            $file = storage_path('app/system_telemetry.json');
            $entries = [];
            if (is_file($file)) {
                $decoded = json_decode((string) @file_get_contents($file), true);
                if (is_array($decoded)) {
                    $entries = $decoded;
                }
            }

            array_unshift($entries, $entry);
            if (count($entries) > self::MAX_ENTRIES) {
                $entries = array_slice($entries, 0, self::MAX_ENTRIES);
            }

            @file_put_contents($file, json_encode($entries, JSON_UNESCAPED_SLASHES), LOCK_EX);

            $this->recordRoleActivityStream($request, $path, $channel);
        } catch (\Throwable $e) {
        }

        return $response;
    }

    private function recordRoleActivityStream(Request $request, string $path, string $channel): void
    {
        if (str_starts_with($path, '/dev-console')) {
            return;
        }

        try {
            $role = '';
            $username = '';

            if ($channel === 'mobile') {
                $role = 'user';
                $username = trim((string) $request->input('nama_user', $request->input('pemohon', '')));
                $uid = (int) $request->input('user_id', 0);
                if ($username === '' && $uid > 0 && Schema::hasTable('users')) {
                    $uRow = DB::table('users')->select('nama_lengkap')->where('users_id', $uid)->first();
                    if ($uRow) {
                        $username = (string) ($uRow->nama_lengkap ?? '');
                    }
                }
                if ($username === '') {
                    $username = 'User Mobile';
                }
            } elseif (Auth::check()) {
                $user = Auth::user();
                $role = strtolower(str_replace([' ', '-'], '_', trim((string) ($user->role ?? ''))));
                if ($role === 'masteradmin') {
                    $role = 'master_admin';
                }
                if ($role === 'developer' || $role === '') {
                    return;
                }
                $username = (string) ($user->nama_lengkap ?? 'Akun');
            } else {
                return;
            }

            $ringkasan = $this->formatRingkasanAksi($request->method(), $path, $channel);
            if ($ringkasan === '') {
                return;
            }

            $streamFile = storage_path('app/dev_activity_stream.json');
            $stream = [];
            if (is_file($streamFile)) {
                $dec = json_decode((string) @file_get_contents($streamFile), true);
                if (is_array($dec)) {
                    $stream = $dec;
                }
            }

            array_unshift($stream, [
                'role'      => $role,
                'username'  => $username,
                'ringkasan' => $ringkasan,
                'tanggal'   => date('d/m/Y H:i:s'),
                'ts'        => microtime(true),
            ]);

            if (count($stream) > 250) {
                $stream = array_slice($stream, 0, 250);
            }

            @file_put_contents($streamFile, json_encode($stream, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        } catch (\Throwable $e) {
        }
    }

    private function formatRingkasanAksi(string $method, string $path, string $channel): string
    {
        $m = strtoupper($method);
        $p = strtolower($path);

        if ($channel === 'mobile') {
            if (str_contains($p, '/api/login')) return 'Login ke APK Mobile';
            if (str_contains($p, '/api/pengajuan/user')) return 'Melihat riwayat pengajuan di APK Mobile';
            if (str_contains($p, '/api/pengajuan') && $m === 'POST') return 'Mengirim pengajuan aset dari APK Mobile';
            if (str_contains($p, '/api/inventory')) return 'Melihat daftar inventaris aset di APK Mobile';
            if (str_contains($p, '/api/categories') || str_contains($p, '/api/branches')) return 'Memuat data kategori / cabang di APK Mobile';
            return 'Mengakses fitur APK Mobile (' . $path . ')';
        }

        if (str_contains($p, 'logout')) return 'Keluar (Logout) dari sistem';
        if (str_contains($p, 'generate-qr')) return 'Membuat / memperbarui Barcode QR aset';
        if (str_contains($p, 'barcode-aset')) return 'Membuka halaman Barcode & QR Aset';
        if (str_contains($p, 'pengajuan') && str_contains($p, 'status')) return 'Memverifikasi (ACC / Tolak) pengajuan aset';
        if (str_contains($p, 'pengajuan')) return 'Membuka halaman Pengajuan Aset';
        if (str_contains($p, 'data-aset') && $m === 'POST') return 'Menambah / mengimpor Data Aset';
        if (str_contains($p, 'data-aset') && ($m === 'PUT' || $m === 'PATCH')) return 'Mengubah / mengedit Data Aset';
        if (str_contains($p, 'data-aset') && $m === 'DELETE') return 'Menghapus Data Aset';
        if (str_contains($p, 'data-aset')) return 'Membuka halaman Data Aset';
        if (str_contains($p, 'laporan')) return 'Membuka / mencetak Laporan Aset';
        if (str_contains($p, 'roles')) return 'Mengakses / mengatur Roles & Permission';
        if (str_contains($p, 'kelola-akun')) return 'Mengakses / mengelola Akun Pengguna';
        if (str_contains($p, 'kategori-aset') || str_contains($p, 'kategori')) return 'Mengakses / mengelola Kategori Aset';
        if (str_contains($p, 'cabang')) return 'Mengakses / mengelola Cabang';
        if (str_contains($p, 'notifikasi')) return 'Membuka halaman Notifikasi';
        if (str_contains($p, 'riwayat-aktivitas')) return 'Membuka halaman Riwayat Aktivitas';
        if (str_contains($p, 'dashboard') || $p === '/masteradmin' || $p === '/admin') return 'Membuka Dashboard';

        return ($m === 'GET' ? 'Membuka ' : 'Mengirim aksi ke ') . $path;
    }

    private function recordOnlinePresence(Request $request, string $path): void
    {
        try {
            $presenceFile = storage_path('app/online_presence.json');
            $presence = [];
            if (is_file($presenceFile)) {
                $dec = json_decode((string) @file_get_contents($presenceFile), true);
                if (is_array($dec)) {
                    $presence = $dec;
                }
            }

            $now = time();
            $changed = false;

            if ($request->is('api/*')) {
                $uid = (int) $request->input('user_id', 0);
                $nama = trim((string) $request->input('nama_user', $request->input('pemohon', '')));
                if ($uid <= 0 && $nama !== '' && Schema::hasTable('users')) {
                    $uRow = DB::table('users')->select('users_id')
                        ->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($nama)])
                        ->first();
                    if ($uRow) {
                        $uid = (int) $uRow->users_id;
                    }
                }
                if ($uid > 0) {
                    $presence[(string) $uid] = [
                        'channel'   => 'mobile',
                        'last_seen' => $now,
                        'path'      => $path,
                    ];
                    $changed = true;
                }
            } elseif (Auth::check()) {
                $uid = (int) Auth::id();
                if ($uid > 0) {
                    if ($path === '/logout' || $path === '/dev-console/logout') {
                        if (isset($presence[(string) $uid])) {
                            unset($presence[(string) $uid]);
                            $changed = true;
                        }
                    } else {
                        $presence[(string) $uid] = [
                            'channel'   => 'web',
                            'last_seen' => $now,
                            'path'      => $path,
                        ];
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                @file_put_contents($presenceFile, json_encode($presence, JSON_UNESCAPED_SLASHES), LOCK_EX);
            }
        } catch (\Throwable $e) {
        }
    }

    private function shouldSkipTelemetry(Request $request, string $path): bool
    {
        if (
            $request->has('_poll_dev_rt')
            || $request->has('_poll_dev_act')
            || $request->has('_poll_dev_struktur')
            || $request->has('_poll_pengajuan_rt')
            || $request->has('_check_permissions_rt')
            || $request->has('_live_sync_page')
            || $request->is('_live-sync*')
        ) {
            return true;
        }
        return in_array($path, ['/cek-sesi', '/up'], true);
    }
}
