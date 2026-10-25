<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminUser;
use App\Models\AdminCabang;
use App\Models\AdminKategori;
use App\Models\AdminItem;
use App\Models\AdminDetailAset;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalPengguna = AdminUser::count();
        $jmlMasterAdmin = AdminUser::where('role', 'master_admin')->orWhere('role', 'Master Admin')->count();
        $jmlAdmin = AdminUser::where('role', 'admin')->orWhere('role', 'Admin')->count();
        $jmlUser = AdminUser::where('role', 'user')->orWhere('role', 'User')->count();

        $totalAset = AdminDetailAset::count();
        $totalCabang = AdminCabang::count();
        $totalKategori = AdminKategori::count();
        $totalMasterAset = AdminItem::count();
        
        $statusRusak = ['Perbaikan', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat', 'Rusak Parah'];
        $totalMendekatiServis = AdminDetailAset::whereIn('status_aset', $statusRusak)->count();

        $asetPerCabang = AdminDetailAset::with('cabang')
            ->select('cabang_id', \DB::raw('count(*) as total'))
            ->groupBy('cabang_id')
            ->get();

        $rawDetailServis = AdminDetailAset::with(['cabang', 'item'])->whereIn('status_aset', $statusRusak)->get();
        $servisDetail = [];
        foreach($rawDetailServis as $aset) {
            $namaCabang = $aset->cabang->nama_cabang ?? 'Tanpa Cabang';
            if(!isset($servisDetail[$namaCabang])) {
                $servisDetail[$namaCabang] = [];
            }
            $namaItem = $aset->item->nama_aset ?? 'Aset Tdk Diketahui';
            $servisDetail[$namaCabang][] = $namaItem . ' (' . ($aset->kode_aset ?? '-') . ') - ' . $aset->status_aset;
        }

        return view('admin.dashboard', compact(
            'totalPengguna', 'jmlMasterAdmin', 'jmlAdmin', 'jmlUser',
            'totalAset', 'totalCabang', 'totalKategori', 'totalMasterAset',
            'totalMendekatiServis', 'asetPerCabang', 'servisDetail'
        ));
    }
}