<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;


class KategoriController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'kode_kategori' => 'required|string|max:50',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
            'kode_kategori' => $request->kode_kategori,
        ]);

        // JIKA REQUEST DARI AJAX (JAVASCRIPT)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori baru berhasil ditambahkan!'
            ]);
        }

        // JIKA BUKAN AJAX (CARA LAMA)
        return redirect()->back()->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        
        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'kode_kategori' => $request->kode_kategori,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
    }

    // Fungsi untuk memproses Hapus Kategori (Dukungan AJAX)
    public function destroy(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        $kategori->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }
}