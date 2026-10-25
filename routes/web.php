<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminAsetController;
use App\Http\Controllers\AdminBarcodeController;
use App\Http\Controllers\AdminKategoriController;
use App\Http\Controllers\AdminCabangController;
use App\Http\Controllers\AdminNotifikasiController;
use App\Http\Controllers\AdminRiwayatController;
use App\Http\Controllers\AdminLokasiController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDivisiController;
use App\Http\Controllers\AdminJabatanController;
use App\Http\Controllers\AdminLaporanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\StatusAsetController;
use App\Http\Controllers\KelolaAkunController;
use App\Http\Controllers\NotifikasiController;

// 1. Halaman Utama langsung membuka Form Login
Route::get('/', [AuthController::class, 'index'])->name('login');

// 2. Rute untuk memproses form saat tombol ditekan
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');

// 3. Rute Halaman Master Admin (Setelah berhasil login)
Route::get('/masteradmin', function () {
    // 1. Data Pengguna
    $totalPengguna  = \App\Models\User::count();
    $jmlMasterAdmin = \App\Models\User::where('role', 'master_admin')->orWhere('role', 'Master Admin')->count();
    $jmlAdmin       = \App\Models\User::where('role', 'admin')->orWhere('role', 'Admin')->count();
    $jmlUser        = \App\Models\User::where('role', 'user')->orWhere('role', 'User')->count();

    // 2. Data Aset Fisik
    $totalAset     = \App\Models\DetailAset::count();
    $asetPerCabang = \App\Models\DetailAset::with('cabang')
                        ->select('cabang_id', \DB::raw('count(*) as total'))
                        ->groupBy('cabang_id')
                        ->get();

    // 3. Data Cabang
    $totalCabang = \App\Models\Cabang::count();

    // 4. Data Maintenance / Mendekati Servis
    // (Misal kita ambil aset yang statusnya 'Perbaikan' atau 'Rusak Ringan')
    $statusRusak = ['Perbaikan', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat', 'Rusak Parah'];
    $totalMendekatiServis = \App\Models\DetailAset::whereIn('status_aset', $statusRusak)->count();
    
    // Ambil detail aset yang rusak lalu kelompokkan per cabang
    $rawDetailServis = \App\Models\DetailAset::with(['cabang', 'item'])->whereIn('status_aset', $statusRusak)->get();
                        
    $servisDetail = [];
    foreach($rawDetailServis as $aset) {
        $namaCabang = $aset->cabang->nama_cabang ?? 'Tanpa Cabang';
        if(!isset($servisDetail[$namaCabang])) {
            $servisDetail[$namaCabang] = [];
        }
        $namaItem = $aset->item->nama_aset ?? 'Aset Tdk Diketahui';
        // Format: NAMA ASET (KODE) - STATUS
        $servisDetail[$namaCabang][] = $namaItem . ' (' . ($aset->kode_aset ?? '-') . ') - ' . $aset->status_aset;
    }

    // 5. Data Kategori & Master Aset
    $totalKategori   = \App\Models\Kategori::count();
    $totalMasterAset = \App\Models\Item::count();

    // PASTIKAN memanggil 'servisDetail' di compact
    return view('masteradmin.dashboard', compact(
        'totalPengguna', 'jmlMasterAdmin', 'jmlAdmin', 'jmlUser',
        'totalAset', 'asetPerCabang',
        'totalCabang',
        'totalMendekatiServis', 'servisDetail',
        'totalKategori', 'totalMasterAset'
    ));
})->middleware('auth')->name('masteradmin.dashboard');

// Rute untuk proses logout
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

Route::get('/notifikasi', [NotifikasiController::class, 'index'])->middleware('auth')->name('notifikasi.index');

Route::get('/barcode-aset', [\App\Http\Controllers\ItemController::class, 'barcodeIndex'])->middleware('auth')->name('barcode_aset.index');

Route::get('/kategori-aset', function (Illuminate\Http\Request $request) {
    
    // 1. KATEGORI: Tampil biasa dengan paginasi (TANPA EFEK PENCARIAN)
    $kategoris = \App\Models\Kategori::paginate(10, ['*'], 'kategori_page')->withQueryString();

    // 2. MASTER ASET: Logika Pencarian Aktif HANYA di Sini
    $queryItem = \App\Models\Item::query();
    
    if ($request->filled('search')) {
        $search = $request->search;
        $queryItem->where('nama_aset', 'like', "%{$search}%")
                  ->orWhere('awal_serial', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function($q) use ($search) {
                      $q->where('nama_kategori', 'like', "%{$search}%");
                  });
    }
    
    $items = $queryItem->paginate(10, ['*'], 'item_page')->withQueryString();

    // 3. STATUS ASET: Tampil semua (TANPA EFEK PENCARIAN)
    $statuses = \App\Models\StatusAset::all(); 

    return view('masteradmin.kategori', compact('kategoris', 'items', 'statuses'));
})->name('kategori_aset.index');


// Master Cabang
Route::middleware('auth')->group(function () {
    Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
    Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
    Route::put('/cabang/{id}', [CabangController::class, 'update'])->name('cabang.update');
    Route::delete('/cabang/{id}', [CabangController::class, 'destroy'])->name('cabang.destroy');
});

Route::get('/divisi', function () {
    return view('masteradmin.divisi'); // Mengarahkan ke file views/masteradmin/divisi.blade.php
})->middleware('auth')->name('divisi.index');

Route::get('/jabatan', function () {
    return view('masteradmin.jabatan'); // Mengarahkan ke file views/masteradmin/jabatan.blade.php
})->middleware('auth')->name('jabatan.index');

Route::middleware('auth')->group(function () {
    Route::get('/kelola-akun', [KelolaAkunController::class, 'index'])->name('kelola_akun.index');
    Route::post('/kelola-akun', [KelolaAkunController::class, 'store'])->name('kelola_akun.store');
    Route::put('/kelola-akun/{id}', [KelolaAkunController::class, 'update'])->name('kelola_akun.update');
    Route::delete('/kelola-akun/{id}', [KelolaAkunController::class, 'destroy'])->name('kelola_akun.destroy');
});

Route::get('/roles', function () {
    return view('masteradmin.roles'); // Mengarahkan ke file views/masteradmin/roles.blade.php
})->middleware('auth')->name('roles.index');

Route::get('/laporan', function () {
    return view('masteradmin.laporan'); // Mengarahkan ke file views/masteradmin/laporan.blade.php
})->middleware('auth')->name('laporan.index');

Route::get('/riwayat-aktivitas', [App\Http\Controllers\RiwayatController::class, 'index'])->middleware('auth')->name('riwayat_aktivitas.index');
Route::delete('/riwayat-aktivitas/clear', [App\Http\Controllers\RiwayatController::class, 'clear'])->middleware('auth')->name('riwayat_aktivitas.clear');

Route::middleware(['auth'])->prefix('masteradmin')->group(function () {
    Route::get('/data-aset', [ItemController::class, 'index'])->name('aset.index');
    Route::post('/data-aset/import', [ItemController::class, 'import'])->name('aset.import');
    Route::get('/view-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'viewLampiran']);
    Route::get('/download-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'downloadLampiran']);
    Route::get('/download-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'downloadLampiran']);
    
    // ---> HAPUS KATA /masteradmin DI DEPANNYA <---
    Route::post('/generate-qr/{id}', [\App\Http\Controllers\ItemController::class, 'generateAndSaveQr'])->name('masteradmin.generate-qr');

    Route::post('/generate-qr-all', [\App\Http\Controllers\ItemController::class, 'generateSemuaQr'])->name('masteradmin.generate-qr-all');
    
    // Rute Kategori
    Route::post('/kategori/store', [KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{id}', [KategoriController::class, 'update']);
    Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

    Route::post('/data-aset/store', [ItemController::class, 'storeDetail'])->name('detailaset.store');
    
    // RUTE EDIT & HAPUS DETAIL ASET (KEMBALIKAN RUTE UPDATE YANG TERHAPUS)
    Route::put('/data-aset/{id}', [ItemController::class, 'updateDetail'])->name('detailaset.update');
    Route::delete('/data-aset/{id}', [ItemController::class, 'destroyDetail'])->name('detailaset.destroy');
    
    // -> PINDAHKAN ROUTE ITEMS KE DALAM SINI AGAR SESUAI DENGAN /masteradmin/items/... <-
    Route::resource('items', ItemController::class);
});

Route::post('/masteradmin/status-aset', [StatusAsetController::class, 'store'])->name('status-aset.store');
Route::put('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'update']);
Route::delete('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'destroy']);
Route::post('/masteradmin/data-aset/import', [\App\Http\Controllers\ItemController::class, 'importExcel'])->name('aset.import');


/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Inventaris Admin
|--------------------------------------------------------------------------
*/


// Halaman Login & Proses Login Utama
Route::get('/', [AdminAuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('/login/proses', [AdminAuthController::class, 'prosesLogin'])->name('login.proses');

// Rute untuk proses logout
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
                  
// Rute langsung /admin-dashboard untuk role admin
Route::get('/admin-dashboard', [AdminDashboardController::class, 'index'])->middleware('auth')->name('admin.dashboard');

// Grup Route untuk Admin (Prefix URL /admin)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    
    // 1. Dashboard Admin
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // 2. Data Aset Fisik & Import
    Route::get('/data-aset', [AdminAsetController::class, 'index'])->name('dataaset.index');
    Route::post('/data-aset', [AdminAsetController::class, 'store'])->name('detailaset.store');
    Route::put('/data-aset/{id}', [AdminAsetController::class, 'update'])->name('detailaset.update');
    Route::delete('/data-aset/{id}', [AdminAsetController::class, 'destroy'])->name('detailaset.destroy');
    Route::post('/import-aset', [AdminAsetController::class, 'importExcel'])->name('aset.import');
    Route::get('/view-lampiran/{filename}', [AdminAsetController::class, 'viewLampiran']);
    Route::get('/download-lampiran/{filename}', [AdminAsetController::class, 'downloadLampiran']);

    // 3. Barcode & QR Aset
    Route::get('/barcode-aset', [AdminBarcodeController::class, 'index'])->name('barcode.index');
    Route::post('/generate-qr/{id}', [AdminBarcodeController::class, 'generateSingleQr']);
    Route::post('/generate-qr-all', [AdminBarcodeController::class, 'generateAllQr']);

    // 4. Master Kategori & Aset
    Route::get('/master-kategori-aset', [AdminKategoriController::class, 'index'])->name('master.kategori');
    Route::post('/kategori', [AdminKategoriController::class, 'storeKategori'])->name('kategori.store');
    Route::put('/kategori/{id}', [AdminKategoriController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{id}', [AdminKategoriController::class, 'destroyKategori'])->name('kategori.destroy');

    Route::post('/items', [AdminKategoriController::class, 'storeItem'])->name('items.store');
    Route::put('/items/{id}', [AdminKategoriController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{id}', [AdminKategoriController::class, 'destroyItem'])->name('items.destroy');

    Route::post('/status-aset', [AdminKategoriController::class, 'storeStatusAset'])->name('status-aset.store');
    Route::put('/status-aset/{id}', [AdminKategoriController::class, 'updateStatusAset'])->name('status-aset.update');
    Route::delete('/status-aset/{id}', [AdminKategoriController::class, 'destroyStatusAset'])->name('status-aset.destroy');

    // 5. Master Cabang
    Route::get('/master-cabang', [AdminCabangController::class, 'index'])->name('cabang.index');
    Route::post('/cabang', [AdminCabangController::class, 'store'])->name('cabang.store');
    Route::put('/cabang/{id}', [AdminCabangController::class, 'update'])->name('cabang.update');
    Route::delete('/cabang/{id}', [AdminCabangController::class, 'destroy'])->name('cabang.destroy');

    // 6. Notifikasi Aset
    Route::get('/notifikasi', [AdminNotifikasiController::class, 'index'])->name('notifikasi.index');

    // 7. Riwayat Aktivitas
    Route::get('/riwayat-aktivitas', [AdminRiwayatController::class, 'index'])->name('riwayat_aktivitas.index');
    Route::delete('/riwayat-aktivitas/clear', [AdminRiwayatController::class, 'clear'])->name('riwayat_aktivitas.clear');

    // 8. Master Lokasi Ruangan
    Route::get('/master-lokasi', [AdminLokasiController::class, 'index'])->name('lokasi.index');
    Route::post('/master-lokasi', [AdminLokasiController::class, 'store'])->name('lokasi.store');
    Route::put('/master-lokasi/{id}', [AdminLokasiController::class, 'update'])->name('lokasi.update');
    Route::delete('/master-lokasi/{id}', [AdminLokasiController::class, 'destroy'])->name('lokasi.destroy');

    // 9. Halaman Pengembangan (Divisi, Jabatan, Laporan)
    Route::get('/divisi', [AdminDivisiController::class, 'index'])->name('divisi.index');
    Route::get('/jabatan', [AdminJabatanController::class, 'index'])->name('jabatan.index');
    Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('laporan.index');
});