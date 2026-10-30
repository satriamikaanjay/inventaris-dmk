<style>
    :root {
        --primary-color: #1a4f91;
        --secondary-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --bg-light: #f8fafc;
        --text-color: #000;
        --text-muted: rgba(0, 0, 0, 0.45);
        --border-color: rgba(0, 0, 0, 0.15);
        --sidebar-width: 270px;
    }

    /* Sidebar Layout */
    sidebar {
        width: var(--sidebar-width);
        background-color: #ffffff;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        overflow-y: auto;
        transition: transform 0.3s ease;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        padding: 20px;
        gap: 12px;
    }

    .brand-logo {
        background-color: var(--primary-color);
        color: #fff;
        font-family: 'Coda Caption', sans-serif;
        font-size: 24px;
        font-weight: 800;
        width: 48px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    .brand-text span {
        display: block;
    }

    .brand-text .sistem {
        font-weight: 800;
        font-size: 18px;
    }

    .brand-text .master-admin {
        font-size: 13px;
        color: var(--text-muted);
    }

    .sidebar-menu {
        padding: 10px 20px;
        flex-grow: 1;
    }

    .menu-section-title {
        font-size: 12px;
        font-weight: 700;
        color: rgba(0, 0, 0, 0.35);
        margin: 15px 0 8px 0;
        letter-spacing: 0.5px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        color: var(--text-muted);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 4px;
        transition: background 0.2s, color 0.2s;
    }

    .menu-item svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
        flex-shrink: 0;
    }

    .menu-item:hover, .menu-item.active {
        background-color: var(--primary-color);
        color: #fff;
    }

    /* Badge & Titik Merah Notifikasi Sidebar */
    .notif-badge {
        margin-left: auto;
        background-color: #ef4444;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.35);
        transition: transform 0.25s ease, opacity 0.25s ease;
    }

    .menu-item:hover .notif-badge,
    .menu-item.active .notif-badge {
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
    }

    .notif-dot {
        margin-left: auto;
        width: 10px;
        height: 10px;
        background-color: #ef4444;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
    }

    .menu-item:hover .notif-dot,
    .menu-item.active .notif-dot {
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
    }

    .sidebar-user {
        padding: 15px 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
        background: #fff;
    }

    .user-avatar {
        background-color: var(--primary-color);
        color: #fff;
        font-family: 'Coda Caption', sans-serif;
        font-size: 18px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .user-info {
        overflow: hidden;
    }

    .user-info .user-name {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-info .user-role {
        font-size: 12px;
        color: var(--text-muted);
    }

    /* Banner Pembatasan Hak Akses Real-Time */
    .perm-readonly-banner {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        color: #92400e;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);
    }

    .perm-revoked-overlay {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        bottom: 0;
        background: rgba(248, 250, 252, 0.96);
        backdrop-filter: blur(4px);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 25px;
    }

    .perm-revoked-card {
        background: #fff;
        border: 1px solid #fecaca;
        border-top: 5px solid #ef4444;
        border-radius: 18px;
        padding: 36px 40px;
        max-width: 480px;
        width: 100%;
        text-align: center;
        box-shadow: 0 15px 35px rgba(239, 68, 68, 0.12);
    }

    .perm-rt-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #0f172a;
        color: #fff;
        padding: 13px 18px;
        border-radius: 12px;
        border-left: 4px solid #38bdf8;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateY(120%);
        opacity: 0;
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .perm-rt-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    /* Menu Pengajuan & Sub-Button di Sidebar Admin */
    .menu-pengajuan-wrap {
        display: flex;
        flex-direction: column;
        margin-bottom: 4px;
    }

    .menu-item-btn {
        width: 100%;
        border: none;
        background: transparent;
        font-family: inherit;
        text-align: left;
        cursor: pointer;
    }

    .chevron-pengajuan {
        margin-left: auto;
        width: 18px !important;
        height: 18px !important;
        transition: transform 0.25s ease;
    }

    .menu-pengajuan-wrap.open .chevron-pengajuan {
        transform: rotate(180deg);
    }

    .submenu-pengajuan {
        display: none;
        flex-direction: column;
        gap: 6px;
        padding: 6px 0 6px 12px;
        margin: 4px 0 6px 18px;
        border-left: 2px dashed #cbd5e1;
        animation: fadeSubmenu 0.2s ease;
    }

    .menu-pengajuan-wrap.open .submenu-pengajuan {
        display: flex;
    }

    @keyframes fadeSubmenu {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .submenu-pengajuan-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 8px 11px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #334155;
        font-family: inherit;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        text-align: left;
        transition: all 0.2s ease;
    }

    .submenu-pengajuan-btn:hover,
    .submenu-pengajuan-btn.active {
        background: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 2px 6px rgba(26, 79, 145, 0.2);
    }

    .submenu-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        flex-shrink: 0;
    }

    /* Panel Halaman Pengajuan di Area Utama */
    .pengajuan-view-container {
        display: none;
        flex-direction: column;
        gap: 20px;
        width: 100%;
    }

    .pengajuan-view-container.active {
        display: flex;
    }

    .pengajuan-top-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 14px;
    }

    .btn-tab-pengajuan {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px 18px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #ffffff;
        color: #1e293b;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
    }

    .btn-tab-pengajuan:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    .btn-tab-pengajuan.active {
        background: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 6px 14px rgba(26, 79, 145, 0.22);
    }

    .pengajuan-content-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-top: 4px solid var(--primary-color);
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
    }

    @media (max-width: 768px) {
        sidebar {
            transform: translateX(-100%);
        }
        .perm-revoked-overlay {
            left: 0;
        }
    }
</style>

