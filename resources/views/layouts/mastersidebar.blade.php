@php

    $__roleLoginSaatIni = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';
    $__isAkunMasterAdmin = ($__roleLoginSaatIni === 'master_admin' || $__roleLoginSaatIni === 'masteradmin');
    $__uriSekarang = strtolower(trim((string) request()->path(), '/'));

    $__isHalamanKhususMaster = ($__uriSekarang === 'masteradmin' || str_starts_with($__uriSekarang, 'masteradmin/dashboard') || str_starts_with($__uriSekarang, 'roles') || str_starts_with($__uriSekarang, 'kelola-akun') || request()->routeIs('masteradmin.dashboard', 'roles.*', 'kelola_akun.*'));

    if (!$__isAkunMasterAdmin && $__isHalamanKhususMaster) {
        if (class_exists(\App\Models\RiwayatAktivitas::class) && \Illuminate\Support\Facades\Auth::check()) {
            try {
                $namaMenuTerlarang = str_starts_with($__uriSekarang, 'roles')
                    ? 'Roles & Permission'
                    : (str_starts_with($__uriSekarang, 'kelola-akun') ? 'Kelola Akun' : 'Dashboard Master Admin');
                $logPelanggaran = new \App\Models\RiwayatAktivitas();
                $tblPlg = $logPelanggaran->getTable();
                $dataPlg = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblPlg, 'user_id')) {
                    $dataPlg['user_id'] = \Illuminate\Support\Facades\Auth::id();
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblPlg, 'aktivitas')) {
                    $dataPlg['aktivitas'] = 'Mencoba mengakses menu ' . $namaMenuTerlarang . ' tanpa hak akses';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblPlg, 'modul')) {
                    $dataPlg['modul'] = $namaMenuTerlarang;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblPlg, 'status')) {
                    $dataPlg['status'] = 'Peringatan';
                }
                $logPelanggaran->forceFill($dataPlg)->save();
            } catch (\Throwable $e) {
            }
        }
        $urlDashAdmin = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin-dashboard');
        header('Location: ' . $urlDashAdmin);
        exit;
    }

    $__isHalamanKhususAdmin = (str_starts_with($__uriSekarang, 'admin-dashboard') || str_starts_with($__uriSekarang, 'admin/dashboard') || request()->routeIs('admin.dashboard'));
    if ($__isAkunMasterAdmin && $__isHalamanKhususAdmin) {
        $urlDashMaster = \Illuminate\Support\Facades\Route::has('masteradmin.dashboard') ? route('masteradmin.dashboard') : url('/masteradmin');
        header('Location: ' . $urlDashMaster);
        exit;
    }

    $__pakaiAdminSidebar = !$__isAkunMasterAdmin;
@endphp
@if($__pakaiAdminSidebar)
    @include('layouts.adminsidebar')
@else
<style>
    :root {
        --primary-color: #1a4f91;
        --secondary-color: #10b981;
        --warning-color: #f59e0b;
        --bg-light: #f8fafc;
        --text-color: #000;
        --text-muted: rgba(0, 0, 0, 0.45);
        --border-color: rgba(0, 0, 0, 0.15);
        --sidebar-width: 270px;
    }

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

    @media (max-width: 768px) {
        sidebar {
            transform: translateX(-100%);
        }
    }

    .nav-loading-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 2147483647;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        cursor: wait !important;
        user-select: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }

    .nav-loading-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: all !important;
    }

    .nav-top-progress-track {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(255, 255, 255, 0.15);
        overflow: hidden;
        z-index: 2147483647;
    }

    .nav-top-progress-bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #38bdf8, #1a4f91, #10b981);
        box-shadow: 0 0 10px rgba(56, 189, 248, 0.8);
        transition: width 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .nav-loading-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-top: 4px solid var(--primary-color);
        border-radius: 18px;
        padding: 28px 34px;
        max-width: 390px;
        width: calc(100% - 32px);
        text-align: center;
        box-shadow: 0 22px 45px rgba(15, 23, 42, 0.28);
        transform: scale(0.94) translateY(8px);
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        pointer-events: none;
    }

    .nav-loading-overlay.active .nav-loading-card {
        transform: scale(1) translateY(0);
    }

    .nav-spinner-wrap {
        position: relative;
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-spinner-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 4px solid #e0f2fe;
        border-top-color: var(--primary-color);
        border-right-color: #38bdf8;
        animation: navSpinRing 0.75s linear infinite;
    }

    .nav-spinner-ring-inner {
        position: absolute;
        inset: 9px;
        border-radius: 50%;
        border: 3px solid transparent;
        border-bottom-color: var(--secondary-color);
        animation: navSpinRingReverse 1.1s linear infinite;
    }

    .nav-spinner-icon {
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @keyframes navSpinRing {
        to { transform: rotate(360deg); }
    }

    @keyframes navSpinRingReverse {
        to { transform: rotate(-360deg); }
    }

    .nav-loading-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.2px;
    }

    .nav-loading-subtitle {
        font-size: 12.5px;
        font-weight: 500;
        color: #64748b;
        line-height: 1.5;
    }

    .nav-network-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 700;
        color: #334155;
        margin-top: 2px;
    }

    .nav-network-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #10b981;
        animation: navPulseDot 1.2s ease-in-out infinite;
    }

    @keyframes navPulseDot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.8); }
    }

