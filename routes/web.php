<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminPengajuanController;
use App\Http\Controllers\DeveloperController;
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
    use App\Http\Controllers\KategoriController;
    use App\Http\Controllers\CabangController;
    use App\Http\Controllers\StatusAsetController;
    use App\Http\Controllers\KelolaAkunController;
    use App\Http\Controllers\NotifikasiController;
    use App\Http\Controllers\TrackingPengadaanController;
    use App\Http\Controllers\LiveSyncController;

    Route::get('/cek-sesi', fn () => response()->json(['aktif' => \Illuminate\Support\Facades\Auth::check()], \Illuminate\Support\Facades\Auth::check() ? 200 : 401))->name('cek_sesi');
    Route::middleware(['auth'])->get('/_live-sync/version', [LiveSyncController::class, 'version'])->name('live_sync.version');

    Route::get('/', [AuthController::class, 'index'])->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('login.attempt')
        ->name('login.proses');

    Route::get('/masteradmin', function () {
        $totalPengguna  = \App\Models\User::count();
        $jmlMasterAdmin = \App\Models\User::where('role', 'master_admin')->orWhere('role', 'Master Admin')->count();
        $jmlAdmin       = \App\Models\User::where('role', 'admin')->orWhere('role', 'Admin')->count();
        $jmlUser        = \App\Models\User::where('role', 'user')->orWhere('role', 'User')->count();

        $totalAset     = \App\Models\DetailAset::count();
        $asetPerCabang = \App\Models\DetailAset::with('cabang')
                            ->select('cabang_id', DB::raw('count(*) as total'))
                            ->groupBy('cabang_id')
                            ->get();

        $totalCabang = \App\Models\Cabang::count();

        $statusRusak = ['Perbaikan', 'Rusak Ringan', 'Rusak Sedang', 'Rusak Berat', 'Rusak Parah'];
        $totalMendekatiServis = \App\Models\DetailAset::whereIn('status_aset', $statusRusak)->count();

        $rawDetailServis = \App\Models\DetailAset::with(['cabang', 'item'])->whereIn('status_aset', $statusRusak)->get();

        $servisDetail = [];
        foreach($rawDetailServis as $aset) {
            $namaCabang = $aset->cabang->nama_cabang ?? 'Tanpa Cabang';
            if(!isset($servisDetail[$namaCabang])) {
                $servisDetail[$namaCabang] = [];
            }
            $namaItem = $aset->item->nama_aset ?? 'Aset Tdk Diketahui';
            $servisDetail[$namaCabang][] = $namaItem . ' (' . ($aset->kode_aset ?? '-') . ') - ' . $aset->status_aset;
        }

        $totalKategori   = \App\Models\Kategori::count();
        $totalMasterAset = \App\Models\Item::count();

        return view('masteradmin.dashboard', compact(
            'totalPengguna', 'jmlMasterAdmin', 'jmlAdmin', 'jmlUser',
            'totalAset', 'asetPerCabang',
            'totalCabang',
            'totalMendekatiServis', 'servisDetail',
            'totalKategori', 'totalMasterAset'
        ));
    })->middleware(['auth', 'role:master_admin'])->name('masteradmin.dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->middleware(['auth', 'role:master_admin'])->name('notifikasi.index');

    Route::get('/barcode-aset', [\App\Http\Controllers\ItemController::class, 'barcodeIndex'])->middleware(['auth', 'role:master_admin'])->name('barcode_aset.index');

    Route::get('/kategori-aset', function (Illuminate\Http\Request $request) {

        $kategoris = \App\Models\Kategori::paginate(10, ['*'], 'kategori_page')->withQueryString();

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

        $statuses = \App\Models\StatusAset::all();

        return view('masteradmin.kategori', compact('kategoris', 'items', 'statuses'));
    })->middleware(['auth', 'role:master_admin'])->name('kategori_aset.index');

    Route::middleware(['auth', 'role:master_admin'])->group(function () {
        Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
        Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
        Route::put('/cabang/{id}', [CabangController::class, 'update'])->name('cabang.update');
        Route::delete('/cabang/{id}', [CabangController::class, 'destroy'])->name('cabang.destroy');
    });

    Route::middleware(['auth', 'role:master_admin'])->group(function () {
        Route::get('/kelola-akun', [KelolaAkunController::class, 'index'])->name('kelola_akun.index');
        Route::post('/kelola-akun', [KelolaAkunController::class, 'store'])->name('kelola_akun.store');
        Route::put('/kelola-akun/{id}', [KelolaAkunController::class, 'update'])->name('kelola_akun.update');
        Route::delete('/kelola-akun/{id}', [KelolaAkunController::class, 'destroy'])->name('kelola_akun.destroy');
    });

    Route::get('/roles', function () {
        return view('masteradmin.roles');
    })->middleware(['auth', 'role:master_admin'])->name('roles.index');

    Route::get('/laporan', function () {
        return view('masteradmin.laporan');
    })->middleware(['auth', 'role:master_admin'])->name('laporan.index');

    Route::get('/riwayat-aktivitas', [App\Http\Controllers\RiwayatController::class, 'index'])->middleware(['auth', 'role:master_admin'])->name('riwayat_aktivitas.index');
    Route::delete('/riwayat-aktivitas/clear', [App\Http\Controllers\RiwayatController::class, 'clear'])->middleware(['auth', 'role:master_admin'])->name('riwayat_aktivitas.clear');

    Route::middleware(['auth', 'role:master_admin'])->prefix('masteradmin')->group(function () {
        Route::get('/data-aset', [ItemController::class, 'index'])->name('aset.index');
        Route::get('/tracking-pengadaan', [TrackingPengadaanController::class, 'index'])->name('tracking_pengadaan.index');
        Route::delete('/data-aset/bulk-delete', [ItemController::class, 'bulkDelete'])->name('detailaset.bulkDelete');
        Route::get('/view-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'viewLampiran']);
        Route::get('/download-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'downloadLampiran']);

        Route::post('/generate-qr/{id}', [\App\Http\Controllers\ItemController::class, 'generateAndSaveQr'])->name('masteradmin.generate-qr');

        Route::post('/generate-qr-all', [\App\Http\Controllers\ItemController::class, 'generateSemuaQr'])->name('masteradmin.generate-qr-all');

        Route::post('/kategori/store', [KategoriController::class, 'store'])->name('kategori.store');
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

        Route::post('/data-aset/store', [ItemController::class, 'storeDetail'])->name('detailaset.store');

        Route::put('/data-aset/{id}', [ItemController::class, 'updateDetail'])->name('detailaset.update');
        Route::delete('/data-aset/{id}', [ItemController::class, 'destroyDetail'])->name('detailaset.destroy');

        Route::resource('items', ItemController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware(['auth', 'role:master_admin'])->group(function () {
        Route::post('/masteradmin/status-aset', [StatusAsetController::class, 'store'])->name('status-aset.store');
        Route::put('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'update']);
        Route::delete('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'destroy']);
        Route::post('/masteradmin/data-aset/import', [\App\Http\Controllers\ItemController::class, 'importExcel'])->name('aset.import');
        Route::get('/masteradmin/data-aset/template-import/{format?}', [\App\Http\Controllers\ItemController::class, 'downloadTemplateImport'])->name('aset.import.template');
    });

    Route::get('/', [AuthController::class, 'index'])->name('login');
    Route::get('/login', [AuthController::class, 'index'])->name('login.page');
    Route::post('/login/proses', [AuthController::class, 'login'])
        ->middleware('login.attempt')
        ->name('login.proses');

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::get('/admin-dashboard', [AdminDashboardController::class, 'index'])->middleware(['auth', 'role:admin'])->name('admin.dashboard');

    Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::get('/data-aset', [AdminAsetController::class, 'index'])->name('admin.aset.index');
        Route::delete('/data-aset/bulk-delete', [AdminAsetController::class, 'bulkDelete'])->name('admin.detailaset.bulkDelete');
        Route::post('/data-aset', [AdminAsetController::class, 'store'])->name('admin.detailaset.store');
        Route::put('/data-aset/{id}', [AdminAsetController::class, 'update'])->name('admin.detailaset.update');
        Route::delete('/data-aset/{id}', [AdminAsetController::class, 'destroy'])->name('admin.detailaset.destroy');
        Route::post('/import-aset', [AdminAsetController::class, 'importExcel'])->name('admin.aset.import');
        Route::get('/import-aset/template/{format?}', [AdminAsetController::class, 'downloadTemplateImport'])->name('admin.aset.import.template');
        Route::get('/view-lampiran/{filename}', [AdminAsetController::class, 'viewLampiran']);
        Route::get('/download-lampiran/{filename}', [AdminAsetController::class, 'downloadLampiran']);

        Route::get('/barcode-aset', [AdminBarcodeController::class, 'index'])->name('admin.barcode.index');
        Route::get('/tracking-pengadaan', [TrackingPengadaanController::class, 'index'])->name('admin.tracking_pengadaan.index');
        Route::post('/generate-qr/{id}', [AdminBarcodeController::class, 'generateSingleQr']);
        Route::post('/generate-qr-all', [AdminBarcodeController::class, 'generateAllQr']);

        Route::get('/master-kategori-aset', [AdminKategoriController::class, 'index'])->name('admin.kategori.index');
        Route::post('/kategori', [AdminKategoriController::class, 'storeKategori'])->name('admin.kategori.store');
        Route::put('/kategori/{id}', [AdminKategoriController::class, 'updateKategori'])->name('admin.kategori.update');
        Route::delete('/kategori/{id}', [AdminKategoriController::class, 'destroyKategori'])->name('admin.kategori.destroy');

        Route::post('/items', [AdminKategoriController::class, 'storeItem'])->name('admin.items.store');
        Route::put('/items/{id}', [AdminKategoriController::class, 'updateItem'])->name('admin.items.update');
        Route::delete('/items/{id}', [AdminKategoriController::class, 'destroyItem'])->name('admin.items.destroy');

        Route::post('/status-aset', [AdminKategoriController::class, 'storeStatusAset'])->name('admin.status-aset.store');
        Route::put('/status-aset/{id}', [AdminKategoriController::class, 'updateStatusAset'])->name('admin.status-aset.update');
        Route::delete('/status-aset/{id}', [AdminKategoriController::class, 'destroyStatusAset'])->name('admin.status-aset.destroy');

        Route::get('/master-cabang', [AdminCabangController::class, 'index'])->name('admin.cabang.index');
        Route::post('/cabang', [AdminCabangController::class, 'store'])->name('admin.cabang.store');
        Route::put('/cabang/{id}', [AdminCabangController::class, 'update'])->name('admin.cabang.update');
        Route::delete('/cabang/{id}', [AdminCabangController::class, 'destroy'])->name('admin.cabang.destroy');

        Route::get('/notifikasi', [AdminNotifikasiController::class, 'index'])->name('admin.notifikasi.index');

        Route::get('/riwayat-aktivitas', [AdminRiwayatController::class, 'index'])->name('admin.riwayat_aktivitas.index');
        Route::delete('/riwayat-aktivitas/clear', [AdminRiwayatController::class, 'clear'])->name('admin.riwayat_aktivitas.clear');

        Route::get('/master-lokasi', [AdminLokasiController::class, 'index'])->name('lokasi.index');
        Route::post('/master-lokasi', [AdminLokasiController::class, 'store'])->name('lokasi.store');
        Route::put('/master-lokasi/{id}', [AdminLokasiController::class, 'update'])->name('lokasi.update');
        Route::delete('/master-lokasi/{id}', [AdminLokasiController::class, 'destroy'])->name('lokasi.destroy');

        Route::get('/divisi', [AdminDivisiController::class, 'index'])->name('divisi.index');
        Route::get('/jabatan', [AdminJabatanController::class, 'index'])->name('jabatan.index');
        Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('admin.laporan.index');

        Route::get('/pengajuan', [AdminPengajuanController::class, 'index'])->name('admin.pengajuan.index');
        Route::post('/pengajuan/{id}/status', [AdminPengajuanController::class, 'updateStatus'])->name('admin.pengajuan.status');
        Route::get('/pengajuan/lampiran/{filename}', [AdminPengajuanController::class, 'viewLampiran'])->name('admin.pengajuan.lampiran');
    });
    Route::middleware(['auth', 'role:admin,master_admin'])->get('/pengajuan', [AdminPengajuanController::class, 'index'])->name('pengajuan.index');
    Route::middleware(['auth', 'role:admin,master_admin'])->post('/pengajuan/{id}/status', [AdminPengajuanController::class, 'updateStatus'])->name('pengajuan.status');
    Route::middleware(['auth', 'role:admin,master_admin'])->get('/pengajuan/lampiran/{filename}', [AdminPengajuanController::class, 'viewLampiran'])->name('pengajuan.lampiran');

    Route::get('/dev-console/login', [DeveloperController::class, 'showLogin'])->name('developer.login');
    Route::post('/dev-console/login', [DeveloperController::class, 'login'])->middleware('throttle:15,1')->name('developer.login.proses');
    Route::middleware(['auth', 'role:developer', 'throttle:240,1'])->prefix('dev-console')->group(function () {
        Route::get('/', [DeveloperController::class, 'index'])->name('developer.dashboard');
        Route::get('/aktivitas-akun', [DeveloperController::class, 'aktivitasAkun'])->name('developer.aktivitas');
        Route::get('/struktur-folder', [DeveloperController::class, 'strukturFolder'])->name('developer.struktur');
        Route::get('/struktur-folder/tree', [DeveloperController::class, 'strukturTree'])->name('developer.struktur.tree');
        Route::get('/struktur-folder/file', [DeveloperController::class, 'strukturFile'])->name('developer.struktur.file');
        Route::post('/struktur-folder/baseline', [DeveloperController::class, 'simpanBaselineFolder'])->name('developer.struktur.baseline');
        Route::post('/kunci-web', [DeveloperController::class, 'toggleKunciWeb'])->name('developer.kunci_web');
        Route::post('/reset-master-password', [DeveloperController::class, 'resetMasterPassword'])->name('developer.reset_master_password');
        Route::post('/buka-blokir-ip', [DeveloperController::class, 'unblockIp'])->name('developer.unblock_ip');
        Route::post('/buka-semua-blokir', [DeveloperController::class, 'unblockAllIps'])->name('developer.unblock_all_ips');
        Route::post('/logout', [DeveloperController::class, 'logout'])->name('developer.logout');
    });
