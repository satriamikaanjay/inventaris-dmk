<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminKategori;
use App\Models\AdminItem;
use App\Models\AdminStatusAset;

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

        return view('kategori', compact('kategoris', 'items', 'statuses'));
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

    public function destroyKategori($id)
    {
        AdminKategori::findOrFail($id)->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
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

    public function destroyItem($id)
    {
        AdminItem::findOrFail($id)->delete();
        return back()->with('success', 'Master aset berhasil dihapus.');
    }

    public function storeStatusAset(Request $request)
    {
        AdminStatusAset::create($request->validate([
            'nama_status' => 'required|string|max:50',
            'warna_status' => 'nullable|string|max:20',
        ]));
        return back()->with('success', 'Status aset berhasil ditambahkan.');
    }
}