<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Item;
use App\Models\DetailAset;
use App\Models\StatusAset;
use App\Support\RolePermission;

class AdminAsetController extends Controller
{
    public function index(Request $request)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        $items = Item::orderBy('kategori_id')->orderBy('urutan')->get();
        $statuses = StatusAset::all();
        $cabangs = Cabang::withCount('asets')->orderBy('nama_cabang')->get();

        $query = DetailAset::with(['item', 'kategori', 'cabang'])->orderBy('aset_id', $request->sort === 'terlama' ? 'asc' : 'desc');

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

    private function cekIzin(Request $request, string $aksi, string $pesan)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'data_aset', $aksi)) {
            return RolePermission::denyResponse($request, $pesan);
        }
        return null;
    }

    public function store(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin menambah Data Aset. Hubungi Master Admin.')) {
            return $resp;
        }
        return app(ItemController::class)->storeDetail($request);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->cekIzin($request, 'edit', 'Anda tidak memiliki izin mengubah Data Aset. Hubungi Master Admin.')) {
            return $resp;
        }
        return app(ItemController::class)->updateDetail($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        if ($resp = $this->cekIzin($request, 'delete', 'Anda tidak memiliki izin menghapus Data Aset. Hubungi Master Admin.')) {
            return $resp;
        }
        return app(ItemController::class)->destroyDetail($request, $id);
    }

    public function bulkDelete(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'delete', 'Anda tidak memiliki izin menghapus Data Aset. Hubungi Master Admin.')) {
            return $resp;
        }
        return app(ItemController::class)->bulkDelete($request);
    }

    public function importExcel(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin mengimpor Data Aset. Hubungi Master Admin.')) {
            return $resp;
        }
        return app(ItemController::class)->importExcel($request);
    }

    public function downloadTemplateImport(Request $request, string $format = 'xlsx')
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin mengunduh template import Data Aset.')) {
            return $resp;
        }
        return app(ItemController::class)->downloadTemplateImport($format);
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
