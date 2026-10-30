<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas - Sistem Master Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
    
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Main Content Layout */
        main {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: calc(100% - var(--sidebar-width));
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 5px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: #fff;
            border: none;
        }

        .btn-danger-soft {
            background: #fee2e2;
            color: var(--danger-color);
            border: none;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Table Section Styles */
        .card-box {
            background: #fff;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            width: 100%;
        }

        .search-box {
            background: #f1f5f9;
            padding: 8px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-grow: 1;
            min-width: 250px;
            border: 1px solid var(--border-color);
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 13px;
        }

        .filter-select {
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            font-size: 13px;
            outline: none;
            color: var(--text-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            font-size: 12px;
            color: var(--text-muted);
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            letter-spacing: 0.5px;
            background: #f8fafc;
        }

        .data-table td {
            font-size: 13px;
            padding: 14px 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            transition: background-color 1s ease, border-color 1s ease, padding-left 0.4s ease;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        /* Warna Baris Berbeda untuk Riwayat Baru (Otomatis Menghilang Setelah Terbaca) */
        .riwayat-row {
            background-color: transparent;
            transition: background-color 1s ease;
        }

        .riwayat-row.belum-dibaca {
            background-color: #fff1f2;
        }

        .riwayat-row.belum-dibaca td:first-child {
            border-left: 4px solid var(--danger-color);
            padding-left: 11px;
        }

        .riwayat-row.belum-dibaca td {
            font-weight: 600;
        }

        .badge-baru {
            display: inline-block;
            background-color: var(--danger-color);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            margin-left: 6px;
            vertical-align: middle;
            letter-spacing: 0.3px;
            opacity: 0;
            transform: scale(0.85);
            pointer-events: none;
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .riwayat-row.belum-dibaca .badge-baru {
            opacity: 1;
            transform: scale(1);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-info {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
        }

        /* ===== PAGINASI ===== */
        .custom-pagination-wrap { margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); }
        .custom-pagination-wrap nav > div:first-child { display: none !important; }
        .custom-pagination-wrap nav > div:last-child { display: flex !important; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; width: 100%; }
        .custom-pagination-wrap nav p { font-size: 13px !important; color: var(--text-muted) !important; margin: 0 !important; }
        .custom-pagination-wrap nav p span { font-weight: 800 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav > div:last-child > div:last-child > span { display: inline-flex !important; border-radius: 8px !important; border: 1px solid var(--border-color) !important; background: #fff !important; overflow: hidden !important; box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important; }
        .custom-pagination-wrap nav span > a, .custom-pagination-wrap nav span > span { display: inline-flex !important; align-items: center !important; justify-content: center !important; min-width: 38px !important; padding: 8px 12px !important; font-size: 13px !important; font-weight: 600 !important; color: #475569 !important; text-decoration: none !important; background: #fff !important; border-right: 1px solid var(--border-color) !important; cursor: pointer !important; transition: all 0.2s !important; margin: 0 !important; border-top: none !important; border-bottom: none !important; border-left: none !important; }
        .custom-pagination-wrap nav span > *:last-child { border-right: none !important; }
        .custom-pagination-wrap nav span > a:hover { background: #f1f5f9 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav span[aria-current="page"] > span { background: var(--primary-color) !important; color: #fff !important; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important; }
        .custom-pagination-wrap nav span[aria-disabled="true"] > span { color: #cbd5e1 !important; background: #f8fafc !important; cursor: not-allowed !important; }
        .custom-pagination-wrap nav svg { width: 16px !important; height: 16px !important; }
            /* ===== REAL-TIME NOTIFIKASI KHUSUS MASTER ADMIN ===== */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
            vertical-align: middle;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-live 1.5s infinite;
        }
        @keyframes pulse-live {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .realtime-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
            width: calc(100% - 48px);
            pointer-events: none;
        }
        .realtime-toast {
            pointer-events: auto;
            background: #ffffff;
            border-left: 5px solid var(--primary-color);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transform: translateX(120%);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
        }
        .realtime-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        .realtime-toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .realtime-toast-body {
            flex: 1;
            min-width: 0;
        }
        .realtime-toast-title {
            font-size: 12px;
            font-weight: 800;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
        }
        .realtime-toast-user {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .realtime-toast-text {
            font-size: 12px;
            color: #475569;
            line-height: 1.4;
            word-break: break-word;
        }
        .realtime-toast-meta {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 5px;
            font-weight: 600;
        }
        .realtime-toast-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            padding: 0 2px;
        }
        .realtime-toast-close:hover {
            color: var(--danger-color);
        }
    </style>
</head>
<body>

    @php
        if (request('panel') === 'admin') {
            session(['active_sidebar_panel' => 'admin']);
        } elseif (request('panel') === 'master') {
            session(['active_sidebar_panel' => 'masteradmin']);
        }
        $__roleUserSekarang = \Illuminate\Support\Facades\Auth::check()
            ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
            : '';
        $__gunakanAdminSidebar = ($__roleUserSekarang === 'admin') || session('active_sidebar_panel') === 'admin';
        // Fitur real-time notifikasi riwayat khusus untuk Master Admin
        $__isMasterAdminRiwayat = ($__roleUserSekarang === 'master_admin' || $__roleUserSekarang === 'masteradmin') && !$__gunakanAdminSidebar;
    @endphp

    <!-- Sidebar Navigation -->
    @if($__gunakanAdminSidebar)
        @include('layouts.adminsidebar')
    @else
        @include('layouts.mastersidebar')
    @endif

    @if($__isMasterAdminRiwayat)
        <div id="realtime-toast-container" class="realtime-toast-container"></div>
    @endif

    <!-- Main Content Layout -->
    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>
                    Riwayat Aktivitas 🕒
                    @if($__isMasterAdminRiwayat)
                        <span class="live-indicator" title="Pemantauan real-time aktif khusus Master Admin">
                            <span class="live-dot"></span> Live Real-Time
                        </span>
                    @endif
                </h1>
                <p>Pantau catatan log aktivitas, penambahan aset, dan perubahan data pada sistem secara real-time</p>
            </div>
            <div class="header-actions">
                <form action="{{ route('riwayat_aktivitas.clear') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membersihkan seluruh riwayat aktivitas?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger-soft">Bersihkan Log</button>
                </form>
            </div>
        </header>

        @if(session('success'))
            <div style="background: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; border-left: 4px solid #10b981;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Table Container -->
        <div class="card-box">
            <div class="table-toolbar">
                <form method="GET" action="{{ route('riwayat_aktivitas.index') }}" class="filter-form">
                    <div class="search-box">
                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: var(--text-muted);"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aktivitas atau nama pengguna...">
                    </div>

                    <!-- TAMBAHAN: Filter Jenis Aktivitas -->
                    <select name="jenis" class="filter-select">
                        <option value="">-- Semua Jenis Aktivitas --</option>
                        <option value="Tambah" {{ request('jenis') == 'Tambah' ? 'selected' : '' }}>Penambahan Data</option>
                        <option value="Ubah" {{ request('jenis') == 'Ubah' ? 'selected' : '' }}>Perubahan Data</option>
                        <option value="Hapus" {{ request('jenis') == 'Hapus' ? 'selected' : '' }}>Penghapusan Data</option>
                        <option value="Sistem" {{ request('jenis') == 'Sistem' ? 'selected' : '' }}>Aktivitas Sistem (Login/Lainnya)</option>
                    </select>

                    <!-- Filter Bulan -->
                    <select name="bulan" class="filter-select">
                        <option value="">-- Semua Bulan --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>

                    <!-- Filter Tahun -->
                    <select name="tahun" class="filter-select">
                        <option value="">-- Semua Tahun --</option>
                        @foreach($tahunList ?? [] as $thn)
                            <option value="{{ $thn }}" {{ request('tahun') == $thn ? 'selected' : '' }}>
                                {{ $thn }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(request('search') || request('bulan') || request('tahun') || request('jenis'))
                        <a href="{{ route('riwayat_aktivitas.index') }}" class="btn" style="background: #e2e8f0; color: #334155;">Reset</a>
                    @endif
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>WAKTU</th>
                            <th>PENGGUNA</th>
                            <th>AKTIVITAS</th>
                            <th>MODUL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody id="riwayat-tbody">
                        @forelse($riwayats as $riwayat)
                            @php
                                $idUnikRiwayat = (is_object($riwayat) && method_exists($riwayat, 'getKey') && $riwayat->getKey() !== null)
                                    ? $riwayat->getKey()
                                    : ($riwayat->id ?? md5(($riwayat->created_at ?? '') . ($riwayat->aktivitas ?? '') . $loop->index));
                            @endphp
                            <tr class="riwayat-row" data-riwayat-id="{{ $idUnikRiwayat }}">
                                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                    {{ $riwayat->created_at->format('d M Y, H:i') }}
                                    <span class="badge-baru">BARU</span>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $riwayat->user ? $riwayat->user->nama_lengkap : 'Sistem / Tamu' }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        {{ $riwayat->user ? str_replace('_', ' ', Str::title($riwayat->user->role)) : 'System' }}
                                    </div>
                                </td>
                                <td>{{ $riwayat->aktivitas }}</td>
                                <td><span style="font-weight: 500;">{{ $riwayat->modul }}</span></td>
                                <td>
                                    @if($riwayat->status == 'Berhasil')
                                        <span class="badge badge-success">Berhasil</span>
                                    @elseif($riwayat->status == 'Peringatan')
                                        <span class="badge badge-warning">Peringatan</span>
                                    @else
                                        <span class="badge badge-info">Informasi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">
                                    Belum ada catatan riwayat aktivitas yang terekam.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="custom-pagination-wrap">
                {{ $riwayats->links() }}
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const barisList = document.querySelectorAll('tr.riwayat-row[data-riwayat-id]');

            // 1. Tampilkan warna baris berbeda untuk riwayat terbaru yang belum dibaca
            barisList.forEach(function (tr) {
                const id = tr.getAttribute('data-riwayat-id');
                const sudahDibaca = typeof window.apakahRiwayatSudahDibaca === 'function'
                    ? window.apakahRiwayatSudahDibaca(id)
                    : true;

                if (!sudahDibaca) {
                    tr.classList.add('belum-dibaca');
                }
            });

            // Fungsi otomatis membaca baris tanpa perlu diklik saat halaman dibuka / baris terlihat
            function prosesOtomatisTerbaca(tr) {
                if (!tr || tr.getAttribute('data-sedang-dibaca') === '1') return;
                tr.setAttribute('data-sedang-dibaca', '1');

                const id = tr.getAttribute('data-riwayat-id');

                // Kurangi/hilangkan angka badge merah di sidebar secara otomatis saat halaman dibuka
                setTimeout(function () {
                    if (typeof window.tandaiSatuRiwayatDibaca === 'function') {
                        window.tandaiSatuRiwayatDibaca(id);
                    }
                }, 500);

                // Setelah user melihat warna baris berbeda (2,5 detik), warna baris menghilang otomatis
                setTimeout(function () {
                    tr.classList.remove('belum-dibaca');
                }, 2500);
            }

            // 2. Gunakan sensor keterlihatan layar (IntersectionObserver) agar otomatis terbaca tanpa diklik
            let observerRiwayat = null;
            if ('IntersectionObserver' in window) {
                observerRiwayat = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            prosesOtomatisTerbaca(entry.target);
                            obs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
            }

            function pantauBarisBelumDibaca(trElement) {
                if (!trElement) return;
                if (observerRiwayat) {
                    observerRiwayat.observe(trElement);
                } else {
                    prosesOtomatisTerbaca(trElement);
                }
            }

            document.querySelectorAll('tr.riwayat-row.belum-dibaca').forEach(function (tr) {
                pantauBarisBelumDibaca(tr);
            });

            // 3. FITUR REAL-TIME POLLING KHUSUS MASTER ADMIN (TANPA PERLU REFRESH)
            const IS_MASTER_ADMIN_RIWAYAT = @json($__isMasterAdminRiwayat);

            function tampilkanNotifRealtimeMasterAdmin(pengguna, role, aktivitas, modul, waktu) {
                const container = document.getElementById('realtime-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = 'realtime-toast';
                toast.innerHTML = `
                    <div class="realtime-toast-icon">🔔</div>
                    <div class="realtime-toast-body">
                        <div class="realtime-toast-title">Aktivitas Baru Masuk</div>
                        <div class="realtime-toast-user">${pengguna} <span style="font-weight:500; color:#64748b; font-size:11px;">(${role})</span></div>
                        <div class="realtime-toast-text">${aktivitas}</div>
                        <div class="realtime-toast-meta">${modul} • ${waktu || 'Baru saja'}</div>
                    </div>
                    <button type="button" class="realtime-toast-close" title="Tutup">&times;</button>
                `;

                toast.querySelector('.realtime-toast-close').addEventListener('click', function () {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                });

                container.prepend(toast);
                requestAnimationFrame(() => {
                    toast.classList.add('show');
                });

                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 350);
                    }
                }, 6000);
            }

            if (IS_MASTER_ADMIN_RIWAYAT) {
                const knownRiwayatIds = new Set();
                document.querySelectorAll('tr.riwayat-row[data-riwayat-id]').forEach(function (tr) {
                    knownRiwayatIds.add(String(tr.getAttribute('data-riwayat-id')));
                });

                let sedangPolling = false;

                async function cekRiwayatTerbaruRealtime() {
                    if (sedangPolling || document.hidden) return;
                    sedangPolling = true;

                    try {
                        const urlPoll = new URL(window.location.href);
                        urlPoll.searchParams.set('_rt_poll', Date.now());

                        const res = await fetch(urlPoll.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Cache-Control': 'no-cache'
                            }
                        });

                        if (!res.ok) {
                            sedangPolling = false;
                            return;
                        }

                        const htmlText = await res.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlText, 'text/html');
                        const rowsBaruServer = Array.from(doc.querySelectorAll('tr.riwayat-row[data-riwayat-id]'));
                        const tbodySekarang = document.getElementById('riwayat-tbody');

                        if (rowsBaruServer.length > 0 && tbodySekarang) {
                            // Cari baris yang belum ada di layar saat ini (diurutkan dari yang paling lama di antara yang baru agar urutan prepend tepat)
                            const itemBaru = rowsBaruServer.filter(tr => !knownRiwayatIds.has(String(tr.getAttribute('data-riwayat-id'))));

                            if (itemBaru.length > 0) {
                                const emptyRow = tbodySekarang.querySelector('.empty-state')?.closest('tr');
                                if (emptyRow) emptyRow.remove();

                                itemBaru.reverse().forEach(function (trServer) {
                                    const idBaru = String(trServer.getAttribute('data-riwayat-id'));
                                    knownRiwayatIds.add(idBaru);

                                    const trClone = trServer.cloneNode(true);
                                    trClone.classList.add('belum-dibaca');
                                    tbodySekarang.insertBefore(trClone, tbodySekarang.firstChild);

                                    // Ambil detail untuk popup toast real-time
                                    const tds = trClone.querySelectorAll('td');
                                    const waktuTeks = tds[0] ? tds[0].childNodes[0]?.textContent?.trim() : 'Baru saja';
                                    const namaUser = tds[1]?.querySelector('div:first-child')?.textContent?.trim() || 'Pengguna';
                                    const roleUser = tds[1]?.querySelector('div:last-child')?.textContent?.trim() || 'Admin';
                                    const teksAktivitas = tds[2]?.textContent?.trim() || 'Melakukan aktivitas baru';
                                    const teksModul = tds[3]?.textContent?.trim() || 'Sistem';

                                    tampilkanNotifRealtimeMasterAdmin(namaUser, roleUser, teksAktivitas, teksModul, waktuTeks);

                                    if (typeof window.hitungRiwayatBelumDibaca === 'function') {
                                        window.hitungRiwayatBelumDibaca();
                                    }

                                    pantauBarisBelumDibaca(trClone);
                                });
                            }
                        }
                    } catch (err) {
                        // Abaikan gangguan jaringan sementara
                    } finally {
                        sedangPolling = false;
                    }
                }

                // Cek otomatis setiap 3 detik khusus untuk Master Admin
                setInterval(cekRiwayatTerbaruRealtime, 3000);
            }
        });
    </script>

</body>
</html>