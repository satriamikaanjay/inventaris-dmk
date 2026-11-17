<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminKategori;
use App\Models\AdminItem;
use App\Models\AdminStatusAset;
use App\Models\RiwayatAktivitas;
use App\Support\RolePermission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminKategoriController extends Controller
{
    public function index(Request $request)
    {
        $kategoris = AdminKategori::all();
        $itemsQuery = AdminItem::with('kategori');

        if ($request->filled('search')) {
            $keyword = $request->search;
            $itemsQuery->where(function ($q) use ($keyword) {
                $q->where('nama_aset', 'like', "%{$keyword}%")
                  ->orWhere('awal_serial', 'like', "%{$keyword}%");
            });
        }

        $items = $itemsQuery->paginate(20)->withQueryString();
        $statuses = AdminStatusAset::all();
        $maxUrutanMap = AdminItem::selectRaw('kategori_id, COALESCE(MAX(urutan), 0) as max_urutan')
            ->groupBy('kategori_id')
            ->pluck('max_urutan', 'kategori_id')
            ->all();

        return view('admin.kategori', compact('kategoris', 'items', 'statuses', 'maxUrutanMap'));
    }

    public function storeKategori(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin menambah kategori. Hubungi Master Admin.')) {
            return $resp;
        }

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100|unique:kategoris,nama_kategori',
            'kode_kategori' => 'required|string|max:20|unique:kategoris,kode_kategori',
        ], [
            'nama_kategori.unique' => 'Nama kategori sudah terdaftar.',
            'kode_kategori.unique' => 'Kode kategori sudah terdaftar.',
        ]);

        $kat = AdminKategori::create($validated);
        $this->catatRiwayat('Menambah kategori aset baru: ' . $kat->nama_kategori . ' (' . $kat->kode_kategori . ')', 'Kategori Aset', 'Berhasil');

        return $this->responSukses($request, 'Kategori baru berhasil ditambahkan!');
    }

    public function updateKategori(Request $request, $id)
    {
        if ($resp = $this->cekIzin($request, 'edit', 'Anda tidak memiliki izin mengubah kategori. Hubungi Master Admin.')) {
            return $resp;
        }

        $kat = AdminKategori::findOrFail($id);
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('kategoris', 'nama_kategori')->ignore($kat->id)],
            'kode_kategori' => ['required', 'string', 'max:20', Rule::unique('kategoris', 'kode_kategori')->ignore($kat->id)],
        ], [
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
            'kode_kategori.unique' => 'Kode kategori sudah digunakan.',
        ]);

        $kat->update($validated);
        $this->catatRiwayat('Memperbarui kategori aset: ' . $kat->nama_kategori . ' (' . $kat->kode_kategori . ')', 'Kategori Aset', 'Informasi');

        return $this->responSukses($request, 'Kategori berhasil diperbarui!');
    }

    public function destroyKategori(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminKategori::findOrFail($id), 'Kategori');
    }

    public function storeItem(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin menambah master aset. Hubungi Master Admin.')) {
            return $resp;
        }

        $validated = $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'urutan'      => 'required|integer|min:1',
            'nama_aset'   => 'required|string|max:150|unique:items,nama_aset',
            'awal_serial' => 'nullable|string|max:50',
            'warna_seri'  => 'nullable|string|max:20',
        ], [
            'nama_aset.unique' => 'Nama aset ini sudah terdaftar. Silakan gunakan nama lain.',
        ]);

        $validated['awal_serial'] = !empty($validated['awal_serial']) ? $validated['awal_serial'] : '-';
        $item = AdminItem::create($validated);
        $this->catatRiwayat('Menambah master aset baru: ' . $item->nama_aset, 'Master Aset', 'Berhasil');

        return $this->responSukses($request, 'Master aset berhasil disimpan!');
    }

    public function updateItem(Request $request, $id)
    {
        if ($resp = $this->cekIzin($request, 'edit', 'Anda tidak memiliki izin mengubah master aset. Hubungi Master Admin.')) {
            return $resp;
        }

        $item = AdminItem::findOrFail($id);
        $validated = $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'urutan'      => 'required|integer|min:1',
            'nama_aset'   => ['required', 'string', 'max:150', Rule::unique('items', 'nama_aset')->ignore($item->id)],
            'awal_serial' => 'nullable|string|max:50',
            'warna_seri'  => 'nullable|string|max:20',
        ], [
            'nama_aset.unique' => 'Nama aset ini sudah terdaftar. Silakan gunakan nama lain.',
        ]);

        $validated['awal_serial'] = !empty($validated['awal_serial']) ? $validated['awal_serial'] : '-';
        $item->update($validated);
        $this->catatRiwayat('Memperbarui master aset: ' . $item->nama_aset, 'Master Aset', 'Informasi');

        return $this->responSukses($request, 'Master aset berhasil diperbarui!');
    }

    public function destroyItem(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminItem::findOrFail($id), 'Master aset');
    }

    public function storeStatusAset(Request $request)
    {
        if ($resp = $this->cekIzin($request, 'create', 'Anda tidak memiliki izin menambah status aset. Hubungi Master Admin.')) {
            return $resp;
        }

        $validated = $request->validate([
            'nama_status'  => 'required|string|max:50',
            'warna_status' => 'nullable|string|max:20',
        ]);

        $st = AdminStatusAset::create($validated);
        $this->catatRiwayat('Menambah status aset baru: ' . $st->nama_status, 'Status Aset', 'Berhasil');

        return $this->responSukses($request, 'Status aset berhasil ditambahkan!');
    }

    public function updateStatusAset(Request $request, $id)
    {
        if ($resp = $this->cekIzin($request, 'edit', 'Anda tidak memiliki izin mengubah status aset. Hubungi Master Admin.')) {
            return $resp;
        }

        $status = AdminStatusAset::findOrFail($id);
        $validated = $request->validate([
            'nama_status'  => 'required|string|max:50',
            'warna_status' => 'nullable|string|max:20',
        ]);

        $status->update($validated);
        $this->catatRiwayat('Memperbarui status aset: ' . $status->nama_status, 'Status Aset', 'Informasi');

        return $this->responSukses($request, 'Status aset berhasil diperbarui!');
    }

    public function destroyStatusAset(Request $request, $id)
    {
        return $this->hapusMaster($request, AdminStatusAset::findOrFail($id), 'Status aset');
    }

    private function cekIzin(Request $request, string $aksi, string $pesan)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'kategori_aset', $aksi)) {
            return RolePermission::denyResponse($request, $pesan);
        }
        return null;
    }

    private function responSukses(Request $request, string $pesan)
    {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $pesan,
            ]);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Hapus data master dengan pengecekan izin "delete" modul kategori_aset
     * (diatur Master Admin di Roles & Permission) dan respons JSON/redirect.
     */
    private function hapusMaster(Request $request, $model, string $label)
    {
        $json = $request->ajax() || $request->wantsJson() || $request->expectsJson();

        if ($resp = $this->cekIzin($request, 'delete', 'Anda tidak memiliki izin menghapus data ini. Hubungi Master Admin.')) {
            return $resp;
        }

        try {
            $model->delete();
        } catch (QueryException $e) {
            $pesan = $label . ' tidak dapat dihapus karena masih dipakai oleh data lain.';
            return $json
                ? response()->json(['success' => false, 'message' => $pesan], 422)
                : back()->withErrors(['error' => $pesan]);
        }

        $this->catatRiwayat('Menghapus data ' . strtolower($label), 'Kategori Aset', 'Peringatan');
        $pesan = $label . ' berhasil dihapus.';
        return $json
            ? response()->json(['success' => true, 'message' => $pesan])
            : back()->with('success', $pesan);
    }

    private function catatRiwayat(string $aktivitas, string $modul, string $status): void
    {
        if (!class_exists(RiwayatAktivitas::class) || !Auth::check()) {
            return;
        }

        try {
            $riwayat = new RiwayatAktivitas();
            $table = $riwayat->getTable();
            $data = [];
            if (Schema::hasColumn($table, 'user_id')) {
                $data['user_id'] = Auth::id();
            }
            if (Schema::hasColumn($table, 'aktivitas')) {
                $data['aktivitas'] = $aktivitas;
            }
            if (Schema::hasColumn($table, 'modul')) {
                $data['modul'] = $modul;
            }
            if (Schema::hasColumn($table, 'status')) {
                $data['status'] = $status;
            }
            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    }
}
