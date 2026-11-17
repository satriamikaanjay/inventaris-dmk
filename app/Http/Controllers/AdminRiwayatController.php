<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiwayatAktivitas;
use App\Support\RolePermission;
use Illuminate\Support\Facades\Auth;

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

        return view('admin.riwayat', compact('riwayats', 'tahunList'));
    }

    public function clear(Request $request)
    {
        $role = Auth::check() ? Auth::user()->role : null;
        if (!RolePermission::can($role, 'riwayat_aktivitas', 'delete')) {
            return RolePermission::denyResponse($request, 'Anda tidak memiliki izin membersihkan log riwayat aktivitas.');
        }

        if (RolePermission::isMasterAdmin($role)) {
            RiwayatAktivitas::query()->delete();
            $pesan = 'Seluruh log riwayat aktivitas berhasil dibersihkan.';
        } else {
            RiwayatAktivitas::where('user_id', Auth::id())->delete();
            $pesan = 'Log riwayat aktivitas akun Anda berhasil dibersihkan.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $pesan]);
        }

        return back()->with('success', $pesan);
    }
}