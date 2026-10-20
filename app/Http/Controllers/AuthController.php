<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    return back()->withErrors([
        'email' => 'Email atau password salah. Silakan coba lagi.',
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