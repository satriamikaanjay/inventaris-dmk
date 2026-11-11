<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LoginAttemptMiddleware
{
    private const MAX_ATTEMPTS = 3;
    private const MAX_MASTER_ATTEMPTS = 5;
    private const MASTER_LOCKOUT_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('post')) {
            return $next($request);
        }

        if ($request->filled('website_trap')) {
            $this->catatLogKeamananLogin('Memblokir serangan bot otomatis pada form login (Honeypot terdeteksi) dari IP ' . $request->ip());
            return redirect()->route('login')->withErrors([
                'email' => 'Akses ditolak oleh sistem keamanan anti-bot.',
            ]);
        }

        if (is_array($request->input('email')) || is_array($request->input('username')) || is_array($request->input('password'))) {
            $this->catatLogKeamananLogin('Memblokir percobaan Array/Type Injection pada form login dari IP ' . $request->ip());
            return redirect()->route('login')->withErrors([
                'email' => 'Format kredensial login tidak valid.',
            ]);
        }

        $lockFilePath = storage_path('app/admin_login_locks.json');
        $clientKey = $this->ambilClientKey($request);
        $allLocks = $this->bacaSemuaLockData($lockFilePath);
        $clientLock = $this->ambilLockClient($allLocks, $clientKey);

        $inputIdentifier = substr(trim((string) $request->input('email', $request->input('username', ''))), 0, 150);
        $loginAsInput = strtolower(str_replace([' ', '-'], '_', trim((string) $request->input('login_as', ''))));

        if ($this->deteksiInjeksiLogin($inputIdentifier)) {
            $this->catatLogKeamananLogin('Memblokir percobaan SQL Injection / Script Injection pada halaman login (' . substr($inputIdentifier, 0, 60) . ') dari IP ' . $request->ip());
            return redirect()->route('login')->withErrors([
                'email' => 'Sistem Keamanan: Terdeteksi karakter atau perintah injeksi terlarang pada form login.',
            ]);
        }

        $ipFloodKey = 'login_ip_flood|' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipFloodKey, 20)) {
            $sisaDetikFlood = RateLimiter::availableIn($ipFloodKey);
            $menitFlood = max(1, (int) ceil($sisaDetikFlood / 60));
            return redirect()->route('login')->withErrors([
                'email' => "Terlalu banyak percobaan login mencurigakan dari jaringan Anda. Akses ditahan sementara selama {$menitFlood} menit demi keamanan siber.",
            ]);
        }

        $roleAkunDatabase = $this->cekRoleAkunDiDatabase($inputIdentifier);

        $apakahLoginAdmin = ($loginAsInput === 'admin') || ($roleAkunDatabase === 'admin');

        $throttleKey = Str::lower($inputIdentifier) . '|' . $request->ip();
        RateLimiter::clear($throttleKey);

        if (!$apakahLoginAdmin) {
            $masterRateKey = 'master_login_guard|' . $clientKey . '|' . $request->ip();
            if (RateLimiter::tooManyAttempts($masterRateKey, self::MAX_MASTER_ATTEMPTS)) {
                $sisaDetikMaster = RateLimiter::availableIn($masterRateKey);
                $menitMaster = max(1, (int) ceil($sisaDetikMaster / 60));
                return redirect()->route('login')
                    ->withInput($request->only('email', 'username', 'login_as', 'remember', 'client_lock_id'))
                    ->withErrors([
                        'email' => "Demi keamanan dari serangan Brute Force, akses login Master Admin dikunci sementara selama {$menitMaster} menit (5/5 gagal berturut-turut). Silakan tunggu atau hubungi pihak Developer untuk peresetan password.",
                    ]);
            }

            $response = $next($request);

            if (Auth::check()) {
                RateLimiter::clear($masterRateKey);
                RateLimiter::clear($ipFloodKey);
                return $response;
            }

            RateLimiter::hit($masterRateKey, self::MASTER_LOCKOUT_SECONDS);
            RateLimiter::hit($ipFloodKey, 600);
            $gagalMaster = RateLimiter::attempts($masterRateKey);
            $sisaMaster = max(0, self::MAX_MASTER_ATTEMPTS - $gagalMaster);

            if ($gagalMaster >= self::MAX_MASTER_ATTEMPTS) {
                $this->catatLogKeamananLogin('Peringatan Keamanan: Akses login Master Admin dikunci sementara (15 menit) setelah 5 kali gagal berturut-turut (' . $inputIdentifier . ').');
                $pesanMaster = 'Demi keamanan dari serangan Brute Force, akses login Master Admin dikunci sementara selama 15 menit (5/5 gagal berturut-turut). Silakan hubungi pihak Developer untuk peresetan password.';
            } else {
                $pesanMaster = "Username/email atau password Master Admin salah ({$gagalMaster}/" . self::MAX_MASTER_ATTEMPTS . "). Sisa kesempatan: {$sisaMaster} kali lagi sebelum dikunci sementara.";
            }

            return redirect()->route('login')
                ->withInput($request->only('email', 'username', 'login_as', 'remember', 'client_lock_id'))
                ->withErrors([
                    'email' => $pesanMaster,
                ]);
        }

        $request->merge(['login_as' => 'admin']);

        $sudahTerblokir = !empty($clientLock['is_blocked']) || ((int) ($clientLock['failed_count'] ?? 0)) >= self::MAX_ATTEMPTS;

        if ($sudahTerblokir) {
            session()->flash('login_attempt_recorded', true);
            session()->flash('login_client_lock_state', $clientLock);

            return redirect()->route('login')
                ->withInput($request->only('email', 'username', 'login_as', 'remember', 'client_lock_id'))
                ->withErrors([
                    'email' => 'Akses login Admin terblokir karena 3 kali berturut-turut salah memasukkan username/email atau password. Silakan hubungi Master Admin untuk peresetan password/username.',
                ]);
        }

        $response = $next($request);

        if (Auth::check()) {
            $userLogin = Auth::user();
            $roleUserLogin = strtolower(str_replace([' ', '-'], '_', trim((string) ($userLogin->role ?? ''))));

            if ($roleUserLogin === 'admin') {
                $this->resetLockClient($lockFilePath, $clientKey);
            }
            RateLimiter::clear($ipFloodKey);

            return $response;
        }

        RateLimiter::hit($ipFloodKey, 600);

        $allLocks = $this->bacaSemuaLockData($lockFilePath);
        $clientLock = $this->ambilLockClient($allLocks, $clientKey);
        $clientLock['failed_count'] = ((int) ($clientLock['failed_count'] ?? 0)) + 1;
        if ($inputIdentifier !== '') {
            $clientLock['last_email'] = strip_tags($inputIdentifier);
        }

        $baruTerblokir = false;
        if ($clientLock['failed_count'] >= self::MAX_ATTEMPTS) {
            if (empty($clientLock['is_blocked'])) {
                $baruTerblokir = true;
            }
            $clientLock['is_blocked'] = true;
            $clientLock['blocked_at'] = now()->format('d M Y, H:i:s');
        }
        $clientLock['updated_at'] = round(microtime(true) * 1000);

        $this->simpanLockClient($lockFilePath, $allLocks, $clientKey, $clientLock);
        session()->flash('login_attempt_recorded', true);
        session()->flash('login_client_lock_state', $clientLock);

        if ($baruTerblokir) {
            $this->catatLogBlokirAdmin($inputIdentifier);
        }

        $jumlahGagal = (int) $clientLock['failed_count'];
        $sisaKesempatan = max(0, self::MAX_ATTEMPTS - $jumlahGagal);

        if ($clientLock['is_blocked'] || $sisaKesempatan <= 0) {
            $pesanError = 'Akses login Admin telah terblokir (3/3 gagal berturut-turut). Silakan hubungi Master Admin untuk peresetan password/username.';
        } else {
            $pesanError = "Username/email atau password Admin salah ({$jumlahGagal}/" . self::MAX_ATTEMPTS . "). Sisa kesempatan: {$sisaKesempatan} kali lagi sebelum terblokir.";
        }

        return redirect()->route('login')
            ->withInput($request->only('email', 'username', 'login_as', 'remember', 'client_lock_id'))
            ->withErrors([
                'email' => $pesanError,
            ]);
    }

    private function deteksiInjeksiLogin(string $input): bool
    {
        if ($input === '') {
            return false;
        }

        $patterns = [
            '/(?:\'|\")\s*(?:or|and)\s*(?:\'|\")?\d+(?:\'|\")?\s*=\s*(?:\'|\")?\d+/i',
            '/\bunion\s+(?:all\s+)?select\b/i',
            '/--\s*$/',
            '/\/\*.*\*\//',
            '/;\s*(?:drop|delete|update|insert|alter|create|truncate)\b/i',
            '/<\s*script\b/i',
            '/javascript\s*:/i',
            '/\x00/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function ambilClientKey(Request $request): string
    {
        $raw = trim((string) ($request->input('client_lock_id') ?: $request->cookie('admin_login_client_id') ?: ''));
        if ($raw !== '') {
            $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $raw);
            if ($clean !== '') {
                return substr($clean, 0, 80);
            }
        }

        try {
            if ($request->hasSession()) {
                $sid = $request->session()->getId();
                if (!empty($sid)) {
                    return 'sess_' . $sid;
                }
            }
        } catch (\Throwable $e) {
        }

        return 'ip_' . md5((string) $request->ip() . '|' . (string) $request->userAgent());
    }

    private function cekRoleAkunDiDatabase(string $identifier): ?string
    {
        if ($identifier === '') {
            return null;
        }

        try {
            if (!Schema::hasTable('users')) {
                return null;
            }

            $query = DB::table('users')->where('email', $identifier);
            if (Schema::hasColumn('users', 'nama_lengkap')) {
                $query->orWhere('nama_lengkap', $identifier);
            } elseif (Schema::hasColumn('users', 'name')) {
                $query->orWhere('name', $identifier);
            }

            $userRow = $query->first();
            if ($userRow && isset($userRow->role)) {
                $norm = strtolower(str_replace([' ', '-'], '_', trim((string) $userRow->role)));
                return $norm === 'masteradmin' ? 'master_admin' : $norm;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    private function bacaSemuaLockData(string $path): array
    {
        $default = [
            'failed_count' => 0,
            'is_blocked'   => false,
            'last_email'   => '',
            'blocked_at'   => null,
            'updated_at'   => 0,
            'clients'      => [],
        ];

        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $merged = array_merge($default, $decoded);
                if (!isset($merged['clients']) || !is_array($merged['clients'])) {
                    $merged['clients'] = [];
                }
                return $merged;
            }
        }

        return $default;
    }

    private function ambilLockClient(array $allLocks, string $clientKey): array
    {
        $defaultClient = [
            'failed_count' => 0,
            'is_blocked'   => false,
            'last_email'   => '',
            'blocked_at'   => null,
            'updated_at'   => 0,
        ];

        if (isset($allLocks['clients'][$clientKey]) && is_array($allLocks['clients'][$clientKey])) {
            return array_merge($defaultClient, $allLocks['clients'][$clientKey]);
        }

        return $defaultClient;
    }

    private function simpanLockClient(string $path, array $allLocks, string $clientKey, array $clientLock): void
    {
        if (!isset($allLocks['clients']) || !is_array($allLocks['clients'])) {
            $allLocks['clients'] = [];
        }
        $allLocks['clients'][$clientKey] = $clientLock;
        $this->sinkronkanRingkasanGlobal($allLocks);

        try {
            file_put_contents($path, json_encode($allLocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        } catch (\Throwable $e) {
        }
    }

    private function resetLockClient(string $path, string $clientKey): void
    {
        $allLocks = $this->bacaSemuaLockData($path);
        if (isset($allLocks['clients'][$clientKey])) {
            unset($allLocks['clients'][$clientKey]);
        }
        $this->sinkronkanRingkasanGlobal($allLocks);

        try {
            file_put_contents($path, json_encode($allLocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        } catch (\Throwable $e) {
        }
    }

    private function sinkronkanRingkasanGlobal(array &$allLocks): void
    {
        $anyBlocked = false;
        $maxFailed = 0;
        $lastEmail = '';
        $blockedAt = null;

        foreach ($allLocks['clients'] ?? [] as $cData) {
            if (!is_array($cData)) {
                continue;
            }
            $fc = (int) ($cData['failed_count'] ?? 0);
            if ($fc > $maxFailed) {
                $maxFailed = $fc;
            }
            if (!empty($cData['is_blocked']) || $fc >= self::MAX_ATTEMPTS) {
                $anyBlocked = true;
                if (!empty($cData['last_email'])) {
                    $lastEmail = (string) $cData['last_email'];
                }
                if (!empty($cData['blocked_at'])) {
                    $blockedAt = (string) $cData['blocked_at'];
                }
            } elseif ($lastEmail === '' && !empty($cData['last_email'])) {
                $lastEmail = (string) $cData['last_email'];
            }
        }

        $allLocks['failed_count'] = $maxFailed;
        $allLocks['is_blocked'] = $anyBlocked;
        $allLocks['last_email'] = $lastEmail;
        $allLocks['blocked_at'] = $anyBlocked ? $blockedAt : null;
        $allLocks['updated_at'] = round(microtime(true) * 1000);
    }

    private function catatLogBlokirAdmin(string $identifier): void
    {
        $infoAkun = $identifier !== '' ? (' (' . strip_tags($identifier) . ')') : '';
        $this->catatLogKeamananLogin('Peringatan Keamanan: Akses login Admin terblokir setelah 3 kali berturut-turut salah memasukkan username/password' . $infoAkun . '.');
    }

    private function catatLogKeamananLogin(string $pesan): void
    {
        if (!class_exists(\App\Models\RiwayatAktivitas::class)) {
            return;
        }

        try {
            $riwayat = new \App\Models\RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            $dataLog = [];

            if (Schema::hasColumn($tbl, 'user_id')) {
                $dataLog['user_id'] = null;
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $dataLog['aktivitas'] = substr($pesan, 0, 240);
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $dataLog['modul'] = 'Autentikasi Sistem';
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $dataLog['status'] = 'Peringatan';
            }

            $riwayat->forceFill($dataLog)->save();
        } catch (\Throwable $e) {
        }
    }
}
