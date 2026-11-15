<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminKategori;
use App\Models\AdminItem;
use App\Models\AdminStatusAset;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class AdminKategoriController extends Controller
{
    public function index(Request $request)
    {
        $kategoris = AdminKategori::all();
        $itemsQuery = AdminItem::with('kategori');

        if ($request->filled('search')) {
            $keyword = $request->search;
            $itemsQuery->where('nama_aset', 'like', "%{$keyword}%")
                       ->orWhere('awal_serial', 'like', "%{$keyword}%");
        }

        $items = $itemsQuery->paginate(20)->withQueryString();
        $statuses = AdminStatusAset::all();

        return view('admin.kategori', compact('kategoris', 'items', 'statuses'));
    }

    public function storeKategori(Request $request)
    {
        AdminKategori::create($request->validate([
            'nama_kategori' => 'required|string|max:100',
            'kode_kategori' => 'required|string|max:20|unique:kategoris'
        ]));

        return back()->with('success', 'Kategori berhasil disimpan.');
    }

    public function updateKategori(Request $request, $id)
    {
        $kat = AdminKategori::findOrFail($id);
        $kat->update($request->validate([
            'nama_kategori' => 'required|string|max:100',
            'kode_kategori' => 'required|string|max:20'
        ]));

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyKategori(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminKategori::findOrFail($id), 'Kategori');
    }

    public function storeItem(Request $request)
    {
        AdminItem::create($request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'urutan' => 'required|integer',
            'nama_aset' => 'required|string|max:150',
            'awal_serial' => 'nullable|string|max:50',
            'warna_seri' => 'nullable|string|max:20',
        ]));

        return back()->with('success', 'Master aset berhasil disimpan.');
    }

    public function updateItem(Request $request, $id)
    {
        $item = AdminItem::findOrFail($id);
        $item->update($request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'urutan' => 'required|integer',
            'nama_aset' => 'required|string|max:150',
            'awal_serial' => 'nullable|string|max:50',
            'warna_seri' => 'nullable|string|max:20',
        ]));

        return back()->with('success', 'Master aset berhasil diperbarui.');
    }

    public function destroyItem(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminItem::findOrFail($id), 'Master aset');
    }

    public function destroyStatusAset(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminStatusAset::findOrFail($id), 'Status aset');
    }

    public function storeStatusAset(Request $request)
    {
        AdminStatusAset::create($request->validate([
            'nama_status' => 'required|string|max:50',
            'warna_status' => 'nullable|string|max:20',
        ]));
        return back()->with('success', 'Status aset berhasil ditambahkan.');
    }

    /**
     * Hapus data master dengan pengecekan izin "delete" modul kategori_aset
     * (diatur Master Admin di Roles & Permission) dan respons JSON/redirect.
     */
    private function hapusMaster(Request $request, $model, string $label)
    {
        $json = $request->ajax() || $request->wantsJson();

        if (!$this->bolehHapus()) {
            $pesan = 'Anda tidak memiliki izin menghapus data ini. Hubungi Master Admin.';
            return $json
                ? response()->json(['success' => false, 'message' => $pesan], 403)
                : back()->withErrors(['error' => $pesan]);
        }

        try {
            $model->delete();
        } catch (QueryException $e) {
            $pesan = $label . ' tidak dapat dihapus karena masih dipakai oleh data lain.';
            return $json
                ? response()->json(['success' => false, 'message' => $pesan], 422)
                : back()->withErrors(['error' => $pesan]);
        }

        $pesan = $label . ' berhasil dihapus.';
        return $json
            ? response()->json(['success' => true, 'message' => $pesan])
            : back()->with('success', $pesan);
    }

    private function bolehHapus(): bool
    {
        $role = strtolower(str_replace(' ', '_', trim((string) (Auth::user()->role ?? 'admin'))));
        if (in_array($role, ['master_admin', 'masteradmin'], true)) {
            return true;
        }

        $path = storage_path('app/roles_permissions.json');
        if (!is_file($path)) {
            return true; // belum ada konfigurasi = default semua diizinkan
        }

        $data = json_decode((string) file_get_contents($path), true);
        $perm = $data['roles'][$role]['permissions']['kategori_aset']
            ?? ($data['roles']['admin']['permissions']['kategori_aset'] ?? null);

        if (!is_array($perm)) {
            return true;
        }

        return !empty($perm['view']) && !empty($perm['delete']);
    }
}
