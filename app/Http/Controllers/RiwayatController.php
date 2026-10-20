<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiwayatAktivitas;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\RiwayatAktivitas::query();

        // 1. Logika Filter Pencarian Teks
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('aktivitas', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('nama_lengkap', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // 2. LOGIKA BARU: Filter Jenis Aktivitas
        if ($request->filled('jenis')) {
            $jenis = $request->jenis;
            $query->where(function($q) use ($jenis) {
                if ($jenis == 'Tambah') {
                    $q->where('aktivitas', 'like', '%Menambah%')
                      ->orWhere('aktivitas', 'like', '%Membuat%');
                } elseif ($jenis == 'Ubah') {
                    $q->where('aktivitas', 'like', '%Memperbarui%')
                      ->orWhere('aktivitas', 'like', '%Mengubah%')
                      ->orWhere('aktivitas', 'like', '%Edit%');
                } elseif ($jenis == 'Hapus') {
                    $q->where('aktivitas', 'like', '%Menghapus%');
                } elseif ($jenis == 'Sistem') {
                    $q->where('aktivitas', 'like', '%Login%')
                      ->orWhere('aktivitas', 'like', '%Logout%')
                      ->orWhere('aktivitas', 'like', '%Sistem%');
                }
            });
        }

        // 3. Logika Filter Bulan & Tahun
        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->tahun);
        }

        // Ambil data untuk pagination (Gunakan withQueryString agar filter tidak hilang saat pindah halaman)
        $riwayats = $query->latest()->paginate(15)->withQueryString();

        // Ambil daftar tahun untuk Dropdown filter tahun
        $tahunList = \App\Models\RiwayatAktivitas::selectRaw('YEAR(created_at) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');
            
        if ($tahunList->isEmpty()) {
            $tahunList = collect([date('Y')]);
        }

        return view('masteradmin.riwayat', compact('riwayats', 'tahunList'));
    }

    public function clear()
    {
        RiwayatAktivitas::truncate();

        return redirect()->route('riwayat_aktivitas.index')->with('success', 'Riwayat aktivitas berhasil dibersihkan.');
    }
}