@php
    // Registrasi otomatis route /pengajuan di routes/web.php jika belum terdaftar
    try {
        $__webRoutePath = base_path('routes/web.php');
        if (is_file($__webRoutePath) && is_writable($__webRoutePath)) {
            $__isiWebRoute = (string) file_get_contents($__webRoutePath);
            if (!str_contains($__isiWebRoute, "'/pengajuan'") && !str_contains($__isiWebRoute, '"/pengajuan"')) {
                $__routePengajuanBaru = "\n// Route Halaman Pengajuan Aset Admin\n"
                    . "\Illuminate\Support\Facades\Route::middleware(['auth'])->get('/pengajuan', function () {\n"
                    . "    if (view()->exists('pengajuan')) {\n"
                    . "        return view('pengajuan');\n"
                    . "    }\n"
                    . "    if (view()->exists('admin.pengajuan')) {\n"
                    . "        return view('admin.pengajuan');\n"
                    . "    }\n"
                    . "    return redirect()->to(url('/admin-dashboard?pengajuan=' . urlencode((string) request('jenis', 'peminjaman'))));\n"
                    . "})->name('pengajuan.index');\n";
                file_put_contents($__webRoutePath, $__isiWebRoute . $__routePengajuanBaru);
            }
        }
    } catch (\Throwable $e) {
    }

    // Sinkronisasi otomatis: Ubah abort(403) pada KelolaAkunController / routes menjadi redirect ke Dashboard Admin
    try {
        $__kandidatFilePatch = array_merge(
            glob(app_path('Http/Controllers/*.php')) ?: [],
            glob(app_path('Http/Middleware/*.php')) ?: [],
            [base_path('routes/web.php')]
        );
        foreach ($__kandidatFilePatch as $__filePatch) {
            if (is_string($__filePatch) && is_file($__filePatch) && is_writable($__filePatch)) {
                $__isiFilePatch = (string) file_get_contents($__filePatch);
                if (str_contains($__isiFilePatch, '403') && (str_contains($__isiFilePatch, 'Hanya Master Admin') || str_contains(basename($__filePatch), 'KelolaAkun'))) {
                    $__kodeRedirectAman = "throw new \\Illuminate\\Http\\Exceptions\\HttpResponseException(redirect(\\Illuminate\\Support\\Facades\\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin-dashboard'))->with('error', 'Hanya Master Admin yang dapat mengelola akun.'));";
                    $__isiBaruPatch = preg_replace(
                        '/(?:return\s+)?abort\s*\(\s*403\s*,\s*[\'""][^\'""]*Hanya Master Admin[^\'""]*[\'""]\s*\)\s*;/i',
                        $__kodeRedirectAman,
                        $__isiFilePatch
                    );
                    $__isiBaruPatch = preg_replace(
                        '/abort_if\s*\(\s*(.+?)\s*,\s*403\s*,\s*[\'""][^\'""]*Hanya Master Admin[^\'""]*[\'""]\s*\)\s*;/i',
                        'if ($1) { ' . $__kodeRedirectAman . ' }',
                        (string) $__isiBaruPatch
                    );
                    $__isiBaruPatch = preg_replace(
                        '/abort_unless\s*\(\s*(.+?)\s*,\s*403\s*,\s*[\'""][^\'""]*Hanya Master Admin[^\'""]*[\'""]\s*\)\s*;/i',
                        'if (!($1)) { ' . $__kodeRedirectAman . ' }',
                        (string) $__isiBaruPatch
                    );
                    if (is_string($__isiBaruPatch) && $__isiBaruPatch !== $__isiFilePatch) {
                        file_put_contents($__filePatch, $__isiBaruPatch);
                    }
                }
            }
        }
    } catch (\Throwable $e) {
    }

    // PROTEKSI ROLE GUARD: Jika yang sedang login adalah Master Admin namun membuka halaman khusus Admin,
    // langsung arahkan kembali ke Dashboard Master Admin!
    $__roleCekAdminSb = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';
    if ($__roleCekAdminSb === 'master_admin' || $__roleCekAdminSb === 'masteradmin') {
        $__uriCekSb = strtolower(trim((string) request()->path(), '/'));
        if (str_starts_with($__uriCekSb, 'admin-dashboard') || str_starts_with($__uriCekSb, 'admin/dashboard') || request()->routeIs('admin.dashboard')) {
            $urlMasterDash = \Illuminate\Support\Facades\Route::has('masteradmin.dashboard') ? route('masteradmin.dashboard') : url('/masteradmin');
            header('Location: ' . $urlMasterDash);
            exit;
        }
    }

    session(['active_sidebar_panel' => 'admin']);

    // 1. Baca konfigurasi Roles & Permission dari storage/app/roles_permissions.json
    $rolesStoragePath = storage_path('app/roles_permissions.json');
    $semuaModulDefault = [
        'dashboard', 'pengajuan', 'pengajuan_peminjaman', 'pengajuan_pengembalian', 'pengajuan_peleburan', 'pengajuan_servis', 'notifikasi', 'data_aset', 'barcode_aset',
        'kategori_aset', 'cabang', 'divisi', 'jabatan',
        'laporan', 'riwayat_aktivitas',
    ];

    $defaultPermsRole = [];
    foreach ($semuaModulDefault as $mKey) {
        $defaultPermsRole[$mKey] = [
            'view'   => true,
            'create' => true,
            'edit'   => true,
            'delete' => true,
        ];
    }

    $roleUserAktif = 'admin';
    if (\Illuminate\Support\Facades\Auth::check()) {
        $rawRoleUser = strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? 'admin'))));
        if ($rawRoleUser !== '' && $rawRoleUser !== 'master_admin' && $rawRoleUser !== 'masteradmin') {
            $roleUserAktif = $rawRoleUser;
        }
    }

    $permissionsUserSaatIni = $defaultPermsRole;
    $versiPermissionSaatIni = 0;

    if (is_file($rolesStoragePath)) {
        $decodedRoles = json_decode((string) file_get_contents($rolesStoragePath), true);
        if (is_array($decodedRoles)) {
            $versiPermissionSaatIni = $decodedRoles['updated_at'] ?? 0;
            $dataRoleTarget = $decodedRoles['roles'][$roleUserAktif] ?? ($decodedRoles['roles']['admin'] ?? null);
            if (is_array($dataRoleTarget) && isset($dataRoleTarget['permissions']) && is_array($dataRoleTarget['permissions'])) {
                foreach ($semuaModulDefault as $mKey) {
                    if (isset($dataRoleTarget['permissions'][$mKey])) {
                        $p = $dataRoleTarget['permissions'][$mKey];
                        $v = !empty($p['view']);
                        $permissionsUserSaatIni[$mKey] = [
                            'view'   => $v,
                            'create' => $v ? !empty($p['create']) : false,
                            'edit'   => $v ? !empty($p['edit']) : false,
                            'delete' => $v ? !empty($p['delete']) : false,
                        ];
                    }
                }
            }
        }
    }

    // Endpoint ringan untuk polling real-time hak akses dari browser Admin (tanpa reload)
    if (request()->has('_check_permissions_rt')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode([
            'success'     => true,
            'role'        => $roleUserAktif,
            'updated_at'  => $versiPermissionSaatIni,
            'permissions' => $permissionsUserSaatIni,
        ]);
        exit;
    }

    // Deteksi modul halaman yang sedang dibuka saat ini secara presisi (tanpa bentrok wildcard *aset.index)
    $currentUriPath = strtolower(trim((string) request()->path(), '/'));
    $modulHalamanAktif = 'dashboard';

    if (str_contains($currentUriPath, 'pengajuan') || request()->routeIs('pengajuan.*', 'admin.pengajuan.*')) {
        $modulHalamanAktif = 'pengajuan';
    } elseif (str_contains($currentUriPath, 'notifikasi') || request()->routeIs('notifikasi.*', 'admin.notifikasi.*')) {
        $modulHalamanAktif = 'notifikasi';
    } elseif (str_contains($currentUriPath, 'barcode-aset') || str_contains($currentUriPath, 'barcode_aset') || request()->routeIs('barcode_aset.*', 'admin.barcode_aset.*')) {
        $modulHalamanAktif = 'barcode_aset';
    } elseif (str_contains($currentUriPath, 'kategori-aset') || str_contains($currentUriPath, 'kategori_aset') || request()->routeIs('kategori_aset.*', 'admin.kategori_aset.*')) {
        $modulHalamanAktif = 'kategori_aset';
    } elseif (str_contains($currentUriPath, 'data-aset') || str_contains($currentUriPath, 'data_aset') || request()->routeIs('aset.index', 'admin.aset.index', 'detailaset.*')) {
        $modulHalamanAktif = 'data_aset';
    } elseif (str_contains($currentUriPath, 'cabang') || request()->routeIs('cabang.*', 'admin.cabang.*')) {
        $modulHalamanAktif = 'cabang';
    } elseif (str_contains($currentUriPath, 'divisi') || request()->routeIs('divisi.*', 'admin.divisi.*')) {
        $modulHalamanAktif = 'divisi';
    } elseif (str_contains($currentUriPath, 'jabatan') || request()->routeIs('jabatan.*', 'admin.jabatan.*')) {
        $modulHalamanAktif = 'jabatan';
    } elseif (str_contains($currentUriPath, 'laporan') || request()->routeIs('laporan.*', 'admin.laporan.*')) {
        $modulHalamanAktif = 'laporan';
    } elseif (str_contains($currentUriPath, 'riwayat') || request()->routeIs('riwayat_aktivitas.*', 'admin.riwayat_aktivitas.*')) {
        $modulHalamanAktif = 'riwayat_aktivitas';
    }

    // Catat otomatis aktivitas Login Admin ke RiwayatAktivitas (1x per sesi login) jika belum tercatat oleh AuthController
    try {
        if (\Illuminate\Support\Facades\Auth::check() && !session()->has('_login_riwayat_tercatat') && class_exists(\App\Models\RiwayatAktivitas::class)) {
            $userLogin = \Illuminate\Support\Facades\Auth::user();
            $sudahAdaLogin = \App\Models\RiwayatAktivitas::where('user_id', $userLogin->id)
                ->where('aktivitas', 'like', '%Login%')
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if (!$sudahAdaLogin) {
                $logLogin = new \App\Models\RiwayatAktivitas();
                $tblLog = $logLogin->getTable();
                $dataLogin = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLog, 'user_id')) {
                    $dataLogin['user_id'] = $userLogin->id;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLog, 'aktivitas')) {
                    $dataLogin['aktivitas'] = 'Melakukan Login ke sistem.';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLog, 'modul')) {
                    $dataLogin['modul'] = 'Autentikasi Sistem';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLog, 'status')) {
                    $dataLogin['status'] = 'Informasi';
                }
                $logLogin->forceFill($dataLogin)->save();
            }
            session(['_login_riwayat_tercatat' => true]);
        }
    } catch (\Throwable $e) {
    }

    // Pasang guard otomatis pada layouts/mastersidebar.blade.php (jika belum ada)
    try {
        $masterSidebarPath = resource_path('views/layouts/mastersidebar.blade.php');
        if (is_file($masterSidebarPath) && is_writable($masterSidebarPath)) {
            $isiMasterSidebar = file_get_contents($masterSidebarPath);
            $penandaGuard = '/* AUTO_ADMIN_SIDEBAR_GUARD */';
            if ($isiMasterSidebar !== false && strpos($isiMasterSidebar, $penandaGuard) === false) {
                $at = chr(64);
                $guardPrefix = $at . "php\n"
                    . "    {$penandaGuard}\n"
                    . "    \$__roleLoginSaatIni = \\Illuminate\\Support\\Facades\\Auth::check()\n"
                    . "        ? strtolower(str_replace(' ', '_', trim((string) (\\Illuminate\\Support\\Facades\\Auth::user()->role ?? ''))))\n"
                    . "        : '';\n"
                    . "    \$__isAkunMasterAdmin = (\$__roleLoginSaatIni === 'master_admin' || \$__roleLoginSaatIni === 'masteradmin');\n"
                    . "    \$__pakaiAdminSidebar = (!\$__isAkunMasterAdmin && \$__roleLoginSaatIni !== '') || request('panel') === 'admin';\n"
                    . $at . "endphp\n"
                    . $at . "if(\$__pakaiAdminSidebar)\n"
                    . "    " . $at . "include('layouts.adminsidebar')\n"
                    . $at . "else\n";
                $guardSuffix = "\n" . $at . "endif\n";
                file_put_contents($masterSidebarPath, $guardPrefix . $isiMasterSidebar . $guardSuffix);
            }
        }
    } catch (\Throwable $e) {
    }

    $resolveAdminMenuUrl = function (array $adminRouteCandidates, string $defaultRoute) {
        foreach ($adminRouteCandidates as $candidate) {
            if (\Illuminate\Support\Facades\Route::has($candidate)) {
                return route($candidate);
            }
        }
        if (\Illuminate\Support\Facades\Route::has($defaultRoute)) {
            return route($defaultRoute);
        }
        return '#';
    };

    // Hitung otomatis jumlah aset yang rusak / mendekati jadwal servis (<= 30 hari)
    $hariIniNotif = \Carbon\Carbon::today();
    $batasMendekatiNotif = $hariIniNotif->copy()->addDays(30)->toDateString();

    $jumlahNotifSidebar = \App\Models\DetailAset::where(function ($sub) use ($batasMendekatiNotif) {
        $sub->where(function ($s1) use ($batasMendekatiNotif) {
            $s1->whereNotNull('servis_berikutnya')
               ->where('servis_berikutnya', '!=', '-')
               ->where('servis_berikutnya', '<=', $batasMendekatiNotif);
        })->orWhere('status_aset', 'like', '%Rusak%');
    })->count();

    // Ambil daftar ID seluruh Riwayat Aktivitas untuk badge riwayat
    $daftarIdRiwayat = [];
    try {
        if (isset($riwayats) && method_exists($riwayats, 'count') && $riwayats->count() > 0) {
            $firstModel = $riwayats->first();
            if (is_object($firstModel) && method_exists($firstModel, 'newQuery')) {
                $pk = $firstModel->getKeyName();
                $daftarIdRiwayat = $firstModel->newQuery()->orderByDesc($pk)->pluck($pk)->map(fn($v) => (string) $v)->toArray();
            }
        }
        if (empty($daftarIdRiwayat)) {
            $riwayatClasses = [
                \App\Models\RiwayatAktivitas::class,
                \App\Models\Riwayat::class,
                \App\Models\ActivityLog::class,
                \App\Models\LogAktivitas::class,
            ];
            foreach ($riwayatClasses as $modelClass) {
                if (class_exists($modelClass)) {
                    $inst = new $modelClass;
                    $pk = $inst->getKeyName();
                    $daftarIdRiwayat = $modelClass::orderByDesc($pk)->pluck($pk)->map(fn($v) => (string) $v)->toArray();
                    break;
                }
            }
        }
        if (empty($daftarIdRiwayat)) {
            foreach (['riwayat_aktivitas', 'riwayats', 'riwayat', 'activity_logs', 'log_aktivitas'] as $tbl) {
                if (\Illuminate\Support\Facades\Schema::hasTable($tbl)) {
                    $cols = \Illuminate\Support\Facades\Schema::getColumnListing($tbl);
                    $pkCol = in_array('id', $cols) ? 'id' : (in_array('riwayat_id', $cols) ? 'riwayat_id' : ($cols[0] ?? 'id'));
                    $daftarIdRiwayat = \Illuminate\Support\Facades\DB::table($tbl)->orderByDesc($pkCol)->pluck($pkCol)->map(fn($v) => (string) $v)->toArray();
                    break;
                }
            }
        }
    } catch (\Throwable $e) {
        $daftarIdRiwayat = [];
    }

    $adaAksiBaru = session()->has('success');
