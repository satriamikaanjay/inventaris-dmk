<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $role = strtolower(str_replace([' ', '-'], '_', trim((string) ($user->role ?? ''))));

            if ($role === 'master_admin' || $role === 'masteradmin') {
                return redirect('/masteradmin');
            } elseif ($role === 'admin') {
                return redirect('/admin-dashboard');
            }
        }

        return view('index');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'email.required'    => 'Username atau email wajib diisi.',
            'email.max'         => 'Username atau email terlalu panjang.',
            'password.required' => 'Password wajib diisi.',
            'password.max'      => 'Password terlalu panjang.',
        ]);

        if ($request->filled('website_trap')) {
            return back()->withErrors([
                'email' => 'Akses ditolak oleh sistem keamanan anti-bot.',
            ]);
        }

        $identifier = strip_tags(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $loginAs = strtolower(str_replace([' ', '-'], '_', trim((string) $request->input('login_as', 'admin'))));
        if ($loginAs === 'masteradmin') {
            $loginAs = 'master_admin';
        }
        if (!in_array($loginAs, ['admin', 'master_admin'], true)) {
            $loginAs = 'admin';
        }
        $remember = $request->boolean('remember');

        $berhasilAuth = Auth::attempt(['email' => $identifier, 'password' => $password], $remember);

        if (!$berhasilAuth && Schema::hasTable('users') && Schema::hasColumn('users', 'nama_lengkap')) {
            $berhasilAuth = Auth::attempt(['nama_lengkap' => $identifier, 'password' => $password], $remember);
        }

        if ($berhasilAuth) {
            $user = Auth::user();
            $userRole = strtolower(str_replace([' ', '-'], '_', trim((string) ($user->role ?? ''))));
            if ($userRole === 'masteradmin') {
                $userRole = 'master_admin';
            }

            if ($loginAs !== '' && $userRole !== $loginAs) {
                Auth::logout();
                $labelRole = $loginAs === 'admin' ? 'Admin' : 'Master Admin';

                return back()->withErrors([
                    'email' => "Akun tidak ditemukan atau Anda tidak memiliki akses sebagai {$labelRole}.",
                ])->withInput($request->only('email', 'login_as', 'remember'));
            }

            $request->session()->regenerate(true);
            $request->session()->regenerateToken();

            try {
                if (class_exists(\App\Models\RiwayatAktivitas::class)) {
                    \App\Models\RiwayatAktivitas::create([
                        'user_id'   => $user->users_id ?? $user->id,
                        'aktivitas' => 'Melakukan Login ke sistem.',
                        'modul'     => 'Autentikasi Sistem',
                        'status'    => 'Informasi',
                    ]);
                }
            } catch (\Throwable $e) {
            }

            if ($userRole === 'master_admin') {
                return redirect()->intended('/masteradmin');
            } elseif ($userRole === 'admin') {
                return redirect()->intended('/admin-dashboard');
            }

            return redirect('/');
        }

        usleep(random_int(120000, 280000));

        return back()->withErrors([
            'email' => 'Username/email atau password yang Anda masukkan salah.',
        ])->withInput($request->only('email', 'login_as', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
