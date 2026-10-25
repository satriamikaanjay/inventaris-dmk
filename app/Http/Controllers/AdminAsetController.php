<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\AdminCabang;
use App\Models\AdminKategori;
use App\Models\AdminItem;
use App\Models\AdminDetailAset;
use App\Models\AdminStatusAset;

class AdminAsetController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminDetailAset::with(['item', 'kategori', 'cabang']);

        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('kode_aset', 'like', "%{$keyword}%")
                  ->orWhere('nomor_seri', 'like', "%{$keyword}%")
                  ->orWhere('merk', 'like', "%{$keyword}%")
                  ->orWhere('Ruangan', 'like', "%{$keyword}%")
                  ->orWhereHas('item', function($i) use ($keyword) {
                      $i->where('nama_aset', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($request->filled('filter_tipe') && $request->filter_tipe !== 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        $detailAsets = $query->paginate(50)->withQueryString();
        $kategoris = AdminKategori::all();
        $items = AdminItem::all();
        $cabangs = AdminCabang::withCount('asets')->get();
        $statuses = AdminStatusAset::all();

        return view('data_aset', compact('detailAsets', 'kategoris', 'items', 'cabangs', 'statuses'));
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Data aset fisik berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $aset = AdminDetailAset::findOrFail($id);
        return back()->with('success', 'Perubahan data aset berhasil disimpan.');
    }

    public function destroy($id)
    {
        $aset = AdminDetailAset::findOrFail($id);
        $aset->delete();
        return back()->with('success', 'Data aset berhasil dihapus.');
    }

    public function importExcel(Request $request)
    {
        return back()->with('success', 'Data aset berhasil diimport.');
    }
}