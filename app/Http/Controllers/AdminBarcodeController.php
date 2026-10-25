<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminDetailAset;
use App\Models\AdminStatusAset;

class AdminBarcodeController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminDetailAset::with(['item', 'kategori', 'cabang']);
        
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where('kode_aset', 'like', "%{$keyword}%")
                  ->orWhere('nomor_seri', 'like', "%{$keyword}%");
        }

        if ($request->filled('filter_tipe') && $request->filter_tipe !== 'Semua') {
            $query->where('status_berjalan', $request->filter_tipe);
        }

        $detailAsets = $query->paginate(50)->withQueryString();
        $statuses = AdminStatusAset::all();

        return view('barcode', compact('detailAsets', 'statuses'));
    }

    public function generateSingleQr($id)
    {
        return response()->json(['success' => true, 'message' => 'QR Code berhasil dibuat.']);
    }

    public function generateAllQr()
    {
        return response()->json(['success' => true, 'message' => 'Semua QR Code berhasil diperbarui.']);
    }
}