@endphp

<sidebar>
    <div class="sidebar-brand">
        <div class="brand-logo">SM</div>
        <div class="brand-text">
            <span class="sistem">Sistem</span>
            <span class="master-admin">{{ isset(Auth::user()->role) && strtolower(Auth::user()->role) !== 'master_admin' ? str_replace('_', ' ', Str::title(Auth::user()->role)) : 'Admin' }}</span>
        </div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section-title" data-section-group="utama">MENU UTAMA</div>
        <a href="{{ route('admin.dashboard') }}"
           id="link-sidebar-dashboard"
           data-menu-key="dashboard"
           data-menu-group="utama"
           style="{{ empty($permissionsUserSaatIni['dashboard']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ ($modulHalamanAktif === 'dashboard' && !request()->has('pengajuan')) ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
            Dashboard
        </a>

        <!-- Menu Pengajuan (Di Bawah Dashboard) beserta 4 Button Pilihan Pengajuan -->
        <div class="menu-pengajuan-wrap {{ ($modulHalamanAktif === 'pengajuan' || request()->has('pengajuan')) ? 'open' : '' }}"
             id="wrap-menu-pengajuan"
             data-menu-key="pengajuan"
             data-menu-group="utama"
             style="{{ (isset($permissionsUserSaatIni['pengajuan']) && empty($permissionsUserSaatIni['pengajuan']['view'])) ? 'display:none;' : '' }}">
            <button type="button"
                    id="btn-sidebar-pengajuan"
                    class="menu-item menu-item-btn {{ ($modulHalamanAktif === 'pengajuan' || request()->has('pengajuan')) ? 'active' : '' }}"
                    onclick="toggleMenuPengajuanSidebar(event)">
                <!-- Ikon Pengajuan -->
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14h-3v3h-2v-3H8v-2h3v-3h2v3h3v2zm-3-7V3.5L18.5 9H13z"/></svg>
                <span>Pengajuan</span>
                <svg class="chevron-pengajuan" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
            </button>
            <div class="submenu-pengajuan" id="submenu-pengajuan-list">
                <button type="button"
                        class="submenu-pengajuan-btn"
                        data-jenis="peminjaman"
                        data-subperm-key="pengajuan_peminjaman"
                        style="{{ (isset($permissionsUserSaatIni['pengajuan_peminjaman']) && empty($permissionsUserSaatIni['pengajuan_peminjaman']['view'])) ? 'display:none;' : '' }}"
                        onclick="pilihKategoriPengajuan('peminjaman')">
                    <span class="submenu-dot"></span>
                    <span>Pengajuan Peminjaman</span>
                </button>
                <button type="button"
                        class="submenu-pengajuan-btn"
                        data-jenis="pengembalian"
                        data-subperm-key="pengajuan_pengembalian"
                        style="{{ (isset($permissionsUserSaatIni['pengajuan_pengembalian']) && empty($permissionsUserSaatIni['pengajuan_pengembalian']['view'])) ? 'display:none;' : '' }}"
                        onclick="pilihKategoriPengajuan('pengembalian')">
                    <span class="submenu-dot"></span>
                    <span>Pengajuan Pengembalian</span>
                </button>
                <button type="button"
                        class="submenu-pengajuan-btn"
                        data-jenis="peleburan"
                        data-subperm-key="pengajuan_peleburan"
                        style="{{ (isset($permissionsUserSaatIni['pengajuan_peleburan']) && empty($permissionsUserSaatIni['pengajuan_peleburan']['view'])) ? 'display:none;' : '' }}"
                        onclick="pilihKategoriPengajuan('peleburan')">
                    <span class="submenu-dot"></span>
                    <span>Pengajuan Peleburan</span>
                </button>
                <button type="button"
                        class="submenu-pengajuan-btn"
                        data-jenis="servis"
                        data-subperm-key="pengajuan_servis"
                        style="{{ (isset($permissionsUserSaatIni['pengajuan_servis']) && empty($permissionsUserSaatIni['pengajuan_servis']['view'])) ? 'display:none;' : '' }}"
                        onclick="pilihKategoriPengajuan('servis')">
                    <span class="submenu-dot"></span>
                    <span>Pengajuan Servis</span>
                </button>
            </div>
        </div>
        <a href="{{ $resolveAdminMenuUrl(['admin.notifikasi.index', 'admin.notifikasi'], 'notifikasi.index') }}"
           data-menu-key="notifikasi"
           data-menu-group="utama"
           style="{{ empty($permissionsUserSaatIni['notifikasi']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'notifikasi' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            <span>Notifikasi</span>
            @if($jumlahNotifSidebar > 0)
                <span class="notif-badge" title="{{ $jumlahNotifSidebar }} aset rusak atau mendekati jadwal servis">{{ $jumlahNotifSidebar }}</span>
            @endif
        </a>

        <div class="menu-section-title" data-section-group="aset">MANAJEMEN ASET</div>
        <a href="{{ $resolveAdminMenuUrl(['admin.aset.index', 'admin.data_aset.index'], 'aset.index') }}"
           data-menu-key="data_aset"
           data-menu-group="aset"
           style="{{ empty($permissionsUserSaatIni['data_aset']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'data_aset' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm10 15H4V8h16v11z"/></svg>
            Data Aset
        </a>
        <a href="{{ $resolveAdminMenuUrl(['admin.barcode_aset.index', 'admin.barcode.index'], 'barcode_aset.index') }}"
           data-menu-key="barcode_aset"
           data-menu-group="aset"
           style="{{ empty($permissionsUserSaatIni['barcode_aset']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'barcode_aset' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M2 5h2v14H2V5zm4 0h1v14H6V5zm3 0h3v14H9V5zm4 0h2v14h-2V5zm3 0h1v14h-1V5zm3 0h2v14h-2V5z"/></svg>
            Barcode Aset
        </a>

        <div class="menu-section-title" data-section-group="master">MASTER DATA</div>
        <a href="{{ $resolveAdminMenuUrl(['admin.kategori_aset.index', 'admin.kategori.index'], 'kategori_aset.index') }}"
           data-menu-key="kategori_aset"
           data-menu-group="master"
           style="{{ empty($permissionsUserSaatIni['kategori_aset']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'kategori_aset' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            Kategori Aset
        </a>

        <a href="{{ $resolveAdminMenuUrl(['admin.cabang.index'], 'cabang.index') }}"
           data-menu-key="cabang"
           data-menu-group="master"
           style="{{ empty($permissionsUserSaatIni['cabang']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'cabang' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
            Cabang
        </a>
        <a href="{{ $resolveAdminMenuUrl(['admin.divisi.index'], 'divisi.index') }}"
           data-menu-key="divisi"
           data-menu-group="master"
           style="{{ empty($permissionsUserSaatIni['divisi']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'divisi' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
            Divisi
        </a>
        <a href="{{ $resolveAdminMenuUrl(['admin.jabatan.index'], 'jabatan.index') }}"
           data-menu-key="jabatan"
           data-menu-group="master"
           style="{{ empty($permissionsUserSaatIni['jabatan']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'jabatan' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            Jabatan
        </a>

        <div class="menu-section-title" data-section-group="laporan">LAPORAN & PEMANTAUAN</div>
        <a href="{{ $resolveAdminMenuUrl(['admin.laporan.index'], 'laporan.index') }}"
           data-menu-key="laporan"
           data-menu-group="laporan"
           style="{{ empty($permissionsUserSaatIni['laporan']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'laporan' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
            Laporan
        </a>
        <a href="{{ $resolveAdminMenuUrl(['admin.riwayat_aktivitas.index', 'admin.riwayat.index'], 'riwayat_aktivitas.index') }}"
           data-menu-key="riwayat_aktivitas"
           data-menu-group="laporan"
           style="{{ empty($permissionsUserSaatIni['riwayat_aktivitas']['view']) ? 'display:none;' : '' }}"
           class="menu-item {{ $modulHalamanAktif === 'riwayat_aktivitas' ? 'active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
            <span>Riwayat Aktivitas</span>
            <span id="badge-riwayat-sidebar" class="notif-badge" style="display: none;" title="Riwayat aktivitas baru belum dibaca">0</span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">{{ substr(Auth::user()->nama_lengkap ?? 'A', 0, 1) }}</div>
        <div class="user-info">
            <div class="user-name">{{ Auth::user()->nama_lengkap ?? 'Admin' }}</div>
            <div class="user-role">{{ isset(Auth::user()->role) ? str_replace('_', ' ', Str::title(Auth::user()->role)) : 'Admin' }}</div>
        </div>
        <div style="margin-left: auto;">
            <form action="{{ route('logout') }}" method="POST" style="margin: 0; display: flex;">
                @csrf
                <button type="submit" title="Logout" style="border: none; background: none; cursor: pointer; color: #dc2626; padding: 5px; display: flex; align-items: center; justify-content: center; transition: opacity 0.2s;">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width: 22px; height: 22px;">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</sidebar>

