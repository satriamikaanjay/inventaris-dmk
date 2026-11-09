<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Str; 
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Menampilkan halaman login (resources/views/index.blade.php)
    public function index()
    {
        // 1. Cek apakah pengguna sudah dalam keadaan login (diingat oleh sistem)
        if (Auth::check()) {
            $user = Auth::user();

            // 2. Jika sudah login, langsung lempar ke dashboard sesuai role
            if ($user->role === 'master_admin') {
                return redirect('/masteradmin'); 
            } elseif ($user->role === 'admin') {
                return redirect('/admin-dashboard'); 
            }
        }

        // 3. Jika belum login sama sekali, barulah tampilkan form login
        return view('index');
    }

    // Memproses data saat user menekan tombol login
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    // Kunci unik berdasarkan email & IP
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        // Cek jika sudah melebihi batas 3 kali percobaan
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Akun dikunci sementara, silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

    $loginAs = $request->input('login_as'); // Mengambil data role dari tombol yang diklik

    $remember = $request->has('remember');

        // Tambahkan variabel $remember sebagai parameter kedua di Auth::attempt
        if (Auth::attempt($credentials, $remember)) {
        $user = Auth::user();

        // Cek apakah role di database cocok dengan role yang dipilih di halaman login
        if ($user->role !== $loginAs) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Data tidak ditemukan atau Anda tidak memiliki akses sebagai ' . str_replace('_', ' ', $loginAs) . '.',
            ])->onlyInput('email');
        }

        // Hapus riwayat kegagalan login karena berhasil masuk
            RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        \App\Models\RiwayatAktivitas::create([
            'user_id'   => $user->users_id ?? $user->id,
            'aktivitas' => 'Melakukan Login ke sistem.',
            'modul'     => 'Autentikasi Sistem',
            'status'    => 'Informasi',
        ]);

        // Redirect sesuai role
        if ($user->role === 'master_admin') {
    return redirect()->intended('/masteradmin'); 
}    elseif ($user->role === 'admin') {
            return redirect()->intended('/admin-dashboard'); 
        }
    }

    // Jika email/password salah
    RateLimiter::hit($throttleKey, 60); // Blokir 60 detik jika habis

    $sisaUpaya = 3 - RateLimiter::attempts($throttleKey);
    $pesanGagal = 'Email atau password salah.';

    if ($sisaUpaya > 0) {
        $pesanGagal .= " Sisa percobaan login: {$sisaUpaya} kali lagi.";
    } else {
        $pesanGagal = 'Anda sudah 3 kali salah memasukkan data. Silakan tunggu sebentar sebelum mencoba lagi.';
    }

    return back()->withErrors([
        'email' => $pesanGagal,
    ])->onlyInput('email');
}
    // Fungsi untuk Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/'); // Kembali ke halaman utama (login)
    }

}