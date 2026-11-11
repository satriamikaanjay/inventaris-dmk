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
                            ->select('cabang_id', \DB::raw('count(*) as total'))
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
    })->middleware('auth')->name('masteradmin.dashboard');

    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->middleware('auth')->name('notifikasi.index');

    Route::get('/barcode-aset', [\App\Http\Controllers\ItemController::class, 'barcodeIndex'])->middleware('auth')->name('barcode_aset.index');

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
    })->middleware('auth')->name('kategori_aset.index');

    Route::middleware('auth')->group(function () {
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
    })->middleware('auth')->name('laporan.index');

    Route::get('/riwayat-aktivitas', [App\Http\Controllers\RiwayatController::class, 'index'])->middleware('auth')->name('riwayat_aktivitas.index');
    Route::delete('/riwayat-aktivitas/clear', [App\Http\Controllers\RiwayatController::class, 'clear'])->middleware('auth')->name('riwayat_aktivitas.clear');

    Route::middleware(['auth'])->prefix('masteradmin')->group(function () {
        Route::get('/data-aset', [ItemController::class, 'index'])->name('aset.index');
        Route::post('/data-aset/import', [ItemController::class, 'import'])->name('aset.import');
        Route::delete('/data-aset/bulk-delete', [ItemController::class, 'bulkDelete'])->name('detailaset.bulkDelete');
        Route::get('/view-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'viewLampiran']);
        Route::get('/download-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'downloadLampiran']);
        Route::get('/download-lampiran/{filename}', [\App\Http\Controllers\ItemController::class, 'downloadLampiran']);
        
        Route::post('/generate-qr/{id}', [\App\Http\Controllers\ItemController::class, 'generateAndSaveQr'])->name('masteradmin.generate-qr');

        Route::post('/generate-qr-all', [\App\Http\Controllers\ItemController::class, 'generateSemuaQr'])->name('masteradmin.generate-qr-all');
        
        Route::post('/kategori/store', [KategoriController::class, 'store'])->name('kategori.store');
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

        Route::post('/data-aset/store', [ItemController::class, 'storeDetail'])->name('detailaset.store');
        
        Route::put('/data-aset/{id}', [ItemController::class, 'updateDetail'])->name('detailaset.update');
        Route::delete('/data-aset/{id}', [ItemController::class, 'destroyDetail'])->name('detailaset.destroy');
        
        Route::resource('items', ItemController::class);
    });

    Route::middleware(['auth'])->group(function () {
        Route::post('/masteradmin/status-aset', [StatusAsetController::class, 'store'])->name('status-aset.store');
        Route::put('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'update']);
        Route::delete('/masteradmin/status-aset/{id}', [StatusAsetController::class, 'destroy']);
        Route::post('/masteradmin/data-aset/import', [\App\Http\Controllers\ItemController::class, 'importExcel'])->name('aset.import');
    });

    Route::get('/', [AuthController::class, 'index'])->name('login');
    Route::get('/login', [AuthController::class, 'index'])->name('login.page');
    Route::post('/login/proses', [AuthController::class, 'login'])
        ->middleware('login.attempt')
        ->name('login.proses');

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
                    
    Route::get('/admin-dashboard', [AdminDashboardController::class, 'index'])->middleware('auth')->name('admin.dashboard');

    Route::middleware(['auth'])->prefix('admin')->group(function () {
        
        Route::get('/', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::get('/data-aset', [AdminAsetController::class, 'index'])->name('admin.aset.index');
        Route::post('/data-aset', [AdminAsetController::class, 'store'])->name('detailaset.store');
        Route::put('/data-aset/{id}', [AdminAsetController::class, 'update'])->name('detailaset.update');
        Route::delete('/data-aset/{id}', [AdminAsetController::class, 'destroy'])->name('detailaset.destroy');
        Route::post('/import-aset', [AdminAsetController::class, 'importExcel'])->name('aset.import');
        Route::get('/view-lampiran/{filename}', [AdminAsetController::class, 'viewLampiran']);
        Route::get('/download-lampiran/{filename}', [AdminAsetController::class, 'downloadLampiran']);

        Route::get('/barcode-aset', [AdminBarcodeController::class, 'index'])->name('admin.barcode.index');
        Route::post('/generate-qr/{id}', [AdminBarcodeController::class, 'generateSingleQr']);
        Route::post('/generate-qr-all', [AdminBarcodeController::class, 'generateAllQr']);

        Route::get('/master-kategori-aset', [AdminKategoriController::class, 'index'])->name('admin.kategori.index');
        Route::post('/kategori', [AdminKategoriController::class, 'storeKategori'])->name('kategori.store');
        Route::put('/kategori/{id}', [AdminKategoriController::class, 'updateKategori'])->name('kategori.update');
        Route::delete('/kategori/{id}', [AdminKategoriController::class, 'destroyKategori'])->name('kategori.destroy');

        Route::post('/items', [AdminKategoriController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{id}', [AdminKategoriController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{id}', [AdminKategoriController::class, 'destroyItem'])->name('items.destroy');

        Route::post('/status-aset', [AdminKategoriController::class, 'storeStatusAset'])->name('status-aset.store');
        Route::put('/status-aset/{id}', [AdminKategoriController::class, 'updateStatusAset'])->name('status-aset.update');
        Route::delete('/status-aset/{id}', [AdminKategoriController::class, 'destroyStatusAset'])->name('status-aset.destroy');

        Route::get('/master-cabang', [AdminCabangController::class, 'index'])->name('admin.cabang.index');
        Route::post('/cabang', [AdminCabangController::class, 'store'])->name('cabang.store');
        Route::put('/cabang/{id}', [AdminCabangController::class, 'update'])->name('cabang.update');
        Route::delete('/cabang/{id}', [AdminCabangController::class, 'destroy'])->name('cabang.destroy');

        Route::get('/notifikasi', [AdminNotifikasiController::class, 'index'])->name('admin.notifikasi.index');

        Route::get('/riwayat-aktivitas', [AdminRiwayatController::class, 'index'])->name('admin.riwayat_aktivitas.index');
        Route::delete('/riwayat-aktivitas/clear', [AdminRiwayatController::class, 'clear'])->name('riwayat_aktivitas.clear');

        Route::get('/master-lokasi', [AdminLokasiController::class, 'index'])->name('lokasi.index');
        Route::post('/master-lokasi', [AdminLokasiController::class, 'store'])->name('lokasi.store');
        Route::put('/master-lokasi/{id}', [AdminLokasiController::class, 'update'])->name('lokasi.update');
        Route::delete('/master-lokasi/{id}', [AdminLokasiController::class, 'destroy'])->name('lokasi.destroy');

        Route::get('/divisi', [AdminDivisiController::class, 'index'])->name('divisi.index');
        Route::get('/jabatan', [AdminJabatanController::class, 'index'])->name('jabatan.index');
        Route::get('/laporan', [AdminLaporanController::class, 'index'])->name('admin.laporan.index');
    });
    \Illuminate\Support\Facades\Route::middleware(['auth'])->get('/pengajuan', function () {
        if (view()->exists('pengajuan')) {
            return view('pengajuan');
        }
        if (view()->exists('admin.pengajuan')) {
            return view('admin.pengajuan');
        }
        return redirect()->to(url('/admin-dashboard?pengajuan=' . urlencode((string) request('jenis', 'peminjaman'))));
    })->name('pengajuan.index');