<script>
    (function () {
        // 1. LOGIKA BADGE RIWAYAT AKTIVITAS
        const STORAGE_KEY_RIWAYAT = 'riwayat_terbaca_auto_user_{{ Auth::id() ?? "guest" }}';
        const semuaIdRiwayatServer = @json($daftarIdRiwayat).map(String);
        const adaAksiBaruServer = @json($adaAksiBaru);

        function inisialisasiJikaBelumAda() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY_RIWAYAT);
                if (raw === null) {
                    const idAwal = adaAksiBaruServer && semuaIdRiwayatServer.length > 0
                        ? semuaIdRiwayatServer.slice(1)
                        : semuaIdRiwayatServer;
                    localStorage.setItem(STORAGE_KEY_RIWAYAT, JSON.stringify(idAwal));
                }
            } catch (e) {}
        }

        function ambilIdRiwayatDibaca() {
            inisialisasiJikaBelumAda();
            try {
                const raw = localStorage.getItem(STORAGE_KEY_RIWAYAT);
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed.map(String) : [];
            } catch (e) {
                return [];
            }
        }

        function simpanIdRiwayatDibaca(listId) {
            try {
                const unik = Array.from(new Set(listId.map(String)));
                localStorage.setItem(STORAGE_KEY_RIWAYAT, JSON.stringify(unik));
            } catch (e) {}
        }

        function ambilSemuaIdRiwayatAktif() {
            const setIds = new Set(semuaIdRiwayatServer);
            document.querySelectorAll('tr[data-riwayat-id]').forEach(function (tr) {
                const id = tr.getAttribute('data-riwayat-id');
                if (id) setIds.add(String(id));
            });
            return Array.from(setIds);
        }

        window.hitungRiwayatBelumDibaca = function () {
            const semuaId = ambilSemuaIdRiwayatAktif();
            const sudahDibaca = new Set(ambilIdRiwayatDibaca());
            let belumDibaca = 0;

            semuaId.forEach(function (id) {
                if (!sudahDibaca.has(String(id))) {
                    belumDibaca++;
                }
            });

            const badge = document.getElementById('badge-riwayat-sidebar');
            if (badge) {
                if (belumDibaca > 0) {
                    badge.textContent = belumDibaca;
                    badge.style.display = 'inline-flex';
                    badge.title = belumDibaca + ' riwayat aktivitas baru belum dibaca';
                } else {
                    badge.textContent = '0';
                    badge.style.display = 'none';
                }
            }

            return belumDibaca;
        };

        window.apakahRiwayatSudahDibaca = function (id) {
            const sudahDibaca = new Set(ambilIdRiwayatDibaca());
            return sudahDibaca.has(String(id));
        };

        window.tandaiSatuRiwayatDibaca = function (id) {
            if (!id) return;
            const sudahDibaca = ambilIdRiwayatDibaca();
            const strId = String(id);
            if (!sudahDibaca.includes(strId)) {
                sudahDibaca.push(strId);
                simpanIdRiwayatDibaca(sudahDibaca);
            }
            window.hitungRiwayatBelumDibaca();
        };

        window.tandaiSemuaRiwayatDibaca = function () {
            const semuaId = ambilSemuaIdRiwayatAktif();
            const sudahDibaca = ambilIdRiwayatDibaca();
            simpanIdRiwayatDibaca(sudahDibaca.concat(semuaId));
            window.hitungRiwayatBelumDibaca();
        };

        // 2. MESIN SINKRONISASI REAL-TIME ROLES & PERMISSION (TANPA REFRESH)
        const MODUL_HALAMAN_AKTIF = @json($modulHalamanAktif);
        let currentVersion = @json($versiPermissionSaatIni);
        let currentPerms = @json($permissionsUserSaatIni);

        function tampilkanToastPerubahanAkses(pesan) {
            const lama = document.getElementById('perm-rt-toast-el');
            if (lama) lama.remove();

            const toast = document.createElement('div');
            toast.id = 'perm-rt-toast-el';
            toast.className = 'perm-rt-toast';
            toast.innerHTML = `<span>🔒 ${pesan}</span>`;
            document.body.appendChild(toast);

            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }

        function setElemenVisible(selectorList, visible) {
            selectorList.forEach(sel => {
                document.querySelectorAll(sel).forEach(el => {
                    el.style.display = visible ? '' : 'none';
                });
            });
        }

        function terapkanHakAksesKeLayar(perms, isRealtimeUpdate = false) {
            if (!perms) return;

            window.__adminCurrentPerms = perms;

            // A. Perbarui visibilitas item menu di sidebar secara langsung (termasuk per item Pengajuan)
            const subKeysPengajuan = ['pengajuan_peminjaman', 'pengajuan_pengembalian', 'pengajuan_peleburan', 'pengajuan_servis'];
            let adaSubPengajuanBuka = false;

            subKeysPengajuan.forEach(sk => {
                const jenis = sk.replace('pengajuan_', '');
                const subAllowed = perms[sk] ? !!perms[sk].view : true;
                if (subAllowed) adaSubPengajuanBuka = true;

                document.querySelectorAll(`.submenu-pengajuan-btn[data-jenis="${jenis}"]`).forEach(btn => {
                    btn.style.display = subAllowed ? 'flex' : 'none';
                });
                document.querySelectorAll(`.btn-tab-pengajuan[data-tab-jenis="${jenis}"]`).forEach(btn => {
                    btn.style.display = subAllowed ? 'flex' : 'none';
                });
            });

            const mainPengajuanAllowed = (perms['pengajuan'] ? !!perms['pengajuan'].view : true) && adaSubPengajuanBuka;

            document.querySelectorAll('[data-menu-key]').forEach(link => {
                const key = link.getAttribute('data-menu-key');
                if (key === 'pengajuan') {
                    link.style.display = mainPengajuanAllowed ? 'flex' : 'none';
                } else {
                    const bolehLihat = perms[key] ? !!perms[key].view : true;
                    link.style.display = bolehLihat ? 'flex' : 'none';
                }
            });

            // Sinkronkan halaman /pengajuan (pengajuan.blade.php) jika sedang dibuka
            if (typeof window.sinkronkanStatusAksesPengajuan === 'function') {
                window.sinkronkanStatusAksesPengajuan(perms);
            }

            // Sinkronkan juga apabila panel fallback pengajuan sedang aktif
            const panelPengajuan = document.getElementById('panel-utama-pengajuan');
            if (panelPengajuan && panelPengajuan.classList.contains('active')) {
                const kartuIsi = document.getElementById('kartu-isi-pengajuan');
                const currentActiveBtn = document.querySelector('.btn-tab-pengajuan.active');
                const currentJenis = currentActiveBtn ? currentActiveBtn.getAttribute('data-tab-jenis') : 'peminjaman';
                const currentSubKey = 'pengajuan_' + currentJenis;
                const isCurrentOpen = mainPengajuanAllowed && (perms[currentSubKey] ? !!perms[currentSubKey].view : true);
                const labelNamaMap = {
                    peminjaman: 'Pengajuan Peminjaman',
                    pengembalian: 'Pengajuan Pengembalian',
                    peleburan: 'Pengajuan Peleburan',
                    servis: 'Pengajuan Servis'
                };
                const labelAktif = labelNamaMap[currentJenis] || 'Pengajuan';

                if (!isCurrentOpen && kartuIsi) {
                    kartuIsi.innerHTML = `
                        <div style="padding: 38px 20px; text-align: center;">
                            <div style="font-size: 42px; margin-bottom: 10px;">🚫</div>
                            <h3 style="color: #0f172a; font-size: 19px; font-weight: 800; margin-bottom: 8px;">Hak Akses ${mainPengajuanAllowed ? labelAktif : 'Menu Pengajuan'} Dicabut</h3>
                            <p style="color: #64748b; font-size: 13px; max-width: 480px; margin: 0 auto 18px auto; line-height: 1.6;">
                                Master Admin telah menutup / menonaktifkan akses pada <strong>${mainPengajuanAllowed ? labelAktif : 'Menu Pengajuan Aset'}</strong> untuk role Anda secara real-time.
                            </p>
                            <a href="{{ route('admin.dashboard') }}" style="display: inline-block; background: #1a4f91; color: #fff; text-decoration: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 700;">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    `;
                } else if (isCurrentOpen && typeof window.tampilkanViewPengajuanDiMain === 'function') {
                    window.tampilkanViewPengajuanDiMain(currentJenis);
                }
            }

            // Sembunyikan judul grup di sidebar jika seluruh menu di bawahnya disembunyikan
            ['utama', 'aset', 'master', 'laporan'].forEach(grup => {
                const header = document.querySelector(`.menu-section-title[data-section-group="${grup}"]`);
                const items = Array.from(document.querySelectorAll(`[data-menu-group="${grup}"]`));
                const adaYangTampil = items.some(it => it.style.display !== 'none');
                if (header) {
                    header.style.display = adaYangTampil ? 'block' : 'none';
                }
            });

            // B. Cek hak akses modul halaman yang sedang dibuka saat ini
            const pModul = perms[MODUL_HALAMAN_AKTIF] || { view: true, create: true, edit: true, delete: true };
            const bolehView = (MODUL_HALAMAN_AKTIF === 'pengajuan') ? mainPengajuanAllowed : !!pModul.view;
            const bolehCreate = bolehView && !!pModul.create;
            const bolehEdit = bolehView && !!pModul.edit;
            const bolehDelete = bolehView && !!pModul.delete;

            // 1. Jika akses melihat halaman ini dicabut oleh Master Admin -> tampilkan layar kunci real-time
            let overlay = document.getElementById('perm-revoked-overlay');
            if (!bolehView) {
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'perm-revoked-overlay';
                    overlay.className = 'perm-revoked-overlay';
                    overlay.innerHTML = `
                        <div class="perm-revoked-card">
                            <div style="font-size: 42px; margin-bottom: 12px;">🚫</div>
                            <h2 style="color: #0f172a; font-size: 20px; font-weight: 800; margin-bottom: 8px;">Hak Akses Halaman Dicabut</h2>
                            <p style="color: #64748b; font-size: 13px; line-height: 1.6; margin-bottom: 22px;">
                                Master Admin telah menonaktifkan akses melihat pada modul ini untuk role Anda secara real-time.
                            </p>
                            <a href="{{ route('admin.dashboard') }}" style="display: inline-block; background: #1a4f91; color: #fff; text-decoration: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 700;">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                }
            } else if (overlay) {
                overlay.remove();
            }

            // 2. Kontrol tombol & form TAMBAH DATA (create)
            setElemenVisible([
                'button[onclick*="openSidebarTambah"]',
                'button[onclick*="openModalImport"]',
                'button[onclick*="prosesGenerateSemua"]',
                '.btn-generate-semua-qr',
                '#btn-simpan-kategori',
                '#btn-simpan-aset',
                '#btn-simpan-status'
            ], bolehCreate);

            // Tombol "Buat Label QR Baru" pada halaman Barcode Aset
            document.querySelectorAll('button[onclick*="prosesBuatQR"], .btn-create-single-qr').forEach(btn => {
                const txt = (btn.textContent || '').trim().toLowerCase();
                if (txt.includes('buat') || btn.classList.contains('btn-create-single-qr')) {
                    btn.style.display = bolehCreate ? '' : 'none';
                    let badgeReadonlyQr = btn.parentElement?.querySelector('.qr-readonly-placeholder');
                    if (!bolehCreate) {
                        if (!badgeReadonlyQr && btn.parentElement) {
                            badgeReadonlyQr = document.createElement('div');
                            badgeReadonlyQr.className = 'qr-readonly-placeholder';
                            badgeReadonlyQr.style.cssText = 'width:100%; padding:8px 12px; border-radius:8px; background:#f1f5f9; color:#64748b; font-size:12px; font-weight:600; text-align:center; border:1px dashed #cbd5e1;';
                            badgeReadonlyQr.textContent = '🔒 Belum Ada QR (Akses Tambah Dibatasi)';
                            btn.parentElement.appendChild(badgeReadonlyQr);
                        }
                    } else if (badgeReadonlyQr) {
                        badgeReadonlyQr.remove();
                    }
                }
            });

            // Sembunyikan kotak form tambah di sebelah kiri pada halaman Kategori Aset, Cabang, Divisi, Jabatan jika create = false
            ['#form-kategori', '#form-aset', '#form-status-aset'].forEach(formSel => {
                const formEl = document.querySelector(formSel);
                if (formEl) {
                    const cardBox = formEl.closest('.card-box');
                    const layoutGrid = formEl.closest('.layout-grid');
                    if (cardBox) {
                        cardBox.style.display = bolehCreate ? '' : 'none';
                    }
                    if (layoutGrid) {
                        layoutGrid.style.gridTemplateColumns = bolehCreate ? '' : '1fr';
                    }
                }
            });

            // Untuk halaman Master Data lain (Cabang, Divisi, Jabatan) yang memiliki form tambah POST di kolom kiri
            document.querySelectorAll('main form[method="POST"], main form[method="post"]').forEach(formEl => {
                const actionUrl = (formEl.getAttribute('action') || '').toLowerCase();
                if (actionUrl.includes('logout') || formEl.classList.contains('chip-edit-form') || formEl.classList.contains('form-delete-kategori')) return;
                const methodInput = formEl.querySelector('input[name="_method"]');
                const methodVal = methodInput ? (methodInput.value || '').toUpperCase() : 'POST';

                if (methodVal === 'POST' && !actionUrl.includes('clear')) {
                    const parentGrid = formEl.closest('.layout-grid');
                    const parentCard = formEl.closest('.card-box');
                    if (parentGrid && parentCard) {
                        parentCard.style.display = bolehCreate ? '' : 'none';
                        parentGrid.style.gridTemplateColumns = bolehCreate ? '' : '1fr';
                    } else {
                        const btnSub = formEl.querySelector('button[type="submit"]');
                        if (btnSub) btnSub.style.display = bolehCreate ? '' : 'none';
                        formEl.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(inp => {
                            if (!bolehCreate) {
                                inp.setAttribute('data-perm-disabled', '1');
                                inp.disabled = true;
                            } else if (inp.getAttribute('data-perm-disabled') === '1') {
                                inp.removeAttribute('data-perm-disabled');
                                inp.disabled = false;
                            }
                        });
                    }
                }
            });

            if (!bolehCreate) {
                const sbTambah = document.getElementById('sidebarTambahAset');
                if (sbTambah) sbTambah.classList.remove('show');
                const modalImp = document.getElementById('modalImportExcel');
                if (modalImp) modalImp.classList.remove('show');
            }

            // 3. Kontrol tombol & form UBAH / EDIT DATA (edit)
            setElemenVisible([
                '.btn-edit-sm',
                'button[onclick*="enableInlineEdit"]',
                'button[onclick*="enableEditMode"]',
                'button[onclick*="prosesUpdateSemua"]',
                '.btn-update-semua-qr',
                '.btn-update-single-qr'
            ], bolehEdit);

            // Tombol "Update QR" pada tiap kartu di halaman Barcode Aset
            document.querySelectorAll('.card-footer button.btn-qr:not(.btn-lihat-qr)').forEach(btn => {
                const txt = (btn.textContent || '').trim().toLowerCase();
                if (txt.includes('update')) {
                    btn.style.display = bolehEdit ? '' : 'none';
                }
            });

            if (!bolehEdit) {
                document.querySelectorAll('.chip-wrapper.is-editing').forEach(w => w.classList.remove('is-editing'));
                if (typeof window.setViewMode === 'function' && document.getElementById('modalDetailAset')?.classList.contains('show')) {
                    window.setViewMode();
                }
            }

            // 4. Kontrol tombol & form HAPUS DATA (delete)
            setElemenVisible([
                '.btn-delete-sm',
                '.form-delete-kategori',
                'button[onclick*="submitHapusAset"]',
                'form[action*="clear"]',
                '.header-actions .btn-danger-soft'
            ], bolehDelete);

            document.querySelectorAll('main form').forEach(formEl => {
                const methodInput = formEl.querySelector('input[name="_method"]');
                if (methodInput && (methodInput.value || '').toUpperCase() === 'DELETE') {
                    formEl.style.display = bolehDelete ? '' : 'none';
                }
            });

            // Jika pada halaman chip (Kategori, Master Aset, Status, Cabang, Divisi, Jabatan) baik Edit maupun Hapus dimatikan,
            // sembunyikan bar .chip-actions agar saat chip diklik tidak muncul ruang kosong
            document.querySelectorAll('.chip-actions').forEach(actEl => {
                actEl.style.display = (!bolehEdit && !bolehDelete) ? 'none' : '';
            });

            // 5. Pasang aturan CSS dinamis (!important) agar tombol/form yang dinonaktifkan dijamin tersembunyi
            // termasuk di dalam modal, kartu QR, maupun chip yang baru diklik
            let dynStyle = document.getElementById('perm-dynamic-style');
            if (!dynStyle) {
                dynStyle = document.createElement('style');
                dynStyle.id = 'perm-dynamic-style';
                document.head.appendChild(dynStyle);
            }
            let cssRules = [];
            if (!bolehCreate) {
                cssRules.push(`
                    button[onclick*="openSidebarTambah"],
                    button[onclick*="openModalImport"],
                    button[onclick*="prosesGenerateSemua"],
                    .btn-generate-semua-qr,
                    .btn-create-single-qr,
                    #btn-simpan-kategori,
                    #btn-simpan-aset,
                    #btn-simpan-status,
                    a[href*="export"],
                    button[onclick*="export"],
                    button[onclick*="cetak"],
                    button[onclick*="print"] {
                        display: none !important;
                    }
                `);
            }
            if (!bolehEdit) {
                cssRules.push(`
                    .btn-edit-sm,
                    button[onclick*="enableInlineEdit"],
                    button[onclick*="enableEditMode"],
                    button[onclick*="prosesUpdateSemua"],
                    .btn-update-semua-qr,
                    .btn-update-single-qr,
                    .chip-edit-form {
                        display: none !important;
                    }
                `);
            }
            if (!bolehDelete) {
                cssRules.push(`
                    .btn-delete-sm,
                    .form-delete-kategori,
                    button[onclick*="submitHapusAset"],
                    .btn-hapus-lampiran,
                    form[action*="clear"],
                    .header-actions .btn-danger-soft {
                        display: none !important;
                    }
                `);
            }
            if (!bolehEdit && !bolehDelete) {
                cssRules.push(`
                    .chip-actions,
                    .chip-wrapper.active .chip-actions {
                        display: none !important;
                    }
                `);
            }
            dynStyle.textContent = cssRules.join('\n');

            // 6. Tampilkan atau sembunyikan Banner Info Pembatasan Akses sesuai kapabilitas modul
            const MODUL_CAPS = {
                dashboard: ['view'],
                pengajuan: ['view'],
                notifikasi: ['view'],
                data_aset: ['view', 'create', 'edit', 'delete'],
                barcode_aset: ['view', 'create', 'edit'],
                kategori_aset: ['view', 'create', 'edit', 'delete'],
                cabang: ['view', 'create', 'edit', 'delete'],
                divisi: ['view', 'create', 'edit', 'delete'],
                jabatan: ['view', 'create', 'edit', 'delete'],
                laporan: ['view', 'create'],
                riwayat_aktivitas: ['view', 'delete']
            };
            const capsModulIni = MODUL_CAPS[MODUL_HALAMAN_AKTIF] || ['view'];
            const punyaAksiSelainView = capsModulIni.length > 1;
            const semuaAksiAktif = (!capsModulIni.includes('create') || bolehCreate)
                && (!capsModulIni.includes('edit') || bolehEdit)
                && (!capsModulIni.includes('delete') || bolehDelete);
            const semuaAksiMati = (!capsModulIni.includes('create') || !bolehCreate)
                && (!capsModulIni.includes('edit') || !bolehEdit)
                && (!capsModulIni.includes('delete') || !bolehDelete);

            const mainEl = document.querySelector('main');
            let bannerEl = document.getElementById('admin-perm-status-banner');

            if (bolehView && punyaAksiSelainView && !semuaAksiAktif) {
                if (!bannerEl && mainEl) {
                    bannerEl = document.createElement('div');
                    bannerEl.id = 'admin-perm-status-banner';
                    bannerEl.className = 'perm-readonly-banner';
                    const headerEl = mainEl.querySelector('.main-header');
                    if (headerEl && headerEl.nextSibling) {
                        mainEl.insertBefore(bannerEl, headerEl.nextSibling);
                    } else {
                        mainEl.prepend(bannerEl);
                    }
                }
                if (bannerEl) {
                    if (semuaAksiMati) {
                        bannerEl.innerHTML = `
                            <span>👁️ <strong>Mode Hanya Lihat (Read-Only):</strong> Anda hanya memiliki akses melihat pada halaman ini. Fitur perubahan/aksi data dinonaktifkan oleh Master Admin.</span>
                            <span style="font-size: 11px; background: #fef3c7; border: 1px solid #f59e0b; padding: 3px 8px; border-radius: 999px; white-space: nowrap;">Read-Only</span>
                        `;
                    } else {
                        const izinAktif = ['Lihat'];
                        if (capsModulIni.includes('create') && bolehCreate) izinAktif.push(MODUL_HALAMAN_AKTIF === 'barcode_aset' ? 'Generate QR' : 'Tambah');
                        if (capsModulIni.includes('edit') && bolehEdit) izinAktif.push(MODUL_HALAMAN_AKTIF === 'barcode_aset' ? 'Update QR' : 'Ubah');
                        if (capsModulIni.includes('delete') && bolehDelete) izinAktif.push(MODUL_HALAMAN_AKTIF === 'riwayat_aktivitas' ? 'Bersihkan Log' : 'Hapus');
                        bannerEl.innerHTML = `
                            <span>🔒 <strong>Hak Akses Dibatasi oleh Master Admin:</strong> Izin aktif Anda pada halaman ini adalah <strong>${izinAktif.join(' + ')}</strong>.</span>
                            <span style="font-size: 11px; background: #fef3c7; border: 1px solid #f59e0b; padding: 3px 8px; border-radius: 999px; white-space: nowrap;">Akses Terbatas</span>
                        `;
                    }
                }
            } else if (bannerEl) {
                bannerEl.remove();
            }

            if (isRealtimeUpdate) {
                tampilkanToastPerubahanAkses('Hak akses Anda baru saja diperbarui oleh Master Admin secara Real-Time!');
            }
        }

        let sedangCekPerm = false;
        async function cekPerubahanPermissionRealtime() {
            if (sedangCekPerm || document.hidden) return;
            sedangCekPerm = true;
            try {
                const pollUrl = new URL(window.location.href);
                pollUrl.searchParams.set('panel', 'admin');
                pollUrl.searchParams.set('_check_permissions_rt', Date.now());

                const res = await fetch(pollUrl.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data && data.success && data.permissions) {
                        const newVer = data.updated_at || 0;
                        const prevJson = JSON.stringify(currentPerms);
                        const nextJson = JSON.stringify(data.permissions);

                        if (newVer !== currentVersion || prevJson !== nextJson) {
                            currentVersion = newVer;
                            currentPerms = data.permissions;
                            terapkanHakAksesKeLayar(currentPerms, true);
                        }
                    }
                }
            } catch (e) {
            } finally {
                sedangCekPerm = false;
            }
        }

        // 3. LOGIKA MENU PENGAJUAN & 4 BUTTON PENGAJUAN (PEMINJAMAN, PENGEMBALIAN, PELEBURAN, SERVIS)
        const INFO_KATEGORI_PENGAJUAN = {
            peminjaman: {
                judul: 'Pengajuan Peminjaman',
                ikon: '📦',
                deskripsi: 'Kelola dan ajukan permohonan peminjaman aset inventaris antar cabang atau divisi.'
            },
            pengembalian: {
                judul: 'Pengajuan Pengembalian',
                ikon: '🔄',
                deskripsi: 'Kelola proses pengembalian aset yang telah selesai dipinjam beserta pengecekan kondisinya.'
            },
            peleburan: {
                judul: 'Pengajuan Peleburan',
                ikon: '🔥',
                deskripsi: 'Ajukan penghapusan atau peleburan aset fisik yang sudah rusak berat / tidak layak pakai.'
            },
            servis: {
                judul: 'Pengajuan Servis',
                ikon: '🛠️',
                deskripsi: 'Ajukan perbaikan dan perawatan berkala (maintenance) untuk aset yang bermasalah.'
            }
        };

        function pastikanPanelUtamaPengajuanAda() {
            const mainEl = document.querySelector('main');
            if (!mainEl) return null;

            let panel = document.getElementById('panel-utama-pengajuan');
            if (!panel) {
                panel = document.createElement('div');
                panel.id = 'panel-utama-pengajuan';
                panel.className = 'pengajuan-view-container';
                panel.innerHTML = `
                    <header class="main-header" style="border-bottom: 2px solid var(--border-color); padding-bottom: 15px;">
                        <div class="header-title">
                            <h1 style="font-size: 25px; font-weight: 800; color: var(--primary-color);">Menu Pengajuan Aset</h1>
                            <p style="font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 5px;">
                                Pilih jenis pengajuan aset di bawah ini (Peminjaman, Pengembalian, Peleburan, atau Servis).
                            </p>
                        </div>
                    </header>

                    <!-- 4 BUTTON UTAMA PENGAJUAN -->
                    <div class="pengajuan-top-buttons">
                        <button type="button" class="btn-tab-pengajuan active" data-tab-jenis="peminjaman" onclick="pilihKategoriPengajuan('peminjaman')">
                            <span>📦</span>
                            <span>Pengajuan Peminjaman</span>
                        </button>
                        <button type="button" class="btn-tab-pengajuan" data-tab-jenis="pengembalian" onclick="pilihKategoriPengajuan('pengembalian')">
                            <span>🔄</span>
                            <span>Pengajuan Pengembalian</span>
                        </button>
                        <button type="button" class="btn-tab-pengajuan" data-tab-jenis="peleburan" onclick="pilihKategoriPengajuan('peleburan')">
                            <span>🔥</span>
                            <span>Pengajuan Peleburan</span>
                        </button>
                        <button type="button" class="btn-tab-pengajuan" data-tab-jenis="servis" onclick="pilihKategoriPengajuan('servis')">
                            <span>🛠️</span>
                            <span>Pengajuan Servis</span>
                        </button>
                    </div>

                    <!-- KONTEN JENIS PENGAJUAN YANG DIPILIH -->
                    <div class="pengajuan-content-card" id="kartu-isi-pengajuan">
                        <h2 id="judul-isi-pengajuan" style="font-size: 18px; font-weight: 800; color: var(--primary-color); margin-bottom: 6px;">
                            📦 Pengajuan Peminjaman
                        </h2>
                        <p id="deskripsi-isi-pengajuan" style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
                            Kelola dan ajukan permohonan peminjaman aset inventaris antar cabang atau divisi.
                        </p>
                        <div style="padding: 30px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; text-align: center; color: #64748b; font-size: 13px; font-weight: 600;">
                            Silakan pilih atau kelola data pada kategori <strong id="label-kategori-aktif" style="color: var(--primary-color);">Pengajuan Peminjaman</strong>.
                        </div>
                    </div>
                `;
                mainEl.prepend(panel);
            }
            return panel;
        }

        window.tampilkanViewPengajuanDiMain = function (jenis = 'peminjaman') {
            const mainEl = document.querySelector('main');
            if (!mainEl) return;

            const panel = pastikanPanelUtamaPengajuanAda();
            if (!panel) return;

            // Pastikan setiap tab button di dalam panel utama mengikuti izin currentPerms per pengajuan
            const listJenis = ['peminjaman', 'pengembalian', 'peleburan', 'servis'];
            listJenis.forEach(j => {
                const sk = 'pengajuan_' + j;
                const boleh = currentPerms && currentPerms[sk] ? !!currentPerms[sk].view : true;
                document.querySelectorAll(`.btn-tab-pengajuan[data-tab-jenis="${j}"]`).forEach(b => {
                    b.style.display = boleh ? 'flex' : 'none';
                });
            });


            // Sembunyikan elemen asli di dalam <main> selain panel pengajuan dan banner permission
            Array.from(mainEl.children).forEach(child => {
                if (child.id !== 'panel-utama-pengajuan' && child.id !== 'admin-perm-status-banner') {
                    if (!child.hasAttribute('data-original-display')) {
                        child.setAttribute('data-original-display', child.style.display || '');
                    }
                    child.style.display = 'none';
                }
            });

            panel.classList.add('active');

            // Atur highlight menu aktif di sidebar
            document.querySelectorAll('sidebar .menu-item').forEach(el => el.classList.remove('active'));
            const btnPengajuan = document.getElementById('btn-sidebar-pengajuan');
            if (btnPengajuan) btnPengajuan.classList.add('active');

            const info = INFO_KATEGORI_PENGAJUAN[jenis] || INFO_KATEGORI_PENGAJUAN.peminjaman;

            document.querySelectorAll('.submenu-pengajuan-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-jenis') === jenis);
            });
            document.querySelectorAll('.btn-tab-pengajuan').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab-jenis') === jenis);
            });

            const kartuIsi = document.getElementById('kartu-isi-pengajuan');
            const subKeyCek = 'pengajuan_' + jenis;
            const subDiizinkan = (!currentPerms || !currentPerms[subKeyCek]) ? true : !!currentPerms[subKeyCek].view;
            if (!subDiizinkan && kartuIsi) {
                kartuIsi.innerHTML = `
                    <div style="padding: 38px 20px; text-align: center;">
                        <div style="font-size: 42px; margin-bottom: 10px;">🚫</div>
                        <h3 style="color: #0f172a; font-size: 19px; font-weight: 800; margin-bottom: 8px;">Hak Akses ${info.judul} Dicabut</h3>
                        <p style="color: #64748b; font-size: 13px; max-width: 480px; margin: 0 auto 18px auto; line-height: 1.6;">
                            Master Admin telah menutup / menonaktifkan akses pada <strong>${info.judul}</strong> untuk role Anda secara real-time.
                        </p>
                        <a href="{{ route('admin.dashboard') }}" style="display: inline-block; background: #1a4f91; color: #fff; text-decoration: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 700;">
                            Kembali ke Dashboard
                        </a>
                    </div>
                `;
                return;
            }
            if (kartuIsi && !document.getElementById('judul-isi-pengajuan')) {
                kartuIsi.innerHTML = `
                    <h2 id="judul-isi-pengajuan" style="font-size: 18px; font-weight: 800; color: var(--primary-color); margin-bottom: 6px;"></h2>
                    <p id="deskripsi-isi-pengajuan" style="font-size: 13px; color: #64748b; margin-bottom: 16px;"></p>
                    <div style="padding: 30px 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; text-align: center; color: #64748b; font-size: 13px; font-weight: 600;">
                        Silakan pilih atau kelola data pada kategori <strong id="label-kategori-aktif" style="color: var(--primary-color);"></strong>.
                    </div>
                `;
            }
            const judulEl = document.getElementById('judul-isi-pengajuan');
            const deskEl = document.getElementById('deskripsi-isi-pengajuan');
            const labelAktifEl = document.getElementById('label-kategori-aktif');
            if (judulEl) judulEl.textContent = `${info.ikon} ${info.judul}`;
            if (deskEl) deskEl.textContent = info.deskripsi;
            if (labelAktifEl) labelAktifEl.textContent = info.judul;
        };

        const URL_HALAMAN_PENGAJUAN = @json(url('/pengajuan'));

        window.toggleMenuPengajuanSidebar = function (e) {
            if (e) e.preventDefault();
            const sedangDiHalamanPengajuan = window.location.pathname.toLowerCase().includes('/pengajuan');
            if (!sedangDiHalamanPengajuan) {
                window.location.href = URL_HALAMAN_PENGAJUAN;
                return;
            }
            const wrap = document.getElementById('wrap-menu-pengajuan');
            if (!wrap) return;
            wrap.classList.toggle('open');
        };

        window.pilihKategoriPengajuan = function (jenis) {
            const targetJenis = jenis || 'peminjaman';
            const sedangDiHalamanPengajuan = window.location.pathname.toLowerCase().includes('/pengajuan');
            if (sedangDiHalamanPengajuan && typeof window.bukaTabPengajuan === 'function') {
                window.bukaTabPengajuan(targetJenis, true);
                return;
            }
            window.location.href = URL_HALAMAN_PENGAJUAN + '?jenis=' + encodeURIComponent(targetJenis);
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.hitungRiwayatBelumDibaca();
            terapkanHakAksesKeLayar(currentPerms, false);
            setInterval(cekPerubahanPermissionRealtime, 2000);

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('pengajuan')) {
                window.pilihKategoriPengajuan(urlParams.get('pengajuan') || 'peminjaman');
            }
        });
    })();
</script>