</style>

@php

    $hariIniNotif = \Carbon\Carbon::today();
    $batasMendekatiNotif = $hariIniNotif->copy()->addDays(30)->toDateString();

    $jumlahNotifSidebar = \App\Models\DetailAset::where(function ($sub) use ($batasMendekatiNotif) {
        $sub->where(function ($s1) use ($batasMendekatiNotif) {
            $s1->whereNotNull('servis_berikutnya')
               ->where('servis_berikutnya', '!=', '-')
               ->where('servis_berikutnya', '<=', $batasMendekatiNotif);
        })->orWhere('status_aset', 'like', '%Rusak%');
    })->count();

    try {
        $resetReqFileSb = storage_path('app/password_reset_requests.json');
        if (is_file($resetReqFileSb)) {
            $listReqSb = json_decode((string) file_get_contents($resetReqFileSb), true);
            if (is_array($listReqSb)) {
                $jmlPendingSb = count(array_filter($listReqSb, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
                $jumlahNotifSidebar += $jmlPendingSb;
            }
        }
    } catch (\Throwable $e) {
    }

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
            foreach ([\App\Models\RiwayatAktivitas::class, \App\Models\Riwayat::class, \App\Models\ActivityLog::class, \App\Models\LogAktivitas::class] as $modelClass) {
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
        				<span class="master-admin">Master Admin</span>
      			</div>
    		</div>

    		<div class="sidebar-menu">
      			<div class="menu-section-title">MENU UTAMA</div>
      			<a href="{{ route('masteradmin.dashboard') }}" class="menu-item {{ Request::is('masteradmin') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        				Dashboard
      			</a>
      			<a href="{{ route('notifikasi.index') }}" class="menu-item {{ Request::is('notifikasi*') || request()->routeIs('notifikasi.*') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        				<span>Notifikasi</span>
        				@if($jumlahNotifSidebar > 0)
        				    <span class="notif-badge" title="{{ $jumlahNotifSidebar }} aset rusak atau mendekati jadwal servis">{{ $jumlahNotifSidebar }}</span>
        				@endif
      			</a>

      			<div class="menu-section-title">MANAJEMEN ASET</div>
      			<a href="{{ route('aset.index') }}" class="menu-item {{ request()->routeIs('aset.index') ? 'active' : '' }}">

    <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm10 15H4V8h16v11z"/></svg>
    Data Aset
</a>
      			<a href="{{ route('barcode_aset.index') }}" class="menu-item {{ Request::is('barcode-aset') ? 'active' : '' }}">

    <svg viewBox="0 0 24 24"><path d="M2 5h2v14H2V5zm4 0h1v14H6V5zm3 0h3v14H9V5zm4 0h2v14h-2V5zm3 0h1v14h-1V5zm3 0h2v14h-2V5z"/></svg>
    Barcode Aset
</a>

      			<div class="menu-section-title">MASTER DATA</div>
      			<a href="{{ route('kategori_aset.index') }}" class="menu-item {{ Request::is('kategori-aset') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        				Kategori Aset
      			</a>

      		<a href="{{ route('cabang.index') }}" class="menu-item {{ Request::is('cabang') ? 'active' : '' }}">

        			<svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
        			Cabang
      		</a>

      			<div class="menu-section-title">AKSES & KEAMANAN</div>
      			<a href="{{ route('kelola_akun.index') }}" class="menu-item {{ Request::is('kelola-akun') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        				Kelola Akun
      			</a>
      			<a href="{{ route('roles.index') }}" class="menu-item {{ Request::is('roles') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
        				Roles & Permission
      			</a>

      			<div class="menu-section-title">LAPORAN & PEMANTAUAN</div>
      			<a href="{{ route('laporan.index') }}" class="menu-item {{ Request::is('laporan*') || request()->routeIs('laporan.*') ? 'active' : '' }}">
        				<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
        				Laporan
      			</a>
      			<a href="{{ route('riwayat_aktivitas.index') }}" class="menu-item {{ Request::is('riwayat-aktivitas*') || request()->routeIs('riwayat_aktivitas.*') ? 'active' : '' }}">

        				<svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
        				<span>Riwayat Aktivitas</span>
        				<span id="badge-riwayat-sidebar" class="notif-badge" style="display: none;" title="Riwayat aktivitas baru belum dibaca">0</span>
      			</a>

    		</div>

    		<div class="sidebar-user">

    <div class="user-avatar">{{ substr(Auth::user()->nama_lengkap, 0, 1) }}</div>

    <div class="user-info">
        <div class="user-name">{{ Auth::user()->nama_lengkap }}</div>
        <div class="user-role">{{ str_replace('_', ' ', Str::title(Auth::user()->role)) }}</div>
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

<div id="global-page-nav-loader" class="nav-loading-overlay" aria-live="polite" aria-busy="true">
    <div class="nav-top-progress-track">
        <div id="nav-top-progress-bar" class="nav-top-progress-bar"></div>
    </div>
    <div class="nav-loading-card">
        <div class="nav-spinner-wrap">
            <div class="nav-spinner-ring"></div>
            <div class="nav-spinner-ring-inner"></div>
            <div class="nav-spinner-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                </svg>
            </div>
        </div>
        <div>
            <div id="nav-loading-title" class="nav-loading-title">Memuat Data Halaman...</div>
            <div id="nav-loading-subtitle" class="nav-loading-subtitle" style="margin-top: 4px;">
                Sedang menyiapkan dan memuat data dari server, mohon tunggu...
            </div>
        </div>
        <div class="nav-network-pill">
            <span id="nav-network-dot" class="nav-network-dot"></span>
            <span id="nav-network-status">Menyesuaikan kecepatan jaringan...</span>
        </div>
    </div>
</div>

<input type="hidden" id="json-daftar-id-riwayat-master" value="{{ json_encode($daftarIdRiwayat) }}">
<input type="hidden" id="json-ada-aksi-baru-master" value="{{ json_encode($adaAksiBaru) }}">

<script>
    (function () {
        const STORAGE_KEY_RIWAYAT = 'riwayat_terbaca_auto_user_{{ Auth::id() ?? "guest" }}';
        const semuaIdRiwayatServer = JSON.parse(document.getElementById('json-daftar-id-riwayat-master').value || '[]').map(String);
        const adaAksiBaruServer = JSON.parse(document.getElementById('json-ada-aksi-baru-master').value || 'false');

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
            if (Array.isArray(window.__extraScopedRiwayatIds)) {
                window.__extraScopedRiwayatIds.forEach(function (id) {
                    if (id) setIds.add(String(id));
                });
            }
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

            const badgeBtnBaca = document.getElementById('badge-btn-baca-semua');
            if (badgeBtnBaca) {
                if (belumDibaca > 0) {
                    badgeBtnBaca.textContent = String(belumDibaca);
                    badgeBtnBaca.style.display = 'inline-block';
                } else {
                    badgeBtnBaca.textContent = '0';
                    badgeBtnBaca.style.display = 'none';
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

        window.tandaiSemuaRiwayatDibaca = function (extraIds) {
            if (Array.isArray(extraIds)) {
                window.__extraScopedRiwayatIds = extraIds.map(String);
            }
            const semuaId = ambilSemuaIdRiwayatAktif();
            const sudahDibaca = ambilIdRiwayatDibaca();
            simpanIdRiwayatDibaca(sudahDibaca.concat(semuaId));
            window.hitungRiwayatBelumDibaca();
        };

        let sedangPindahHalaman = false;
        let sedangUnloadHalaman = false;
        let jumlahRequestDataAktif = 0;
        let timerProgresJaringan = null;
        let waktuMulaiNavigasi = 0;

        function deteksiInfoKoneksiJaringan() {
            const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            if (conn) {
                const tipe = (conn.effectiveType || '').toUpperCase();
                const rtt = typeof conn.rtt === 'number' && conn.rtt > 0 ? `${conn.rtt} ms` : '';
                if (tipe || rtt) {
                    return `Jaringan ${tipe || 'Aktif'}${rtt ? ' • Ping ' + rtt : ''}`;
                }
            }
            return 'Menunggu respons jaringan server...';
        }

        function apakahRequestHarusTampilkanLoader(urlInput, methodInput) {
            const strUrl = String(urlInput || '');
            const method = String(methodInput || 'GET').toUpperCase();

            if (
                strUrl.includes('_check_permissions_rt') ||
                strUrl.includes('_perm_sync=') ||
                strUrl.includes('_poll_riwayat_realtime') ||
                strUrl.includes('_riwayat_realtime=') ||
                strUrl.includes('poll_requests') ||
                strUrl.includes('_notif_reset_action') ||
                strUrl.includes('_login_lock_action') ||
                strUrl.includes('_cache_lampiran_info') ||
                strUrl.includes('_sync_lampiran_meta') ||
                strUrl.includes('view-lampiran') ||
                strUrl.includes('_log_cetak_laporan') ||
                strUrl.includes('_roles_action=save_permissions')
            ) {
                return false;
            }

            if (method !== 'GET') {
                return true;
            }

            return strUrl.includes('_bulk_delete_aset') || strUrl.includes('_clear_riwayat_scoped');
        }

        window.tampilkanLoadingPindahHalaman = function (judulKustom, subjudulKustom) {
            const overlay = document.getElementById('global-page-nav-loader');
            if (!overlay) return;

            sedangPindahHalaman = true;
            waktuMulaiNavigasi = Date.now();

            const titleEl = document.getElementById('nav-loading-title');
            const subEl = document.getElementById('nav-loading-subtitle');
            const netStatusEl = document.getElementById('nav-network-status');
            const netDotEl = document.getElementById('nav-network-dot');
            const barEl = document.getElementById('nav-top-progress-bar');

            if (titleEl) titleEl.textContent = judulKustom || 'Memuat Data Halaman...';
            if (subEl) subEl.textContent = subjudulKustom || 'Sedang menyiapkan dan memuat data dari server, mohon tunggu...';
            if (netStatusEl) netStatusEl.textContent = deteksiInfoKoneksiJaringan();
            if (netDotEl) netDotEl.style.background = '#10b981';

            overlay.classList.add('active');
            document.body.style.cursor = 'wait';

            let progresSaatIni = 18;
            if (barEl) barEl.style.width = progresSaatIni + '%';

            if (timerProgresJaringan) clearInterval(timerProgresJaringan);
            timerProgresJaringan = setInterval(function () {
                const durasiMs = Date.now() - waktuMulaiNavigasi;
                const sisa = 92 - progresSaatIni;
                if (sisa > 0.5) {
                    progresSaatIni += Math.max(0.4, sisa * 0.08);
                    if (barEl) barEl.style.width = progresSaatIni.toFixed(1) + '%';
                }

                if (durasiMs > 4500) {
                    if (titleEl) titleEl.textContent = 'Koneksi Jaringan Sedang Lambat...';
                    if (subEl) subEl.textContent = 'Permintaan Anda sedang diproses oleh server. Halaman akan terbuka otomatis begitu data selesai dimuat.';
                    if (netStatusEl) netStatusEl.textContent = `Waktu tunggu: ${(durasiMs / 1000).toFixed(1)} dtk • Mohon tetap tunggu`;
                    if (netDotEl) netDotEl.style.background = '#f59e0b';
                } else if (durasiMs > 1600) {
                    if (subEl) subEl.textContent = 'Sedang memuat data halaman dari server sesuai kecepatan jaringan Anda...';
                    if (netStatusEl) netStatusEl.textContent = `Memuat data (${(durasiMs / 1000).toFixed(1)} dtk)...`;
                }
            }, 150);
        };

        window.sembunyikanLoadingPindahHalaman = function () {
            sedangUnloadHalaman = false;
            sedangPindahHalaman = false;
            if (timerProgresJaringan) {
                clearInterval(timerProgresJaringan);
                timerProgresJaringan = null;
            }
            const barEl = document.getElementById('nav-top-progress-bar');
            if (barEl) barEl.style.width = '100%';

            const overlay = document.getElementById('global-page-nav-loader');
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.style.cursor = '';
        };

        if (document.readyState === 'loading') {
            window.tampilkanLoadingPindahHalaman('Memuat Data Halaman...', 'Sedang menyiapkan dan memuat data dari server, mohon tunggu...');
        }

        if (typeof window.fetch === 'function') {
            const fetchAsli = window.fetch.bind(window);
            window.fetch = function (input, init) {
                const urlTarget = typeof input === 'string' ? input : (input && input.url ? input.url : '');
                const methodTarget = (init && init.method) ? init.method : (input && input.method ? input.method : 'GET');
                const gunakanLoader = apakahRequestHarusTampilkanLoader(urlTarget, methodTarget);

                if (gunakanLoader) {
                    jumlahRequestDataAktif++;
                    if (!sedangPindahHalaman) {
                        window.tampilkanLoadingPindahHalaman('Memuat Data...', 'Sedang memproses dan memuat data dari server, mohon tunggu...');
                    }
                }

                return fetchAsli(input, init).finally(function () {
                    if (gunakanLoader) {
                        jumlahRequestDataAktif = Math.max(0, jumlahRequestDataAktif - 1);
                        setTimeout(function () {
                            if (jumlahRequestDataAktif === 0 && !sedangUnloadHalaman) {
                                window.sembunyikanLoadingPindahHalaman();
                            }
                        }, 80);
                    }
                });
            };
        }

        if (typeof window.XMLHttpRequest === 'function') {
            const openAsli = XMLHttpRequest.prototype.open;
            const sendAsli = XMLHttpRequest.prototype.send;

            XMLHttpRequest.prototype.open = function (method, url) {
                this._methodTargetLoader = String(method || 'GET');
                this._urlTargetLoader = String(url || '');
                return openAsli.apply(this, arguments);
            };

            XMLHttpRequest.prototype.send = function () {
                const gunakanLoader = apakahRequestHarusTampilkanLoader(this._urlTargetLoader, this._methodTargetLoader);
                if (gunakanLoader) {
                    jumlahRequestDataAktif++;
                    if (!sedangPindahHalaman) {
                        window.tampilkanLoadingPindahHalaman('Memuat Data...', 'Sedang memproses dan memuat data dari server, mohon tunggu...');
                    }
                    this.addEventListener('loadend', function () {
                        jumlahRequestDataAktif = Math.max(0, jumlahRequestDataAktif - 1);
                        setTimeout(function () {
                            if (jumlahRequestDataAktif === 0 && !sedangUnloadHalaman) {
                                window.sembunyikanLoadingPindahHalaman();
                            }
                        }, 80);
                    }, { once: true });
                }
                return sendAsli.apply(this, arguments);
            };
        }

        ['click', 'dblclick', 'submit'].forEach(function (evtName) {
            window.addEventListener(evtName, function (e) {
                if (sedangPindahHalaman) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                    return false;
                }
            }, true);
        });

        window.addEventListener('keydown', function (e) {
            if (sedangPindahHalaman && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        document.addEventListener('click', function (e) {
            if (sedangPindahHalaman || e.defaultPrevented) return;
            if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

            const linkEl = e.target.closest('a[href]');
            if (!linkEl) return;

            const rawHref = (linkEl.getAttribute('href') || '').trim();
            if (!rawHref || rawHref === '#' || rawHref.toLowerCase().startsWith('javascript:')) return;
            if (linkEl.hasAttribute('download') || linkEl.getAttribute('target') === '_blank') return;
            if (rawHref.includes('download-lampiran') || rawHref.includes('view-lampiran')) return;

            let targetUrl;
            try {
                targetUrl = new URL(linkEl.href, window.location.origin);
            } catch (err) {
                return;
            }

            if (targetUrl.origin !== window.location.origin) return;

            if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash !== '') {
                return;
            }

            const teksMenu = (linkEl.textContent || '').replace(/\s+/g, ' ').trim();
            const judulLoad = teksMenu && teksMenu.length < 35
                ? `Membuka ${teksMenu}...`
                : 'Memuat Halaman...';

            sedangUnloadHalaman = true;
            window.tampilkanLoadingPindahHalaman(judulLoad);
        }, false);

        document.addEventListener('submit', function (e) {
            if (sedangPindahHalaman || e.defaultPrevented) return;
            const formEl = e.target;
            if (!formEl || formEl.tagName !== 'FORM') return;
            if (formEl.getAttribute('target') === '_blank') return;

            const idForm = formEl.id || '';
            if (idForm === 'form-tambah-role' || idForm === 'form-clear-riwayat') return;

            setTimeout(function () {
                if (!e.defaultPrevented && !sedangPindahHalaman) {
                    sedangUnloadHalaman = true;
                    window.tampilkanLoadingPindahHalaman('Memproses & Memuat Data...', 'Mohon tunggu, permintaan sedang diproses oleh server.');
                }
            }, 0);
        }, false);

        window.addEventListener('beforeunload', function () {
            sedangUnloadHalaman = true;
            if (!sedangPindahHalaman) {
                window.tampilkanLoadingPindahHalaman('Memuat Data Halaman...', 'Sedang memuat data dari server, mohon tunggu...');
            }
        });

        window.addEventListener('pageshow', function () {
            window.sembunyikanLoadingPindahHalaman();
        });

        window.addEventListener('load', function () {
            window.sembunyikanLoadingPindahHalaman();
        });

        document.addEventListener('DOMContentLoaded', function () {
            window.sembunyikanLoadingPindahHalaman();
            window.hitungRiwayatBelumDibaca();
        });
    })();
</script>
@endif
