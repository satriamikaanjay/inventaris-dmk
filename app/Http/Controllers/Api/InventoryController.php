<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DetailAset;
use App\Models\Kategori;
use App\Models\Cabang;

class InventoryController extends Controller
{
    // 1. DATA INVENTARIS & DETAIL BARANG
    public function index()
    {
        try {
            $asetList = DetailAset::with(['item', 'kategori', 'cabang'])->get();
            
            $mappedAset = $asetList->map(function ($data) {
                return [
                    // INI HARUS SAMA PERSIS DENGAN @SerializedName DI ANDROID
                    'id'                => $data->aset_id, // Android minta "id", bukan "aset_id"
                    'kode_aset'         => $data->kode_aset,
                    'nama_aset'         => $data->item ? $data->item->nama_aset : '-',
                    'kategori'          => $data->kategori ? $data->kategori->nama_kategori : '-',
                    'kategori_id'       => $data->kategori_id,
                    'status'            => $data->status_aset,
                    'status_raw'        => $data->status_aset,
                    'lokasi'            => $data->lokasi_saat_ini ?: ($data->Ruangan ?: '-'), // Menjawab detail lokasi yang kosong
                    'unit'              => $data->unit ?: 0,
                    
                    // Menggabungkan merk dan nomor seri untuk mengisi kolom "spesifikasi"
                    'spesifikasi'       => ($data->merk ?: '-') . ' | Seri: ' . ($data->nomor_seri ?: '-'),
                    'merk'              => $data->merk ?: '-',
                    'nomor_seri'        => $data->nomor_seri ?: '-',
                    
                    'cabang'            => $data->cabang ? $data->cabang->nama_cabang : '-',
                    'cabang_id'         => $data->cabang_id,
                    'penanggung_jawab'  => $data->penanggung_jawab ?: '-',
                    'tanggal_pengadaan' => $data->tanggal_pengadaan ?: '-',
                    'harga_beli'        => $data->harga_beli ? (float) $data->harga_beli : 0.0,
                ];
            });
            
            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil diambil',
                'total'   => $mappedAset->count(), // Menyesuaikan InventoryListResponse
                'data'    => $mappedAset
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'total'   => 0,
                'data'    => []
            ], 500);
        }
    }

    // 2. DATA FILTER KATEGORI
    public function categories()
    {
        try {
            // Android meminta List<CategoryItem> yang punya id dan nama_kategori
            $kategori = Kategori::select('id', 'nama_kategori')->get();
            
            return response()->json([
                'status' => 'success',
                'data'   => $kategori
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'data' => []], 500);
        }
    }

    // 3. DATA FILTER CABANG
    public function branches()
    {
        try {
            // Android meminta List<BranchItem> yang punya id dan nama_cabang
            $cabang = Cabang::select('id', 'nama_cabang')->get();
            
            return response()->json([
                'status' => 'success',
                'data'   => $cabang
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'data' => []], 500);
        }
    }
}