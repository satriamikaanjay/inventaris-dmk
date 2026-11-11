<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Item;
use App\Models\DetailAset;
use App\Models\StatusAset;

class AdminAsetController extends Controller
{
    public function index(Request $request)
    {
        $kategoris = Kategori::paginate(50, ['*'], 'kategori_page');
        $items = Item::paginate(50, ['*'], 'item_page');
        $statuses = StatusAset::all();
        $cabangs = Cabang::withCount('asets')->get();

        $query = DetailAset::with(['item', 'kategori', 'cabang'])->latest('aset_id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_aset', 'like', "%{$search}%")
                  ->orWhere('merk', 'like', "%{$search}%")
                  ->orWhere('nomor_seri', 'like', "%{$search}%")
                  ->orWhere('Ruangan', 'like', "%{$search}%")
                  ->orWhere('lokasi_saat_ini', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%")
                  ->orWhere('status_aset', 'like', "%{$search}%")
                  ->orWhereHas('item', function ($qItem) use ($search) {
                      $qItem->where('nama_aset', 'like', "%{$search}%");
                  })
                  ->orWhereHas('kategori', function ($qKat) use ($search) {
                      $qKat->where('nama_kategori', 'like', "%{$search}%");
                  })
                  ->orWhereHas('cabang', function ($qCab) use ($search) {
                      $qCab->where('nama_cabang', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('filter_tipe') && $request->filter_tipe !== 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        $detailAsets = $query->paginate(100)->withQueryString();

        return view('admin.data_aset', compact('detailAsets', 'kategoris', 'items', 'cabangs', 'statuses'));
    }

    public function store(Request $request)
    {
        return app(ItemController::class)->storeDetail($request);
    }

    public function update(Request $request, $id)
    {
        return app(ItemController::class)->updateDetail($request, $id);
    }

    public function destroy($id)
    {
        return app(ItemController::class)->destroyDetail($id);
    }

    public function importExcel(Request $request)
    {
        return app(ItemController::class)->importExcel($request);
    }

    public function viewLampiran($filename)
    {
        return app(ItemController::class)->viewLampiran($filename);
    }

    public function downloadLampiran($filename)
    {
        return app(ItemController::class)->downloadLampiran($filename);
    }
}
