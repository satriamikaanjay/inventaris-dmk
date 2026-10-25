<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminDetailAset;
use App\Models\AdminCabang;
use App\Models\AdminStatusAset;
use Carbon\Carbon;

class AdminNotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $hariIni = Carbon::now()->startOfDay();
        $batasWaktunya = 7;
        $batasMendekati = 30;

        $query = AdminDetailAset::with(['item', 'kategori', 'cabang']);

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function($q) use ($keyword) {
                $q->where('kode_aset', 'like', "%{$keyword}%")
                  ->orWhere('merk', 'like', "%{$keyword}%")
                  ->orWhere('Ruangan', 'like', "%{$keyword}%")
                  ->orWhereHas('item', function($i) use ($keyword) {
                      $i->where('nama_aset', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($request->filled('cabang')) {
            $query->where('cabang_id', $request->cabang);
        }

        $tipe = $request->get('tipe', 'semua');
        if ($tipe === 'rusak') {
            $query->where('status_aset', '!=', 'Tersedia');
        } elseif ($tipe === 'terlewat') {
            $query->whereNotNull('servis_berikutnya')->where('servis_berikutnya', '<', $hariIni);
        } elseif ($tipe === 'waktunya') {
            $query->whereNotNull('servis_berikutnya')
                  ->whereBetween('servis_berikutnya', [$hariIni, $hariIni->copy()->addDays($batasWaktunya)]);
        } elseif ($tipe === 'mendekati') {
            $query->whereNotNull('servis_berikutnya')
                  ->whereBetween('servis_berikutnya', [$hariIni->copy()->addDays($batasWaktunya + 1), $hariIni->copy()->addDays($batasMendekati)]);
        }

        $notifs = $query->paginate(20)->withQueryString();
        $cabangs = AdminCabang::all();
        $statuses = AdminStatusAset::all();

        $hitung = ['semua' => 0, 'rusak' => 0, 'terlewat' => 0, 'waktunya' => 0, 'mendekati' => 0];
        $batas = ['waktunya' => $batasWaktunya, 'mendekati' => $batasMendekati];

        return view('notifikasi', compact('notifs', 'cabangs', 'statuses', 'hitung', 'batas', 'hariIni', 'tipe'));
    }
}