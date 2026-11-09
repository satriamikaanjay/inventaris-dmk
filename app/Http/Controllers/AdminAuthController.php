<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Str;                 
use Illuminate\Validation\ValidationException;

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
        // ==========================================
        // 1. CEK BATAS PERCOBAAN LOGIN (MAX 3 KALI)
        // ==========================================
       $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Akun dikunci sementara, silakan coba lagi dalam {$seconds} detik.",
            ]);
        }


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

       // ==========================================
        // 4. JIKA LOGIN GAGAL (HITUNG PERCOBAAN)
        // ==========================================
       RateLimiter::hit($throttleKey, 60); // Blokir/hitung selama 60 detik
        
        $sisaUpaya = 3 - RateLimiter::attempts($throttleKey);
        $pesanGagal = 'Email atau password yang Anda masukkan salah.';
        
        if ($sisaUpaya > 0) {
            $pesanGagal .= " Sisa percobaan login: {$sisaUpaya} kali lagi.";
        } else {
            $pesanGagal = 'Anda sudah 3 kali salah memasukkan data. Silakan tunggu sebentar sebelum mencoba lagi.';
        }

        return back()->withErrors([
            'email' => $pesanGagal,
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