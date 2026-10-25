<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminCabang;

class AdminCabangController extends Controller
{
    public function index()
    {
        $cabangs = AdminCabang::withCount('asets')->get();
        return view('cabang', compact('cabangs'));
    }

    public function store(Request $request)
    {
        AdminCabang::create($request->validate([
            'nama_cabang' => 'required|string|max:100|unique:cabangs'
        ]));
        return back()->with('success', 'Cabang baru berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $cabang = AdminCabang::findOrFail($id);
        $cabang->update($request->validate([
            'nama_cabang' => 'required|string|max:100'
        ]));
        return back()->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $cabang = AdminCabang::withCount('asets')->findOrFail($id);
        if ($cabang->asets_count > 0) {
            return back()->withErrors(['error' => 'Cabang masih memiliki aset dan tidak bisa dihapus.']);
        }
        $cabang->delete();
        return back()->with('success', 'Cabang berhasil dihapus.');
    }
}