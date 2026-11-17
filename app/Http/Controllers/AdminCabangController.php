<?php

namespace App\Http\Controllers;

use App\Models\AdminCabang;
use App\Models\RiwayatAktivitas;
use App\Support\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminCabangController extends Controller
{
    public function index()
    {
        $cabangs = AdminCabang::withCount('asets')->orderBy('nama_cabang')->get();
        return view('admin.cabang', compact('cabangs'));
    }

    public function store(Request $request)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'cabang', 'create')) {
            return RolePermission::denyResponse($request, 'Anda tidak memiliki izin menambah Cabang. Hubungi Master Admin.');
        }

        $this->rapikanInput($request);
        $validated = $request->validate([
            'nama_cabang' => ['required', 'string', 'max:100', 'unique:cabangs,nama_cabang'],
        ], $this->pesan());

        $cabang = AdminCabang::create($validated);
        $this->catatRiwayat('Menambah cabang baru: ' . $cabang->nama_cabang, 'Berhasil');

        $msg = 'Cabang "' . $cabang->nama_cabang . '" berhasil ditambahkan.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, $id)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'cabang', 'edit')) {
            return RolePermission::denyResponse($request, 'Anda tidak memiliki izin mengubah Cabang. Hubungi Master Admin.');
        }

        $cabang = AdminCabang::findOrFail($id);
        $this->rapikanInput($request);

        $validated = $request->validate([
            'nama_cabang' => [
                'required', 'string', 'max:100',
                Rule::unique('cabangs', 'nama_cabang')->ignore($cabang->id),
            ],
        ], $this->pesan());

        $cabang->update($validated);
        $this->catatRiwayat('Memperbarui cabang: ' . $cabang->nama_cabang, 'Informasi');

        $msg = 'Cabang berhasil diperbarui.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, $id)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'cabang', 'delete')) {
            return RolePermission::denyResponse($request, 'Anda tidak memiliki izin menghapus Cabang. Hubungi Master Admin.');
        }

        $cabang = AdminCabang::withCount('asets')->findOrFail($id);
        if ($cabang->asets_count > 0) {
            $err = 'Cabang "' . $cabang->nama_cabang . '" masih memiliki ' . $cabang->asets_count . ' aset dan tidak bisa dihapus.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            return back()->withErrors(['error' => $err]);
        }

        $nama = $cabang->nama_cabang;
        $cabang->delete();
        $this->catatRiwayat('Menghapus cabang: ' . $nama, 'Peringatan');

        $msg = 'Cabang berhasil dihapus.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    private function rapikanInput(Request $request): void
    {
        $request->merge([
            'nama_cabang' => trim(preg_replace('/\s+/', ' ', (string) $request->input('nama_cabang'))),
        ]);
    }

    private function pesan(): array
    {
        return [
            'nama_cabang.required' => 'Nama cabang wajib diisi.',
            'nama_cabang.unique'   => 'Nama cabang sudah ada.',
            'nama_cabang.max'      => 'Nama cabang maksimal 100 karakter.',
        ];
    }

    private function catatRiwayat(string $aktivitas, string $status): void
    {
        if (!class_exists(RiwayatAktivitas::class) || !Auth::check()) {
            return;
        }
        try {
            $log = new RiwayatAktivitas();
            $tbl = $log->getTable();
            $payload = [];
            if (Schema::hasColumn($tbl, 'user_id')) {
                $payload['user_id'] = Auth::id();
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $payload['aktivitas'] = $aktivitas;
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $payload['modul'] = 'Cabang';
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $payload['status'] = $status;
            }
            $log->forceFill($payload)->save();
        } catch (\Throwable $e) {
        }
    }
}
