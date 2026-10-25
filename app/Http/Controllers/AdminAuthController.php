<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    // Menampilkan halaman login
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $role = strtolower(str_replace(' ', '_', $user->role ?? ''));

            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'master_admin') {
                return redirect()->route('masteradmin.dashboard');
            }
        }

        return view('admin.index'); // Sesuaikan dengan lokasi view login Anda jika berbeda
    }

    // Memproses login
    public function prosesLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $userRole = strtolower(str_replace(' ', '_', $user->role ?? ''));

            // Catat riwayat aktivitas login
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
                // Jangan gagalkan login jika log riwayat gagal
            }

            // Logika pengalihan berdasarkan role pengguna sebenarnya di database
            if ($userRole === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($userRole === 'master_admin') {
                return redirect()->route('masteradmin.dashboard');
            } else {
                return redirect('/');
            }
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->withInput();
    }
    // Proses logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}