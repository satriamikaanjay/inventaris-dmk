<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SecurityShieldMiddleware;
use App\Models\RiwayatAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class DeveloperController extends Controller
{
    private function ensureDeveloperAccount(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }
            $exists = User::whereRaw('LOWER(TRIM(role)) = ?', ['developer'])->exists();
            if (!$exists) {
                $userData = [
                    'nama_lengkap' => 'developer',
                    'email'        => 'developer@inventaris-dmk.local',
                    'password'     => Hash::make(\Illuminate\Support\Str::random(24)),
                    'role'         => 'developer',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                if (Schema::hasColumn('users', 'username')) {
                    $userData['username'] = 'developer';
                }

                if (Schema::hasColumn('users', 'role_id') && Schema::hasTable('roles')) {
                    $roleRow = DB::table('roles')->whereRaw('LOWER(TRIM(nama_role)) = ?', ['developer'])->first();
                    if (!$roleRow) {
                        $roleId = DB::table('roles')->insertGetId([
                            'nama_role'  => 'developer',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $roleId = $roleRow->id;
                    }
                    $userData['role_id'] = $roleId;
                }

                DB::table('users')->insert($userData);
            }
        } catch (\Throwable $e) {
            Log::error('ensureDeveloperAccount error: ' . $e->getMessage());
        }
    }

    public function showLogin()
    {
        $this->ensureDeveloperAccount();

        if (Auth::check()) {
            $role = strtolower(str_replace([' ', '-'], '_', trim((string) (Auth::user()->role ?? ''))));
            if ($role === 'developer' && session('dev_portal_verified') === true) {
                return redirect()->route('developer.dashboard');
            }
        }

        return view('developer.login');
    }

    public function login(Request $request)
    {
        $this->ensureDeveloperAccount();

        $rateKey = 'dev_portal_login|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $waitSec = RateLimiter::availableIn($rateKey);
            $waitMin = max(1, (int) ceil($waitSec / 60));
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan gagal ke Developer Console. Akses ditahan selama {$waitMin} menit.",
            ]);
        }

        $request->validate([
            'username'   => ['required', 'string', 'max:120'],
            'password'   => ['required', 'string', 'max:120'],
            'access_key' => ['required', 'string', 'max:150'],
        ], [
            'username.required'   => 'Username Developer wajib diisi.',
            'password.required'   => 'Password Developer wajib diisi.',
            'access_key.required' => 'Developer Access Key wajib diisi.',
        ]);

        $turnstileSecret = trim((string) config('services.turnstile.secret_key', ''));
        if ($turnstileSecret !== '') {
            $token = trim((string) $request->input('cf-turnstile-response', ''));
            if ($token === '') {
                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Selesaikan verifikasi keamanan Cloudflare terlebih dahulu.',
                ]);
            }
            try {
                $httpOpts = [];
                $iniCa = trim((string) ini_get('curl.cainfo'));
                if ($iniCa === '' || !is_file($iniCa)) {
                    $laragonCa = 'C:\\laragon\\etc\\ssl\\cacert.pem';
                    $httpOpts['verify'] = is_file($laragonCa) ? $laragonCa : false;
                }
                $verify = Http::withOptions($httpOpts)->asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => $turnstileSecret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
                if (!$verify->successful() || !($verify->json('success') ?? false)) {
                    return back()->withInput($request->only('username'))->withErrors([
                        'username' => 'Verifikasi Cloudflare gagal atau kedaluwarsa. Silakan ulangi.',
                    ]);
                }
            } catch (\Throwable $e) {
                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Gagal menghubungi server verifikasi Cloudflare.',
                ]);
            }
        }

        $expectedKey = (string) config('services.developer.access_key', 'DMK-DEV-2026-KEY');
        $givenKey = trim((string) $request->input('access_key', ''));
        if (!hash_equals($expectedKey, $givenKey)) {
            RateLimiter::hit($rateKey, 600);
            return back()->withInput($request->only('username'))->withErrors([
                'access_key' => 'Developer Access Key tidak valid.',
            ]);
        }

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $hasUsernameCol = Schema::hasColumn('users', 'username');
        $userQuery = User::where(function ($q) use ($username, $hasUsernameCol) {
            $q->where('nama_lengkap', $username)
                ->orWhere('email', $username);
            if ($hasUsernameCol) {
                $q->orWhere('username', $username);
            }
        });
        $matchedUser = $userQuery->first();

        $ok = false;
        if ($matchedUser) {
            $storedHash = (string) ($matchedUser->password ?? '');
            $isBcrypt = str_starts_with($storedHash, '$2y$')
                || str_starts_with($storedHash, '$2a$')
                || str_starts_with($storedHash, '$2b$');

            if ($isBcrypt) {
                $ok = Hash::check($password, $storedHash);
            } else {
                if (hash_equals($storedHash, $password) || (strlen($storedHash) === 32 && ctype_xdigit($storedHash) && hash_equals(strtolower($storedHash), md5($password)))) {
                    $ok = true;
                    DB::table('users')
                        ->where('users_id', $matchedUser->users_id)
                        ->update([
                            'password'   => Hash::make($password),
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        if (!$ok) {
            RateLimiter::hit($rateKey, 600);
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Username atau password Developer salah.',
            ]);
        }

        Auth::login($matchedUser);
        $request->session()->regenerate(true);
        $user = Auth::user();
        $role = strtolower(str_replace([' ', '-'], '_', trim((string) ($user->role ?? ''))));
        if ($role !== 'developer') {
            Auth::logout();
            RateLimiter::hit($rateKey, 600);
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Akses ditolak. Halaman ini khusus untuk role Developer.',
            ]);
        }

        RateLimiter::clear($rateKey);
        $request->session()->regenerate(true);
        $request->session()->put('dev_portal_verified', true);
        $request->session()->put('dev_ua_hash', hash('sha256', (string) $request->userAgent()));

        return redirect()->route('developer.dashboard');
    }

    private function hasValidDeveloperSession(Request $request): bool
    {
        if (session('dev_portal_verified') !== true || !Auth::check()) {
            return false;
        }
        $expectedUa = (string) session('dev_ua_hash', '');
        $currentUa = hash('sha256', (string) $request->userAgent());
        if ($expectedUa !== '' && !hash_equals($expectedUa, $currentUa)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return false;
        }
        return true;
    }

    public function logout(Request $request)
    {
        $request->session()->forget('dev_portal_verified');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('developer.login');
    }

    public function index(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax() || $request->has('_poll_dev_rt')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('developer.login')->withErrors([
                'access_key' => 'Sesi Developer Console belum terverifikasi. Silakan login melalui portal Developer.',
            ]);
        }

        $snapshot = $this->collectSystemSnapshot();

        if ($request->has('_poll_dev_rt')) {
            return response()->json([
                'success' => true,
                'data'    => $snapshot,
            ]);
        }

        return view('developer.dashboard', $snapshot);
    }

    public function aktivitasAkun(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax() || $request->has('_poll_dev_act')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('developer.login')->withErrors([
                'access_key' => 'Sesi Developer Console belum terverifikasi. Silakan login melalui portal Developer.',
            ]);
        }

        $data = $this->collectAktivitasTigaRole();

        if ($request->has('_poll_dev_act')) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('developer.aktivitas', $data);
    }

    private function collectStrukturSnapshot(): array
    {
        $integrity = $this->computeIntegrityState();
        $rootAbs = realpath(base_path());
        $rootListing = $rootAbs
            ? $this->listDirectoryItems($rootAbs, '', $integrity['anomalyMap'], $integrity['dirAnomalyMap'])
            : ['dirs' => [], 'files' => []];

        $webLockInfo = ['locked' => false, 'locked_at' => null, 'locked_by' => null];
        $lockFile = storage_path('app/web_emergency_lock.json');
        if (is_file($lockFile)) {
            $decLock = json_decode((string) @file_get_contents($lockFile), true);
            if (is_array($decLock)) {
                $webLockInfo = array_merge($webLockInfo, $decLock);
            }
        }

        return [
            'rootDirs'        => $rootListing['dirs'],
            'rootFiles'       => $rootListing['files'],
            'anomalies'       => $integrity['anomalies'],
            'baselineCreated' => $integrity['baselineCreated'],
            'baselineTotal'   => $integrity['baselineTotal'],
            'webLocked'       => !empty($webLockInfo['locked']),
            'webLockInfo'     => $webLockInfo,
        ];
    }

    public function strukturFolder(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax() || $request->has('_poll_dev_struktur')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            return redirect()->route('developer.login')->withErrors([
                'access_key' => 'Sesi Developer Console belum terverifikasi. Silakan login melalui portal Developer.',
            ]);
        }

        $snapshot = $this->collectStrukturSnapshot();

        if ($request->has('_poll_dev_struktur')) {
            return response()->json([
                'success' => true,
                'data'    => $snapshot,
            ]);
        }

        return view('developer.struktur', $snapshot);
    }

    public function strukturTree(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $dirParam = (string) $request->query('dir', '');
        $absDir = $this->resolveSafeProjectPath($dirParam);
        if ($absDir === null || !is_dir($absDir)) {
            return response()->json(['success' => false, 'message' => 'Folder tidak ditemukan'], 404);
        }

        $relDir = $this->toRelativeProjectPath($absDir);
        $integrity = $this->computeIntegrityState();
        $listing = $this->listDirectoryItems($absDir, $relDir, $integrity['anomalyMap'], $integrity['dirAnomalyMap']);

        return response()->json([
            'success' => true,
            'dir'     => $relDir,
            'dirs'    => $listing['dirs'],
            'files'   => $listing['files'],
        ]);
    }

    public function strukturFile(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $fileParam = (string) $request->query('file', '');
        $absFile = $this->resolveSafeProjectPath($fileParam);
        if ($absFile === null || !is_file($absFile)) {
            return response()->json(['success' => false, 'message' => 'File tidak ditemukan'], 404);
        }

        $integrity = $this->computeIntegrityState();
        $details = $this->readSafeFileDetails($absFile, $integrity['anomalyMap'], $integrity['baseHashMap']);

        return response()->json([
            'success' => true,
            'file'    => $details,
        ]);
    }

    private function computeIntegrityState(): array
    {
        $baselineFile = storage_path('app/dev_file_baseline.json');
        $baselineData = null;
        if (is_file($baselineFile)) {
            $decoded = json_decode((string) @file_get_contents($baselineFile), true);
            if (is_array($decoded) && isset($decoded['files']) && is_array($decoded['files'])) {
                $baselineData = $decoded;
            }
        }

        if ($baselineData === null) {
            $baselineData = $this->buildBaselineSnapshot();
            @file_put_contents($baselineFile, json_encode($baselineData, JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        $scan = $this->scanProjectForIntegrity();
        $currentFiles = $scan['files'];
        $currentDirs = $scan['dirs'];
        $baselineFiles = $baselineData['files'] ?? [];
        $baselineDirs = array_flip($baselineData['dirs'] ?? []);

        $anomalies = [];
        $anomalyMap = [];
        $dirAnomalyMap = [];
        $baseHashMap = [];

        if (!empty($baselineDirs)) {
            foreach ($currentDirs as $relDir => $dMeta) {
                if (!isset($baselineDirs[$relDir])) {
                    $anomalies[] = [
                        'type'   => 'FOLDER',
                        'path'   => $relDir,
                        'status' => 'BARU',
                        'size'   => 0,
                        'mtime'  => date('d/m/Y H:i:s', $dMeta['mtime']),
                    ];
                    $dirAnomalyMap[$relDir] = 'BARU';
                }
            }
        }

        foreach ($currentFiles as $rel => $meta) {
            if (!isset($baselineFiles[$rel])) {
                $anomalies[] = [
                    'type'   => 'FILE',
                    'path'   => $rel,
                    'status' => 'BARU',
                    'size'   => $meta['size'],
                    'mtime'  => date('d/m/Y H:i:s', $meta['mtime']),
                ];
                $anomalyMap[$rel] = 'BARU';
            } else {
                $baseMeta = $baselineFiles[$rel];
                if ((int) ($baseMeta['size'] ?? -1) !== (int) $meta['size'] || (int) ($baseMeta['mtime'] ?? 0) !== (int) $meta['mtime']) {
                    $currHash = @sha1_file(base_path($rel)) ?: '';
                    if ($currHash !== '' && $currHash !== ($baseMeta['hash'] ?? '')) {
                        $anomalies[] = [
                            'type'   => 'FILE',
                            'path'   => $rel,
                            'status' => 'DIUBAH',
                            'size'   => $meta['size'],
                            'mtime'  => date('d/m/Y H:i:s', $meta['mtime']),
                        ];
                        $anomalyMap[$rel] = 'DIUBAH';
                        $baseHashMap[$rel] = (string) ($baseMeta['hash'] ?? '');
                    }
                }
            }
        }

        foreach ($baselineFiles as $rel => $baseMeta) {
            if (!isset($currentFiles[$rel])) {
                $anomalies[] = [
                    'type'   => 'FILE',
                    'path'   => $rel,
                    'status' => 'DIHAPUS',
                    'size'   => (int) ($baseMeta['size'] ?? 0),
                    'mtime'  => '-',
                ];
                $anomalyMap[$rel] = 'DIHAPUS';
            }
        }

        return [
            'anomalies'       => $anomalies,
            'anomalyMap'      => $anomalyMap,
            'dirAnomalyMap'   => $dirAnomalyMap,
            'baseHashMap'     => $baseHashMap,
            'baselineCreated' => $baselineData['created_at'] ?? '-',
            'baselineTotal'   => (int) ($baselineData['total_files'] ?? count($baselineFiles)),
        ];
    }

    private function listDirectoryItems(string $absDir, string $relDir, array $anomalyMap, array $dirAnomalyMap = []): array
    {
        $dirs = [];
        $files = [];
        $rawItems = @scandir($absDir);
        if (is_array($rawItems)) {
            foreach ($rawItems as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $absDir . DIRECTORY_SEPARATOR . $entry;
                $rel = $relDir === '' ? $entry : ($relDir . '/' . $entry);
                if (is_dir($full)) {
                    $isNewDir = ($dirAnomalyMap[$rel] ?? null) === 'BARU';
                    $hasAnomaly = $isNewDir;
                    $prefix = $rel . '/';
                    if (!$hasAnomaly) {
                        foreach ($anomalyMap as $anomPath => $anomStatus) {
                            if (str_starts_with($anomPath, $prefix) && $anomStatus !== 'DIHAPUS') {
                                $hasAnomaly = true;
                                break;
                            }
                        }
                    }
                    if (!$hasAnomaly) {
                        foreach ($dirAnomalyMap as $dPath => $dStatus) {
                            if (str_starts_with($dPath, $prefix)) {
                                $hasAnomaly = true;
                                break;
                            }
                        }
                    }
                    $dirs[] = [
                        'name'        => $entry,
                        'path'        => $rel,
                        'is_new'      => $isNewDir,
                        'has_anomaly' => $hasAnomaly,
                        'mtime'       => date('d/m/Y H:i:s', (int) @filemtime($full)),
                    ];
                } elseif (is_file($full)) {
                    $files[] = [
                        'name'    => $entry,
                        'path'    => $rel,
                        'size'    => (int) @filesize($full),
                        'mtime'   => date('d/m/Y H:i:s', (int) @filemtime($full)),
                        'anomaly' => $anomalyMap[$rel] ?? null,
                    ];
                }
            }
        }

        usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['dirs' => $dirs, 'files' => $files];
    }

    private function readSafeFileDetails(string $absFile, array $anomalyMap, array $baseHashMap = []): array
    {
        $relFile = $this->toRelativeProjectPath($absFile);
        $size = (int) @filesize($absFile);
        $ext = strtolower(pathinfo($absFile, PATHINFO_EXTENSION));
        $binaryExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'pdf', 'zip', 'rar', '7z', 'exe', 'dll', 'woff', 'woff2', 'ttf', 'eot', 'mp3', 'mp4', 'apk'];

        $content = null;
        $notice = null;
        $changedLines = [];
        $anomaly = $anomalyMap[$relFile] ?? null;

        if (in_array($ext, $binaryExts, true)) {
            $notice = 'File biner/media (' . strtoupper($ext) . ') tidak dapat ditampilkan sebagai teks.';
        } elseif ($size > 524288) {
            $notice = 'Ukuran file melebihi 512 KB (' . number_format($size / 1024, 1) . ' KB), konten tidak dimuat penuh.';
        } else {
            $rawContent = (string) @file_get_contents($absFile);
            if (str_contains($rawContent, "\0")) {
                $notice = 'File terdeteksi berisi data biner dan tidak dapat ditampilkan sebagai teks.';
            } else {
                $content = $rawContent;
                if ($anomaly === 'DIUBAH' && !empty($baseHashMap[$relFile])) {
                    $oldContent = $this->loadBaselineFileContent($baseHashMap[$relFile]);
                    if ($oldContent !== null) {
                        $changedLines = $this->computeChangedLineNumbers($oldContent, $rawContent);
                    }
                }
            }
        }

        return [
            'path'          => $relFile,
            'size'          => $size,
            'mtime'         => date('d/m/Y H:i:s', (int) @filemtime($absFile)),
            'sha1'          => @sha1_file($absFile) ?: '-',
            'anomaly'       => $anomaly,
            'changed_lines' => $changedLines,
            'content'       => $content,
            'notice'        => $notice,
        ];
    }

    public function simpanBaselineFolder(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sesi Developer tidak valid.'], 401);
            }
            return redirect()->route('developer.login')->withErrors([
                'access_key' => 'Sesi Developer Console belum terverifikasi.',
            ]);
        }

        $baselineData = $this->buildBaselineSnapshot();
        @file_put_contents(storage_path('app/dev_file_baseline.json'), json_encode($baselineData, JSON_UNESCAPED_SLASHES), LOCK_EX);
        $msg = 'Baseline struktur file berhasil diperbarui (' . $baselineData['total_files'] . ' file tercatat).';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $this->collectStrukturSnapshot(),
            ]);
        }

        return redirect()->route('developer.struktur')->with('success', $msg);
    }

    public function toggleKunciWeb(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sesi Developer tidak valid.'], 401);
            }
            return redirect()->route('developer.login')->withErrors([
                'access_key' => 'Sesi Developer Console belum terverifikasi.',
            ]);
        }

        $lockFile = storage_path('app/web_emergency_lock.json');
        $currentlyLocked = false;
        if (is_file($lockFile)) {
            $decoded = json_decode((string) @file_get_contents($lockFile), true);
            if (is_array($decoded) && !empty($decoded['locked'])) {
                $currentlyLocked = true;
            }
        }

        $newLocked = !$currentlyLocked;
        $payload = [
            'locked'    => $newLocked,
            'locked_at' => $newLocked ? date('d/m/Y H:i:s') : null,
            'locked_by' => (string) (Auth::user()->nama_lengkap ?? 'developer'),
        ];
        @file_put_contents($lockFile, json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX);

        $msg = $newLocked
            ? 'Web berhasil DIKUNCI (Mode Maintenance Darurat aktif). Semua pengguna melihat halaman Maintenance.'
            : 'Kunci Web berhasil DIBUKA. Seluruh akses web kembali normal.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => $msg,
                'webLocked'   => $newLocked,
                'webLockInfo' => $payload,
            ]);
        }

        return redirect()->route('developer.struktur')->with('success', $msg);
    }

    private function resolveSafeProjectPath(string $relativePath): ?string
    {
        $baseReal = realpath(base_path());
        if ($baseReal === false) {
            return null;
        }
        $clean = trim(str_replace(['\\', "\0"], ['/', ''], $relativePath), '/');
        if ($clean === '') {
            return $baseReal;
        }
        $candidate = $baseReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
        $real = realpath($candidate);
        if ($real === false) {
            return null;
        }
        if ($real !== $baseReal && !str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $real;
    }

    private function toRelativeProjectPath(string $absPath): string
    {
        $baseReal = realpath(base_path());
        if ($baseReal === false) {
            return '';
        }
        if ($absPath === $baseReal) {
            return '';
        }
        $prefix = $baseReal . DIRECTORY_SEPARATOR;
        if (str_starts_with($absPath, $prefix)) {
            return str_replace('\\', '/', substr($absPath, strlen($prefix)));
        }
        return '';
    }

    private function scanProjectForIntegrity(): array
    {
        $baseReal = realpath(base_path());
        if ($baseReal === false) {
            return ['files' => [], 'dirs' => []];
        }

        $skipDirs = ['vendor', 'node_modules', '.git', 'storage/framework', 'storage/logs', 'storage/app/dev_baseline_contents'];
        $skipFiles = [
            'storage/app/dev_file_baseline.json',
            'storage/app/system_telemetry.json',
            'storage/app/dev_activity_stream.json',
            'storage/app/online_presence.json',
            'storage/app/master_login_locks.json',
            'storage/app/master_reset_requests.json',
            'storage/app/web_emergency_lock.json',
            'storage/app/sec_blocked_ips.json',
        ];

        $files = [];
        $dirs = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($baseReal, \FilesystemIterator::SKIP_DOTS),
                function ($current, $key, $iter) use ($baseReal, $skipDirs) {
                    $rel = str_replace('\\', '/', substr($current->getPathname(), strlen($baseReal) + 1));
                    if ($current->isDir()) {
                        foreach ($skipDirs as $skip) {
                            if ($rel === $skip || str_starts_with($rel, $skip . '/')) {
                                return false;
                            }
                        }
                    }
                    return true;
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $itemInfo) {
            $rel = str_replace('\\', '/', substr($itemInfo->getPathname(), strlen($baseReal) + 1));
            if ($itemInfo->isDir()) {
                $dirs[$rel] = [
                    'mtime' => (int) $itemInfo->getMTime(),
                ];
                continue;
            }
            if (!$itemInfo->isFile()) {
                continue;
            }
            if (in_array($rel, $skipFiles, true)) {
                continue;
            }
            $files[$rel] = [
                'size'  => (int) $itemInfo->getSize(),
                'mtime' => (int) $itemInfo->getMTime(),
            ];
        }

        ksort($files);
        ksort($dirs);
        return ['files' => $files, 'dirs' => $dirs];
    }

    private function buildBaselineSnapshot(): array
    {
        $scan = $this->scanProjectForIntegrity();
        $snapshotFiles = [];
        $contentDir = storage_path('app/dev_baseline_contents');
        if (!is_dir($contentDir)) {
            @mkdir($contentDir, 0755, true);
        }

        $binaryExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'pdf', 'zip', 'rar', '7z', 'exe', 'dll', 'woff', 'woff2', 'ttf', 'eot', 'mp3', 'mp4', 'apk'];

        foreach ($scan['files'] as $rel => $meta) {
            $abs = base_path($rel);
            $hash = @sha1_file($abs) ?: '';
            $snapshotFiles[$rel] = [
                'size'  => $meta['size'],
                'mtime' => $meta['mtime'],
                'hash'  => $hash,
            ];

            if ($hash !== '' && $meta['size'] <= 524288) {
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                if (!in_array($ext, $binaryExts, true)) {
                    $snapFile = $contentDir . DIRECTORY_SEPARATOR . $hash . '.dat';
                    if (!is_file($snapFile)) {
                        $raw = @file_get_contents($abs);
                        if (is_string($raw) && !str_contains($raw, "\0")) {
                            $compressed = function_exists('gzdeflate') ? @gzdeflate($raw, 6) : $raw;
                            if (is_string($compressed)) {
                                @file_put_contents($snapFile, $compressed, LOCK_EX);
                            }
                        }
                    }
                }
            }
        }

        return [
            'created_at'  => date('d/m/Y H:i:s'),
            'total_files' => count($snapshotFiles),
            'dirs'        => array_keys($scan['dirs']),
            'files'       => $snapshotFiles,
        ];
    }

    private function loadBaselineFileContent(string $hash): ?string
    {
        $cleanHash = preg_replace('/[^a-f0-9]/i', '', $hash) ?? '';
        if ($cleanHash === '') {
            return null;
        }
        $snapFile = storage_path('app/dev_baseline_contents/' . $cleanHash . '.dat');
        if (!is_file($snapFile)) {
            return null;
        }
        $raw = @file_get_contents($snapFile);
        if (!is_string($raw)) {
            return null;
        }
        if (function_exists('gzinflate')) {
            $inflated = @gzinflate($raw);
            if (is_string($inflated)) {
                return $inflated;
            }
        }
        return $raw;
    }

    private function computeChangedLineNumbers(string $oldContent, string $newContent): array
    {
        $oldLines = preg_split("/\r\n|\n|\r/", $oldContent) ?: [];
        $newLines = preg_split("/\r\n|\n|\r/", $newContent) ?: [];
        $n = count($oldLines);
        $m = count($newLines);

        if ($n === 0 && $m === 0) {
            return [];
        }

        if ($n * $m > 2500000) {
            $oldCounts = array_count_values($oldLines);
            $changed = [];
            foreach ($newLines as $idx => $line) {
                if (!isset($oldCounts[$line]) || $oldCounts[$line] <= 0) {
                    $changed[] = $idx + 1;
                } else {
                    $oldCounts[$line]--;
                }
            }
            return $changed;
        }

        $start = 0;
        while ($start < $n && $start < $m && $oldLines[$start] === $newLines[$start]) {
            $start++;
        }

        $oldEnd = $n - 1;
        $newEnd = $m - 1;
        while ($oldEnd >= $start && $newEnd >= $start && $oldLines[$oldEnd] === $newLines[$newEnd]) {
            $oldEnd--;
            $newEnd--;
        }

        $subOld = array_slice($oldLines, $start, max(0, $oldEnd - $start + 1));
        $subNew = array_slice($newLines, $start, max(0, $newEnd - $start + 1));
        $sn = count($subOld);
        $sm = count($subNew);

        if ($sn === 0) {
            $changed = [];
            for ($j = 0; $j < $sm; $j++) {
                $changed[] = $start + $j + 1;
            }
            return $changed;
        }

        if ($sm === 0) {
            return [];
        }

        if ($sn * $sm > 1500000) {
            $changed = [];
            for ($j = 0; $j < $sm; $j++) {
                $changed[] = $start + $j + 1;
            }
            return $changed;
        }

        $dp = array_fill(0, $sn + 1, array_fill(0, $sm + 1, 0));
        for ($i = $sn - 1; $i >= 0; $i--) {
            for ($j = $sm - 1; $j >= 0; $j--) {
                if ($subOld[$i] === $subNew[$j]) {
                    $dp[$i][$j] = $dp[$i + 1][$j + 1] + 1;
                } else {
                    $dp[$i][$j] = max($dp[$i + 1][$j], $dp[$i][$j + 1]);
                }
            }
        }

        $changed = [];
        $i = 0;
        $j = 0;
        while ($i < $sn && $j < $sm) {
            if ($subOld[$i] === $subNew[$j]) {
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $i++;
            } else {
                $changed[] = $start + $j + 1;
                $j++;
            }
        }
        while ($j < $sm) {
            $changed[] = $start + $j + 1;
            $j++;
        }

        return $changed;
    }

    private function ringkasAktivitas(string $teks): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($teks)) ?? '');
        return mb_strimwidth($clean, 0, 95, '...');
    }

    private function collectAktivitasTigaRole(): array
    {
        $masterLogs = [];
        $adminLogs = [];
        $userLogs = [];

        $streamFile = storage_path('app/dev_activity_stream.json');
        if (is_file($streamFile)) {
            $decStream = json_decode((string) @file_get_contents($streamFile), true);
            if (is_array($decStream)) {
                foreach ($decStream as $s) {
                    $rRole = $s['role'] ?? '';
                    $item = [
                        'username'  => (string) ($s['username'] ?? '-'),
                        'ringkasan' => $this->ringkasAktivitas((string) ($s['ringkasan'] ?? '-')),
                        'modul'     => 'Live',
                        'tanggal'   => (string) ($s['tanggal'] ?? '-'),
                    ];
                    if ($rRole === 'master_admin' && count($masterLogs) < 60) {
                        $masterLogs[] = $item;
                    } elseif ($rRole === 'admin' && count($adminLogs) < 60) {
                        $adminLogs[] = $item;
                    } elseif ($rRole === 'user' && count($userLogs) < 60) {
                        $userLogs[] = $item;
                    }
                }
            }
        }

        try {
            if (Schema::hasTable('riwayat_aktivitas') && Schema::hasTable('users')) {
                $rows = DB::table('riwayat_aktivitas as ra')
                    ->leftJoin('users as u', 'ra.user_id', '=', 'u.users_id')
                    ->select(['ra.aktivitas', 'ra.modul', 'ra.created_at', 'u.nama_lengkap', 'u.role'])
                    ->orderByDesc('ra.id')
                    ->limit(150)
                    ->get();

                foreach ($rows as $r) {
                    $role = strtolower(str_replace([' ', '-'], '_', trim((string) ($r->role ?? ''))));
                    if ($role === 'masteradmin') {
                        $role = 'master_admin';
                    }
                    $tgl = '-';
                    if (!empty($r->created_at)) {
                        try {
                            $tgl = \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i');
                        } catch (\Throwable $e) {
                            $tgl = substr((string) $r->created_at, 0, 16);
                        }
                    }

                    $entry = [
                        'username'  => (string) ($r->nama_lengkap ?: 'Sistem'),
                        'ringkasan' => $this->ringkasAktivitas((string) ($r->aktivitas ?? '-')),
                        'modul'     => (string) ($r->modul ?? '-'),
                        'tanggal'   => $tgl,
                    ];

                    if ($role === 'master_admin' && count($masterLogs) < 50) {
                        $masterLogs[] = $entry;
                    } elseif ($role === 'admin' && count($adminLogs) < 50) {
                        $adminLogs[] = $entry;
                    } elseif ($role === 'user' && count($userLogs) < 50) {
                        $userLogs[] = $entry;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            if (Schema::hasTable('pengajuan_asets')) {
                $pengajuanRows = DB::table('pengajuan_asets')
                    ->select(['pemohon', 'jenis', 'nama_aset', 'tanggal_pengajuan', 'created_at'])
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();

                foreach ($pengajuanRows as $p) {
                    $tgl = '-';
                    $rawTgl = $p->created_at ?: $p->tanggal_pengajuan;
                    if (!empty($rawTgl)) {
                        try {
                            $tgl = \Carbon\Carbon::parse($rawTgl)->format('d/m/Y H:i');
                        } catch (\Throwable $e) {
                            $tgl = substr((string) $rawTgl, 0, 16);
                        }
                    }
                    $jenis = ucfirst(strtolower((string) ($p->jenis ?? 'pengajuan')));
                    $namaAset = trim((string) ($p->nama_aset ?? '-'));
                    $userLogs[] = [
                        'username'  => (string) ($p->pemohon ?: 'User Mobile'),
                        'ringkasan' => $this->ringkasAktivitas("Mengajukan {$jenis} untuk {$namaAset}"),
                        'modul'     => 'APK Mobile',
                        'tanggal'   => $tgl,
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        $presence = $this->collectRolesPresenceSnapshot();

        return [
            'masterLogs'    => array_slice($masterLogs, 0, 60),
            'adminLogs'     => array_slice($adminLogs, 0, 60),
            'userLogs'      => array_slice($userLogs, 0, 60),
            'allRolesUsers' => $presence['allRolesUsers'],
        ];
    }

    /**
     * Ambil daftar seluruh akun beserta status online/offline live dan ringkasan Master Admin.
     *
     * @return array{allRolesUsers: array<int, array<string, mixed>>, masterAdmins: array<int, array<string, mixed>>, mobileOnlineCount: int}
     */
    private function collectRolesPresenceSnapshot(): array
    {
        $presenceMap = [];
        $presenceFile = storage_path('app/online_presence.json');
        if (is_file($presenceFile)) {
            $decP = json_decode((string) @file_get_contents($presenceFile), true);
            if (is_array($decP)) {
                $presenceMap = $decP;
            }
        }
        $nowTs = time();

        $allRolesUsers = [];
        $masterAdmins = [];
        $mobileOnlineCount = 0;

        try {
            if (Schema::hasTable('users')) {
                $users = DB::table('users')
                    ->select(['users_id', 'nama_lengkap', 'role', 'created_at'])
                    ->orderBy('role')
                    ->orderBy('nama_lengkap')
                    ->get();
                foreach ($users as $u) {
                    $normRole = strtolower(str_replace([' ', '-'], '_', trim((string) ($u->role ?? 'user'))));
                    if ($normRole === 'masteradmin') {
                        $normRole = 'master_admin';
                    }
                    $uidStr = (string) $u->users_id;
                    $pInfo = $presenceMap[$uidStr] ?? null;
                    $lastSeen = is_array($pInfo) ? (int) ($pInfo['last_seen'] ?? 0) : 0;
                    $isOnline = ($lastSeen > 0 && ($nowTs - $lastSeen) <= 45);
                    $onlineChannel = $isOnline ? (string) ($pInfo['channel'] ?? 'web') : null;
                    if ($isOnline && $onlineChannel === 'mobile') {
                        $mobileOnlineCount++;
                    }

                    $row = [
                        'id'             => (int) $u->users_id,
                        'username'       => (string) ($u->nama_lengkap ?: '-'),
                        'role'           => $normRole,
                        'is_online'      => $isOnline,
                        'online_channel' => $onlineChannel,
                        'last_seen_at'   => $lastSeen > 0 ? date('d/m/Y H:i:s', $lastSeen) : '-',
                        'created_at'     => $u->created_at ? substr((string) $u->created_at, 0, 10) : '-',
                    ];
                    $allRolesUsers[] = $row;
                    if ($normRole === 'master_admin') {
                        $masterAdmins[] = $row;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return [
            'allRolesUsers'     => $allRolesUsers,
            'masterAdmins'      => $masterAdmins,
            'mobileOnlineCount' => $mobileOnlineCount,
        ];
    }

    public function resetMasterPassword(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sesi Developer tidak valid.'], 401);
            }
            return redirect()->route('developer.login');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'master_user_id' => ['required', 'integer'],
            'new_password'   => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ], [
            'master_user_id.required' => 'Pilih akun Master Admin terlebih dahulu.',
            'new_password.required'   => 'Kata sandi baru wajib diisi.',
            'new_password.min'        => 'Kata sandi baru minimal 6 karakter.',
            'new_password.confirmed'  => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $targetUser = User::where('users_id', (int) $request->input('master_user_id'))->first();
        if (!$targetUser) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akun Master Admin tidak ditemukan.'], 404);
            }
            return back()->withErrors(['master_user_id' => 'Akun Master Admin tidak ditemukan.']);
        }

        $targetRole = strtolower(str_replace([' ', '-'], '_', trim((string) ($targetUser->role ?? ''))));
        if ($targetRole !== 'master_admin' && $targetRole !== 'masteradmin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akun yang dipilih bukan Master Admin.'], 422);
            }
            return back()->withErrors(['master_user_id' => 'Akun yang dipilih bukan Master Admin.']);
        }

        $targetUser->password = Hash::make((string) $request->input('new_password'));
        $targetUser->save();

        $masterLockFile = storage_path('app/master_login_locks.json');
        @file_put_contents($masterLockFile, json_encode([
            'failed_count'    => 0,
            'is_blocked'      => false,
            'unlocked_by_dev' => true,
            'unlocked_at'     => date('Y-m-d H:i:s'),
            'updated_at'      => round(microtime(true) * 1000),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $masterResetPath = storage_path('app/master_reset_requests.json');
        if (is_file($masterResetPath)) {
            $reqs = json_decode((string) @file_get_contents($masterResetPath), true);
            if (is_array($reqs)) {
                foreach ($reqs as &$reqItem) {
                    if (($reqItem['status'] ?? '') === 'Menunggu') {
                        $reqItem['status'] = 'Selesai Direset';
                        $reqItem['resolved_at'] = date('Y-m-d H:i:s');
                    }
                }
                unset($reqItem);
                @file_put_contents($masterResetPath, json_encode($reqs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        try {
            if (class_exists(RiwayatAktivitas::class)) {
                RiwayatAktivitas::create([
                    'user_id'   => Auth::id(),
                    'aktivitas' => 'Developer mereset kata sandi & membuka blokir login Master Admin (' . ($targetUser->nama_lengkap ?: 'master_admin') . ').',
                    'modul'     => 'Developer Console',
                    'status'    => 'Berhasil',
                ]);
            }
        } catch (\Throwable $e) {
        }

        $msg = 'Kata sandi Master Admin (' . ($targetUser->nama_lengkap ?: 'Master Admin') . ') berhasil direset dan blokir login 3x gagal telah dibuka.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $this->collectSystemSnapshot(),
            ]);
        }

        return back()->with('success', $msg);
    }

    public function unblockIp(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sesi Developer tidak valid.'], 401);
            }
            return redirect()->route('developer.login');
        }

        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ], [
            'ip.required' => 'Alamat IP wajib diisi.',
            'ip.ip'       => 'Format alamat IP tidak valid.',
        ]);

        $ip = (string) $validated['ip'];
        $actor = (string) (Auth::user()->nama_lengkap ?? 'Developer');
        SecurityShieldMiddleware::unblockIp($ip, $actor);

        try {
            if (class_exists(RiwayatAktivitas::class)) {
                RiwayatAktivitas::create([
                    'user_id'   => Auth::id(),
                    'aktivitas' => 'Developer membuka blokir WAF untuk IP ' . $ip . '.',
                    'modul'     => 'Developer Console',
                    'status'    => 'Berhasil',
                ]);
            }
        } catch (\Throwable $e) {
        }

        $msg = 'Blokir IP ' . $ip . ' berhasil dibuka.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'    => true,
                'message'    => $msg,
                'blockedIps' => SecurityShieldMiddleware::getBlockedRegistry(),
            ]);
        }

        return back()->with('success', $msg);
    }

    public function unblockAllIps(Request $request)
    {
        if (!$this->hasValidDeveloperSession($request)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Sesi Developer tidak valid.'], 401);
            }
            return redirect()->route('developer.login');
        }

        $actor = (string) (Auth::user()->nama_lengkap ?? 'Developer');
        $count = SecurityShieldMiddleware::unblockAll($actor);

        try {
            if ($count > 0 && class_exists(RiwayatAktivitas::class)) {
                RiwayatAktivitas::create([
                    'user_id'   => Auth::id(),
                    'aktivitas' => 'Developer membuka seluruh blokir WAF (' . $count . ' IP).',
                    'modul'     => 'Developer Console',
                    'status'    => 'Berhasil',
                ]);
            }
        } catch (\Throwable $e) {
        }

        $msg = $count > 0
            ? "Berhasil membuka {$count} blokir/peringatan IP."
            : 'Tidak ada IP yang sedang terblokir saat ini.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'    => true,
                'message'    => $msg,
                'blockedIps' => SecurityShieldMiddleware::getBlockedRegistry(),
            ]);
        }

        return back()->with('success', $msg);
    }

    private function collectSystemSnapshot(): array
    {
        $dbStatus = 'Terhubung';
        $dbLatencyMs = 0.0;
        $dbError = null;
        try {
            $t0 = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $t0) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'Error';
            $dbError = $e->getMessage();
        }

        $telemetryFile = storage_path('app/system_telemetry.json');
        $rawTelemetry = [];
        if (is_file($telemetryFile)) {
            $decoded = json_decode((string) @file_get_contents($telemetryFile), true);
            if (is_array($decoded)) {
                $rawTelemetry = $decoded;
            }
        }

        $webEntries = array_values(array_filter($rawTelemetry, fn($e) => ($e['channel'] ?? 'web') === 'web'));
        $mobileEntries = array_values(array_filter($rawTelemetry, fn($e) => ($e['channel'] ?? 'web') === 'mobile'));
        $errorEntries = array_values(array_filter($rawTelemetry, fn($e) => !empty($e['is_error'])));

        $calcStats = function (array $list): array {
            $count = count($list);
            if ($count === 0) {
                return ['count' => 0, 'avg_ms' => 0, 'max_ms' => 0, 'errors' => 0, 'error_rate' => 0];
            }
            $durations = array_map(fn($x) => (float) ($x['duration_ms'] ?? 0), $list);
            $errs = count(array_filter($list, fn($x) => !empty($x['is_error'])));
            return [
                'count'      => $count,
                'avg_ms'     => round(array_sum($durations) / $count, 2),
                'max_ms'     => round(max($durations), 2),
                'errors'     => $errs,
                'error_rate' => round(($errs / $count) * 100, 1),
            ];
        };

        $webStats = $calcStats($webEntries);
        $mobileStats = $calcStats($mobileEntries);

        $diskFreeBytes = @disk_free_space(base_path()) ?: 0;
        $diskTotalBytes = @disk_total_space(base_path()) ?: 1;
        $diskUsedPct = round((($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100, 1);

        $health = [
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'db_status'        => $dbStatus,
            'db_latency_ms'    => $dbLatencyMs,
            'db_error'         => $dbError,
            'memory_mb'        => round(memory_get_usage(true) / 1048576, 2),
            'disk_free_gb'     => round($diskFreeBytes / 1073741824, 2),
            'disk_used_pct'    => $diskUsedPct,
            'storage_writable' => is_writable(storage_path('app')),
            'server_time'      => date('d/m/Y H:i:s'),
        ];

        $logErrors = $this->readRecentLaravelErrors(20);
        $nowTs = time();

        $presenceSnapshot = $this->collectRolesPresenceSnapshot();
        $masterAdmins = $presenceSnapshot['masterAdmins'];
        $mobileOnlineCount = $presenceSnapshot['mobileOnlineCount'];

        $latestMobile = $mobileEntries[0] ?? null;
        $lastMobileTs = (!empty($latestMobile['timestamp'])) ? (int) strtotime((string) $latestMobile['timestamp']) : 0;
        $diffMobileSec = $lastMobileTs > 0 ? max(0, $nowTs - $lastMobileTs) : null;

        if ($mobileOnlineCount > 0 || ($diffMobileSec !== null && $diffMobileSec <= 120)) {
            $mobileConnState = 'active';
            $mobileConnLabel = 'Terhubung (Aktif)';
            $mobileConnected = true;
        } elseif ($latestMobile !== null) {
            $mobileConnState = 'idle';
            $mobileConnLabel = 'Terhubung (Idle)';
            $mobileConnected = true;
        } else {
            $mobileConnState = 'disconnected';
            $mobileConnLabel = 'Belum Terhubung';
            $mobileConnected = false;
        }

        $mobileApiStatus = [
            'connected'      => $mobileConnected,
            'state'          => $mobileConnState,
            'label'          => $mobileConnLabel,
            'active_devices' => $mobileOnlineCount,
            'last_endpoint'  => $latestMobile['path'] ?? '-',
            'last_status'    => $latestMobile['status'] ?? null,
            'last_seen_at'   => $latestMobile['timestamp'] ?? '-',
            'last_ip'        => $latestMobile['ip'] ?? '-',
            'api_base_url'   => url('/api'),
        ];

        $masterLockState = [
            'failed_count' => 0,
            'is_blocked'   => false,
            'last_email'   => '',
            'blocked_at'   => null,
        ];
        $masterLockFile = storage_path('app/master_login_locks.json');
        if (is_file($masterLockFile)) {
            $decLock = json_decode((string) @file_get_contents($masterLockFile), true);
            if (is_array($decLock)) {
                $masterLockState = array_merge($masterLockState, $decLock);
            }
        }

        $masterResetRequests = [];
        $masterResetPath = storage_path('app/master_reset_requests.json');
        if (is_file($masterResetPath)) {
            $decReqs = json_decode((string) @file_get_contents($masterResetPath), true);
            if (is_array($decReqs)) {
                $masterResetRequests = $decReqs;
            }
        }

        return [
            'health'              => $health,
            'webStats'            => $webStats,
            'mobileStats'         => $mobileStats,
            'mobileApiStatus'     => $mobileApiStatus,
            'webTelemetry'        => array_slice($webEntries, 0, 35),
            'mobileTelemetry'     => array_slice($mobileEntries, 0, 35),
            'recentTelemetry'     => array_slice($rawTelemetry, 0, 40),
            'recentErrorReqs'     => array_slice($errorEntries, 0, 25),
            'logErrors'           => $logErrors,
            'masterAdmins'        => $masterAdmins,
            'masterLockState'     => $masterLockState,
            'masterResetRequests' => $masterResetRequests,
            'blockedIps'          => SecurityShieldMiddleware::getBlockedRegistry(),
        ];
    }

    private function readRecentLaravelErrors(int $limit = 20): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath)) {
            return [];
        }

        $entries = [];
        try {
            $size = filesize($logPath);
            $readBytes = min($size, 131072);
            $fp = fopen($logPath, 'rb');
            if ($fp) {
                if ($size > $readBytes) {
                    fseek($fp, $size - $readBytes);
                }
                $chunk = fread($fp, $readBytes);
                fclose($fp);

                $lines = explode("\n", (string) $chunk);
                foreach ($lines as $line) {
                    if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s+[a-zA-Z0-9_-]+\.(ERROR|CRITICAL|ALERT|EMERGENCY|WARNING):\s+(.*)$/', $line, $m)) {
                        $entries[] = [
                            'time'    => $m[1],
                            'level'   => $m[2],
                            'message' => mb_strimwidth(trim($m[3]), 0, 240, '...'),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $entries = array_reverse($entries);
        return array_slice($entries, 0, $limit);
    }
}
