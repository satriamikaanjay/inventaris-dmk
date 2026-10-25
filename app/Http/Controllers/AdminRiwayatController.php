<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiwayatAktivitas; // Sesuaikan dengan nama model log Anda

class AdminRiwayatController extends Controller
{
    public function index(Request $request)
    {
        $query = RiwayatAktivitas::with('user')->latest();

        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where('aktivitas', 'like', "%{$keyword}%");
        }

        if ($request->filled('jenis')) {
            $query->where('modul', $request->jenis);
        }

        $riwayats = $query->paginate(15)->withQueryString();
        $tahunList = RiwayatAktivitas::selectRaw('YEAR(created_at) as tahun')->distinct()->pluck('tahun');

        return view('riwayat_aktivitas', compact('riwayats', 'tahunList'));
    }

    public function clear()
    {
        RiwayatAktivitas::truncate();
        return back()->with('success', 'Log riwayat aktivitas berhasil dibersihkan.');
    }
}