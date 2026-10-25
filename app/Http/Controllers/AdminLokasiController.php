<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminLokasi;

class AdminLokasiController extends Controller
{
    public function index()
    {
        $lokasis = AdminLokasi::all();
        return view('lokasi', compact('lokasis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lokasi' => 'required|string|max:100',
            'kode_lokasi' => 'required|string|max:20|unique:lokasis'
        ]);

        AdminLokasi::create($data);
        return response()->json(['success' => true, 'message' => 'Lokasi baru berhasil disimpan.']);
    }

    public function update(Request $request, $id)
    {
        $lokasi = AdminLokasi::findOrFail($id);
        $data = $request->validate([
            'nama_lokasi' => 'required|string|max:100',
            'kode_lokasi' => 'required|string|max:20|unique:lokasis,kode_lokasi,' . $id
        ]);

        $lokasi->update($data);
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil diperbarui.']);
    }

    public function destroy($id)
    {
        $lokasi = AdminLokasi::findOrFail($id);
        $lokasi->delete();
        return response()->json(['success' => true, 'message' => 'Lokasi berhasil dihapus.']);
    }
}