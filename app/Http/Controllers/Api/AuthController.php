<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiwayatAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_API_LOGIN_ATTEMPTS = 5;
    private const API_LOGIN_LOCKOUT_SECONDS = 600;

    public static function isUserDashboardAllowed(): bool
    {
        $path = storage_path('app/roles_permissions.json');
        if (!is_file($path)) {
            return true;
        }
        try {
            clearstatcache(true, $path);
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['roles']['user']['permissions']['dashboard']['view'])) {
                return !empty($decoded['roles']['user']['permissions']['dashboard']['view']);
            }
        } catch (\Throwable $e) {
        }
        return true;
    }

    public function accessStatus()
    {
        $allowed = self::isUserDashboardAllowed();
        return response()->json([
            'status'  => $allowed ? 'allowed' : 'blocked',
            'allowed' => $allowed,
            'blocked' => !$allowed,
            'message' => $allowed ? 'Akses user aktif' : 'Akses user di blokir oleh master admin',
        ], $allowed ? 200 : 403);
    }

    public function login(Request $request)
    {
        if (!self::isUserDashboardAllowed()) {
            return response()->json([
                'status'  => 'blocked',
                'success' => false,
                'blocked' => true,
                'message' => 'Akses user di blokir oleh master admin',
                'data'    => null,
            ], 403);
        }

        $rawIdentifier = $request->input('email', $request->input('username', ''));
        $rawPassword = $request->input('password', '');

        if (is_array($rawIdentifier) || is_array($rawPassword)) {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'blocked' => false,
                'message' => 'Format kredensial login tidak valid.',
                'data'    => null,
            ], 422);
        }

        $identifier = trim((string) $rawIdentifier);
        $password = (string) $rawPassword;

        if ($identifier === '' || $password === '') {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'blocked' => false,
                'message' => 'Email/username dan password wajib diisi.',
                'data'    => null,
            ], 422);
        }

        $rateKey = 'api_login|' . sha1(Str::lower($identifier)) . '|' . $request->ip();
        $ipRateKey = 'api_login_ip|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, self::MAX_API_LOGIN_ATTEMPTS) || RateLimiter::tooManyAttempts($ipRateKey, 15)) {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'blocked' => false,
                'message' => 'Terlalu banyak percobaan login gagal. Silakan coba lagi beberapa menit lagi.',
                'data'    => null,
            ], 429);
        }

        $dbUser = null;
        try {
            if (Schema::hasTable('users')) {
                $query = User::where('email', $identifier);
                if (Schema::hasColumn('users', 'nama_lengkap')) {
                    $query->orWhere('nama_lengkap', $identifier);
                }
                if (Schema::hasColumn('users', 'username')) {
                    $query->orWhere('username', $identifier);
                }
                $dbUser = $query->first();
            }
        } catch (\Throwable $e) {
        }

        $storedPassword = $dbUser ? (string) ($dbUser->password ?? '') : '';
        if (!$dbUser || $storedPassword === '' || !Hash::check($password, $storedPassword)) {
            RateLimiter::hit($rateKey, self::API_LOGIN_LOCKOUT_SECONDS);
            RateLimiter::hit($ipRateKey, self::API_LOGIN_LOCKOUT_SECONDS);
            $this->logFailedApiLogin($identifier, (string) $request->ip());

            return response()->json([
                'status'  => 'error',
                'success' => false,
                'blocked' => false,
                'message' => 'Email/username atau password salah.',
                'data'    => null,
            ], 401);
        }

        $normRole = strtolower(str_replace([' ', '-'], '_', trim((string) ($dbUser->role ?? 'user'))));
        if ($normRole === 'masteradmin') {
            $normRole = 'master_admin';
        }
        if ($normRole === 'developer') {
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'blocked' => true,
                'message' => 'Akun Developer tidak diizinkan login melalui API mobile.',
                'data'    => null,
            ], 403);
        }

        RateLimiter::clear($rateKey);
        RateLimiter::clear($ipRateKey);

        $uid = $dbUser->users_id ?? $dbUser->id;
        $uname = $dbUser->nama_lengkap ?: ($dbUser->name ?? $identifier);
        $userData = [
            'id'           => (int) $uid,
            'user_id'      => (int) $uid,
            'user_name'    => $uname,
            'nama_lengkap' => $uname,
            'email'        => $dbUser->email ?: $identifier,
            'role'         => $dbUser->role ?: 'user',
        ];

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'blocked' => false,
            'message' => 'Login berhasil',
            'data'    => $userData,
        ]);
    }

    private function logFailedApiLogin(string $identifier, string $ip): void
    {
        try {
            if (!class_exists(RiwayatAktivitas::class)) {
                return;
            }
            $riwayat = new RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            if (!Schema::hasTable($tbl)) {
                return;
            }
            $data = [];
            if (Schema::hasColumn($tbl, 'user_id')) {
                $data['user_id'] = null;
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $data['aktivitas'] = 'Percobaan login API gagal untuk (' . substr(strip_tags($identifier), 0, 80) . ') dari IP ' . $ip;
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $data['modul'] = 'Autentikasi API';
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $data['status'] = 'Peringatan';
            }
            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    }
}
