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

    @media (max-width: 768px) {
        sidebar {
            transform: translateX(-100%);
        }
    }
</style>

@php
    // 1. Hitung otomatis jumlah aset yang rusak / menuju rusak atau sedang / mendekati jadwal servis (<= 30 hari)
    $hariIniNotif = \Carbon\Carbon::today();
    $batasMendekatiNotif = $hariIniNotif->copy()->addDays(30)->toDateString();

    $jumlahNotifSidebar = \App\Models\DetailAset::where(function ($sub) use ($batasMendekatiNotif) {
        $sub->where(function ($s1) use ($batasMendekatiNotif) {
            $s1->whereNotNull('servis_berikutnya')
               ->where('servis_berikutnya', '!=', '-')
               ->where('servis_berikutnya', '<=', $batasMendekatiNotif);
        })->orWhere('status_aset', 'like', '%Rusak%');
    })->count();

    // 2. Ambil daftar ID seluruh Riwayat Aktivitas (diurutkan dari terbaru) untuk mendeteksi riwayat baru
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
            foreach (['\App\Models\RiwayatAktivitas', '\App\Models\Riwayat', '\App\Models\ActivityLog', '\App\Models\LogAktivitas'] as $modelClass) {
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
        				<span class="master-admin">Admin</span>
      			</div>
    		</div>

    		<div class="sidebar-menu">
      			<div class="menu-section-title">MENU UTAMA</div>
      			<a href="{{ route('admin.dashboard') }}" class="menu-item {{ Request::is('admin*') || Request::is('admin-dashboard*') ? 'active' : '' }}">
        				<!-- Ikon Dashboard -->
        				<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        				Dashboard
      			</a>
      			<a href="{{ route('notifikasi.index') }}" class="menu-item {{ Request::is('notifikasi*') || request()->routeIs('notifikasi.*') ? 'active' : '' }}">
        				<!-- Ikon Notifikasi -->
        				<svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        				<span>Notifikasi</span>
        				@if($jumlahNotifSidebar > 0)
        				    <span class="notif-badge" title="{{ $jumlahNotifSidebar }} aset rusak atau mendekati jadwal servis">{{ $jumlahNotifSidebar }}</span>
        				@endif
      			</a>

      			<div class="menu-section-title">MANAJEMEN ASET</div>
      			<a href="{{ route('aset.index') }}" class="menu-item {{ request()->routeIs('aset.index') ? 'active' : '' }}">
    <!-- Ikon Data Aset -->
    <svg viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM10 4h4v2h-4V4zm10 15H4V8h16v11z"/></svg>
    Data Aset
</a>
      			<a href="{{ route('barcode_aset.index') }}" class="menu-item {{ Request::is('barcode-aset') ? 'active' : '' }}">
    <!-- Ikon Barcode Aset -->
    <svg viewBox="0 0 24 24"><path d="M2 5h2v14H2V5zm4 0h1v14H6V5zm3 0h3v14H9V5zm4 0h2v14h-2V5zm3 0h1v14h-1V5zm3 0h2v14h-2V5z"/></svg>
    Barcode Aset
</a>

      			<div class="menu-section-title">MASTER DATA</div>
      			<a href="{{ route('kategori_aset.index') }}" class="menu-item {{ Request::is('kategori-aset') ? 'active' : '' }}">
        				<!-- Ikon Kategori Aset -->
        				<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        				Kategori Aset
      			</a>
      		
      		<a href="{{ route('cabang.index') }}" class="menu-item {{ Request::is('cabang') ? 'active' : '' }}">
        			<!-- Ikon Cabang -->
        			<svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
        			Cabang
      		</a>
      			<a href="{{ route('divisi.index') }}" class="menu-item {{ Request::is('divisi') ? 'active' : '' }}">
        				<!-- Ikon Divisi -->
        				<svg viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
        				Divisi
      			</a>
      			<a href="{{ route('jabatan.index') }}" class="menu-item {{ Request::is('jabatan') ? 'active' : '' }}">
        				<!-- Ikon Jabatan -->
        				<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        				Jabatan
      			</a>

      			<div class="menu-section-title">LAPORAN & PEMANTAUAN</div>
      			<a href="{{ route('laporan.index') }}" class="menu-item {{ Request::is('laporan') ? 'active' : '' }}">
        				<!-- Ikon Laporan -->
        				<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h２v7zm4 0h-２V7h２v１０zm4 0h-２v-４h２v４z"/></svg>
        				Laporan
      			</a>
      			<a href="{{ route('riwayat_aktivitas.index') }}" class="menu-item {{ Request::is('riwayat-aktivitas*') || request()->routeIs('riwayat_aktivitas.*') ? 'active' : '' }}">
        				<!-- Ikon Riwayat Aktivitas -->
        				<svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
        				<span>Riwayat Aktivitas</span>
        				<span id="badge-riwayat-sidebar" class="notif-badge" style="display: none;" title="Riwayat aktivitas baru belum dibaca">0</span>
      			</a>
                
    		</div>

    		<div class="sidebar-user">
    <!-- 1. Avatar -->
    <div class="user-avatar">{{ substr(Auth::user()->nama_lengkap, 0, 1) }}</div>
    
    <!-- 2. Info Profil -->
    <div class="user-info">
        <div class="user-name">{{ Auth::user()->nama_lengkap }}</div>
        <div class="user-role">{{ str_replace('_', ' ', Str::title(Auth::user()->role)) }}</div>
    </div>

    <!-- 3. Tombol Logout (Di sebelah kanan) -->
    <div style="margin-left: auto;">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0; display: flex;">
            @csrf
            <button type="submit" title="Logout" style="border: none; background: none; cursor: pointer; color: #dc2626; padding: 5px; display: flex; align-items: center; justify-content: center; transition: opacity 0.2s;">
                <!-- Ikon Logout Saja -->
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
        const STORAGE_KEY_RIWAYAT = 'riwayat_terbaca_auto_user_{{ Auth::id() ?? "guest" }}';
        const semuaIdRiwayatServer = @json($daftarIdRiwayat).map(String);
        const adaAksiBaruServer = @json($adaAksiBaru);

        function inisialisasiJikaBelumAda() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY_RIWAYAT);
                if (raw === null) {
                    // Saat pertama kali dijalankan, tandai riwayat lama sebagai sudah dibaca
                    // agar hanya aktivitas baru yang memunculkan angka 1, 2, dst.
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

        document.addEventListener('DOMContentLoaded', function () {
            window.hitungRiwayatBelumDibaca();
        });
    })();
</script>