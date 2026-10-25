<!DOCTYPE html>
<html lang="id">
<head>
  	<meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1.0">
  	<title>Dashboard Admin</title>
  	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
  	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />
  	
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
    		}

    		.user-info .user-name {
      			font-size: 14px;
      			font-weight: 600;
    		}

    		.user-info .user-role {
      			font-size: 12px;
      			color: var(--text-muted);
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
    		}

    		.header-title h1 {
      			font-size: 26px;
      			font-weight: 700;
    		}

    		.header-title p {
      			font-size: 14px;
      			color: var(--text-muted);
      			font-weight: 500;
    		}

    		.search-bar {
      			background: #e5e7eb;
      			padding: 8px 15px;
      			border-radius: 20px;
      			display: flex;
      			align-items: center;
      			gap: 10px;
      			width: 250px;
    		}

    		.search-bar input {
      			border: none;
      			background: transparent;
      			outline: none;
      			width: 100%;
      			font-size: 14px;
    		}

    		.stats-grid {
      			display: grid;
      			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      			gap: 20px;
    		}

    		.stat-card {
      			background: #fff;
      			padding: 20px;
      			border-radius: 15px;
      			box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      			border: 1px solid var(--border-color);
      			display: flex;
      			flex-direction: column;
      			gap: 10px;
    		}

    		.stat-card .stat-value {
      			font-size: 24px;
      			font-weight: 700;
    		}

    		.stat-card .stat-desc {
      			font-size: 13px;
      			color: var(--text-muted);
    		}

    		.dashboard-grid-row {
      			display: grid;
      			grid-template-columns: 2fr 1fr;
      			gap: 20px;
    		}

    		@media (max-width: 1024px) {
      			.dashboard-grid-row {
        				grid-template-columns: 1fr;
      			}
    		}

    		.card-box {
      			background: #fff;
      			padding: 20px;
      			border-radius: 20px;
      			box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      			border: 1px solid var(--border-color);
    		}

    		.chart-header {
      			display: flex;
      			justify-content: space-between;
      			align-items: center;
      			margin-bottom: 20px;
      			flex-wrap: wrap;
      			gap: 10px;
    		}

    		.chart-filters {
      			display: flex;
      			gap: 8px;
    		}

    		.filter-btn {
      			background: #e5e7eb;
      			border: none;
      			padding: 6px 14px;
      			border-radius: 8px;
      			font-size: 13px;
      			font-weight: 600;
      			cursor: pointer;
      			color: #71717a;
    		}

    		.filter-btn.active {
      			background: #000;
      			color: #fff;
    		}

    		.chart-bars-container {
      			display: flex;
      			justify-content: space-around;
      			align-items: flex-end;
      			height: 220px;
      			padding-top: 20px;
      			border-bottom: 2px solid #e5e7eb;
    		}

    		.bar-group {
      			display: flex;
      			flex-direction: column;
      			align-items: center;
      			gap: 8px;
      			flex: 1;
    		}

    		.bar-value {
      			font-size: 12px;
      			font-weight: 600;
    		}

    		.bar {
      			width: 36px;
      			background-color: #2f6aa7;
      			border-radius: 8px 8px 0 0;
    		}

    		.chart-legends {
      			display: flex;
      			justify-content: center;
      			gap: 20px;
      			margin-top: 15px;
      			font-size: 13px;
    		}

    		.legend-item {
      			display: flex;
      			align-items: center;
      			gap: 6px;
    		}

    		.legend-dot {
      			width: 12px;
      			height: 12px;
      			border-radius: 50%;
    		}

    		.log-list {
      			display: flex;
      			flex-direction: column;
      			gap: 15px;
      			margin-top: 15px;
    		}

    		.log-item .log-content h4 {
      			font-size: 13px;
      			font-weight: 600;
    		}

    		.log-item .log-content span {
      			font-size: 12px;
      			color: var(--text-muted);
    		}

    		.action-buttons-grid {
      			display: grid;
      			grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      			gap: 15px;
    		}

    		.action-btn {
      			padding: 15px;
      			border-radius: 12px;
      			border: 1px solid var(--border-color);
      			display: flex;
      			align-items: center;
      			justify-content: center;
      			gap: 10px;
      			font-weight: 600;
      			font-size: 14px;
      			cursor: pointer;
      			text-decoration: none;
      			transition: opacity 0.2s;
    		}

    		.action-btn.primary { background: var(--primary-color); color: #fff; border: none; }
    		.action-btn.secondary { background: #fff; color: var(--primary-color); }
    		.action-btn.success { background: var(--secondary-color); color: #fff; border: none; }
    		.action-btn:hover { opacity: 0.9; }

    		.tables-grid-row {
      			display: grid;
      			grid-template-columns: 1fr 1fr;
      			gap: 20px;
    		}

    		@media (max-width: 1024px) {
      			.tables-grid-row {
        				grid-template-columns: 1fr;
      			}
    		}

    		.data-table {
      			width: 100%;
      			border-collapse: collapse;
      			margin-top: 15px;
    		}

    		.data-table th {
      			font-size: 11px;
      			color: var(--text-muted);
      			text-align: left;
      			padding: 8px 10px;
      			border-bottom: 1px solid var(--border-color);
    		}

    		.data-table td {
      			font-size: 13px;
      			padding: 10px;
      			border-bottom: 1px solid rgba(0,0,0,0.05);
    		}

    		@media (max-width: 768px) {
      			sidebar {
        				transform: translateX(-100%);
      			}
      			main {
        				margin-left: 0;
        				width: 100%;
        				padding: 15px;
      			}
    		}
  	</style>
</head>
<body>

  	<!-- Sidebar Navigation -->
  	@include('layouts.adminsidebar')

  	<!-- Main Wrapper -->
  	<main>
        <!-- CSS Tambahan Khusus Dashboard Ini -->
        <style>
            .custom-scroll::-webkit-scrollbar { width: 6px; }
            .custom-scroll::-webkit-scrollbar-track { background: transparent; }
            .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
            @media (max-width: 992px) { .dashboard-panels { grid-template-columns: 1fr !important; } }
        </style>

        <header class="main-header" style="border-bottom: none; padding-bottom: 0;">
            <div class="header-title">
                <h1>Dashboard Admin</h1>
                <p id="live-clock" style="color: #64748b; font-size: 13px; margin-top: 4px;">Memuat waktu...</p>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- BARIS 1: KOTAK ANGKA RINGKASAN (KPI CARDS) -->
        <!-- ========================================== -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; margin-bottom: 25px;">
            
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Total Pengguna</div>
                <div style="font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1;">{{ $totalPengguna }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; font-weight: 500;">M: {{ $jmlMasterAdmin }} | A: {{ $jmlAdmin }} | U: {{ $jmlUser }}</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Total Aset Fisik</div>
                <div style="font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1;">{{ $totalAset }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; font-weight: 500;">Tersebar di {{ $totalCabang }} Cabang</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #ef4444; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Aset Perlu Servis</div>
                <div style="font-size: 26px; font-weight: 800; color: #ef4444; line-height: 1;">{{ $totalMendekatiServis }}</div>
                <div style="font-size: 11px; color: #ef4444; margin-top: 10px; font-weight: 600;">Butuh penanganan segera</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Kategori Aset</div>
                <div style="font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1;">{{ $totalKategori }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; font-weight: 500;">Master Kategori</div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #14b8a6; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Master Nama Aset</div>
                <div style="font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1;">{{ $totalMasterAset }}</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; font-weight: 500;">Master Nama Item</div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- BARIS 2: PANEL RINCIAN DAFTAR -->
        <!-- ========================================== -->
        <div class="dashboard-panels" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">
            
            <!-- PANEL KIRI: Tabel Distribusi Cabang -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden;">
                <div style="padding: 18px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1e293b; font-size: 14px;">
                    Distribusi Aset per Cabang
                </div>
                <div class="custom-scroll" style="max-height: 450px; overflow-y: auto; padding: 0 20px;">
                    <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                        @forelse($asetPerCabang as $cabang)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 14px 0; font-weight: 600; color: #475569;">{{ $cabang->cabang->nama_cabang ?? 'Tanpa Cabang' }}</td>
                            <td style="padding: 14px 0; text-align: right; font-weight: 700; color: #10b981;">{{ $cabang->total }} Aset</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" style="padding: 30px 0; text-align: center; color: #94a3b8;">Belum ada distribusi aset.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>

            <!-- PANEL KANAN: Daftar Aset Rusak -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden;">
                <div style="padding: 18px 20px; background: #fef2f2; border-bottom: 1px solid #fecaca; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-weight: 700; color: #b91c1c; font-size: 14px;">Detail Aset Perlu Servis / Rusak</span>
                    
                    <!-- DROPDOWN PILIH CABANG -->
                    <select id="selectCabangRusak" onchange="filterCabangRusak(this.value)" style="padding: 6px 12px; border: 1px solid #fca5a5; border-radius: 8px; font-size: 12px; font-weight: 600; background: #fff; color: #b91c1c; outline: none; cursor: pointer;">
                        <option value="semua">-- Tampilkan Semua Cabang ({{ $totalMendekatiServis }}) --</option>
                        @foreach($servisDetail as $namaCabang => $daftarAset)
                            <option value="{{ Str::slug($namaCabang) }}">{{ $namaCabang }} ({{ count($daftarAset) }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="custom-scroll" style="max-height: 450px; overflow-y: auto; padding: 25px;">
                    
                    @forelse($servisDetail as $namaCabang => $daftarAset)
                    <!-- Setiap blok cabang diberi ID berbasis slug agar bisa difilter oleh dropdown -->
                    <div class="cabang-rusak-item" data-cabang="{{ Str::slug($namaCabang) }}" style="margin-bottom: 25px;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 12px;">
                            <strong style="color: #0f172a; font-size: 14px;">🏢 {{ $namaCabang }}</strong>
                            <span style="background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">{{ count($daftarAset) }} Aset</span>
                        </div>
                        
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach($daftarAset as $infoAset)
                            <li style="padding: 10px 0; border-bottom: 1px dashed #e2e8f0; font-size: 13px; color: #475569; position: relative; padding-left: 18px;">
                                <span style="position: absolute; left: 0; top: 16px; width: 6px; height: 6px; background: #ef4444; border-radius: 50%;"></span>
                                {{ $infoAset }}
                            </li>
                            @endforeach
                        </ul>

                    </div>
                    @empty
                    <div style="text-align: center; padding: 50px 20px; color: #059669; font-weight: 600; font-size: 14px;">
                        🎉 Luar biasa! Semua aset di seluruh cabang dalam kondisi prima.
                    </div>
                    @endforelse

                    <div id="noDataMessage" style="display: none; text-align: center; padding: 40px; color: #94a3b8; font-size: 13px;">
                        Tidak ada aset bermasalah pada cabang ini.
                    </div>

                </div>
            </div>

        </div>
    </main>
<script>
    function updateClock() {
        const now = new Date();
        
        // Daftar nama hari dan bulan dalam bahasa Indonesia
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        const dayName = days[now.getDay()];
        const date = String(now.getDate()).padStart(2, '0');
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        // Gabungkan formatnya (bisa ditambah detik jika mau: ${seconds})
        const currentTime = `${dayName}, ${date} ${monthName} ${year} ${hours}:${minutes}:${seconds} WIB`;

        // Masukkan ke dalam elemen HTML
        document.getElementById('live-clock').innerText = currentTime;
    }

    // Jalankan fungsi saat halaman pertama kali dimuat
    updateClock();

    // Perbarui setiap 1 detik agar jamnya terus bergerak
    setInterval(updateClock, 1000);
</script>

<script>
    function filterCabangRusak(selectedSlug) {
        let items = document.querySelectorAll('.cabang-rusak-item');
        let visibleCount = 0;

        items.forEach(item => {
            let cabangSlug = item.getAttribute('data-cabang');
            if (selectedSlug === 'semua' || cabangSlug === selectedSlug) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        let noDataEl = document.getElementById('noDataMessage');
        if (visibleCount === 0 && selectedSlug !== 'semua') {
            noDataEl.style.display = 'block';
        } else {
            noDataEl.style.display = 'none';
        }
    }
</script>
</body>
</html>