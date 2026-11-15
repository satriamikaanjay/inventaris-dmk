@php
    $__cekRolePengajuan = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';

    if ($__cekRolePengajuan === 'master_admin' || $__cekRolePengajuan === 'masteradmin') {
        $targetMasterUrl = \Illuminate\Support\Facades\Route::has('masteradmin.dashboard')
            ? route('masteradmin.dashboard')
            : url('/masteradmin');
        header('Location: ' . $targetMasterUrl);
        exit;
    }

    session(['active_sidebar_panel' => 'admin']);

    \App\Http\Controllers\AdminPengajuanController::pastikanTabelTersedia();

    if (!isset($pengajuanList)) {
        try {
            $pengajuanList = \Illuminate\Support\Facades\Schema::hasTable('pengajuan_asets')
                ? \Illuminate\Support\Facades\DB::table('pengajuan_asets')->orderByDesc('id')->get()
                : collect();
        } catch (\Throwable $e) {
            $pengajuanList = collect();
        }
    }

    $semuaItemFormatted = $pengajuanList->map(function ($r) {
        return \App\Http\Controllers\AdminPengajuanController::formatRowUntukWeb($r);
    })->values();

    $rolesStoragePath = storage_path('app/roles_permissions.json');
    $izinPengajuan = [
        'pengajuan'             => true,
        'pengajuan_peminjaman'  => true,
        'pengajuan_permintaan'  => true,
        'pengajuan_peleburan'   => true,
        'pengajuan_perpindahan' => true,
        'pengajuan_servis'      => true,
    ];

    $roleUserSaatIni = 'admin';
    if (\Illuminate\Support\Facades\Auth::check()) {
        $rawRole = strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? 'admin'))));
        if ($rawRole !== '' && $rawRole !== 'master_admin' && $rawRole !== 'masteradmin') {
            $roleUserSaatIni = $rawRole;
        }
    }

    if (is_file($rolesStoragePath)) {
        $decodedPerms = json_decode((string) file_get_contents($rolesStoragePath), true);
        if (is_array($decodedPerms)) {
            $roleTargetData = $decodedPerms['roles'][$roleUserSaatIni] ?? ($decodedPerms['roles']['admin'] ?? null);
            if (is_array($roleTargetData) && isset($roleTargetData['permissions']) && is_array($roleTargetData['permissions'])) {
                foreach ($izinPengajuan as $kPerm => $defVal) {
                    if (isset($roleTargetData['permissions'][$kPerm])) {
                        $izinPengajuan[$kPerm] = !empty($roleTargetData['permissions'][$kPerm]['view']);
                    }
                }
            }
        }
    }

    $daftarJenisValid = ['peminjaman', 'permintaan', 'peleburan', 'perpindahan', 'servis'];
    $adaQueryJenisEksplisit = request()->has('jenis') || request()->has('pengajuan');
    $jenisAktif = strtolower((string) request()->query('jenis', request()->query('pengajuan', 'peminjaman')));
    if (!in_array($jenisAktif, $daftarJenisValid, true)) {
        $jenisAktif = 'peminjaman';
    }

    if (!$adaQueryJenisEksplisit && empty($izinPengajuan['pengajuan_' . $jenisAktif])) {
        foreach ($daftarJenisValid as $jKandidat) {
            if (!empty($izinPengajuan['pengajuan_' . $jKandidat])) {
                $jenisAktif = $jKandidat;
                break;
            }
        }
    }

    $semuaSubTertutup = empty($izinPengajuan['pengajuan_peminjaman'])
        && empty($izinPengajuan['pengajuan_permintaan'])
        && empty($izinPengajuan['pengajuan_peleburan'])
        && empty($izinPengajuan['pengajuan_perpindahan'])
        && empty($izinPengajuan['pengajuan_servis']);
    $menuUtamaPengajuanBoleh = !empty($izinPengajuan['pengajuan']) && !$semuaSubTertutup;
    $tabAktifSaatIniBoleh = $menuUtamaPengajuanBoleh && !empty($izinPengajuan['pengajuan_' . $jenisAktif]);

    $labelNamaPengajuan = [
        'peminjaman'  => 'Pengajuan Peminjaman',
        'permintaan'  => 'Pengajuan Permintaan',
        'peleburan'   => 'Pengajuan Peleburan',
        'perpindahan' => 'Pengajuan Perpindahan',
        'servis'      => 'Pengajuan Servis',
    ];

    $deskripsiPengajuan = [
        'peminjaman'  => 'Daftar ajuan peminjaman aset dari User. Klik Lihat Detail untuk melihat rincian lengkap dan Terima / Tolak ajuan.',
        'permintaan'  => 'Daftar ajuan permintaan pengadaan/alokasi aset dari User. Klik Lihat Detail untuk melihat rincian lengkap dan Terima / Tolak ajuan.',
        'peleburan'   => 'Daftar ajuan peleburan/penghapusan aset rusak berat dari User. Klik Lihat Detail untuk melihat rincian lengkap dan Terima / Tolak ajuan.',
        'perpindahan' => 'Daftar ajuan perpindahan ruangan/cabang aset dari User. Saat diterima (ACC), ruangan & unit aset otomatis tersinkronisasi.',
        'servis'      => 'Daftar ajuan servis/perbaikan aset bermasalah dari User. Klik Lihat Detail untuk melihat rincian lengkap dan Terima / Tolak ajuan.',
    ];

    $pendingPerJenis = [
        'peminjaman'  => $semuaItemFormatted->where('jenis', 'peminjaman')->where('status', 'Menunggu')->count(),
        'permintaan'  => $semuaItemFormatted->where('jenis', 'permintaan')->where('status', 'Menunggu')->count(),
        'peleburan'   => $semuaItemFormatted->where('jenis', 'peleburan')->where('status', 'Menunggu')->count(),
        'perpindahan' => $semuaItemFormatted->where('jenis', 'perpindahan')->where('status', 'Menunggu')->count(),
        'servis'      => $semuaItemFormatted->where('jenis', 'servis')->where('status', 'Menunggu')->count(),
    ];

    $totalSemua = $semuaItemFormatted->count();
    $totalMenunggu = $semuaItemFormatted->where('status', 'Menunggu')->count();
    $totalDisetujui = $semuaItemFormatted->whereIn('status', ['Disetujui', 'Selesai'])->count();
    $totalDitolak = $semuaItemFormatted->where('status', 'Ditolak')->count();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pengajuan Aset - Sistem Manajemen Aset</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --text-color: #0f172a;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.12);
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

        main {
            margin-left: var(--sidebar-width);
            padding: 25px 30px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-top: 4px;
        }

        .live-indicator-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 13px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 12px;
            font-weight: 700;
        }

        .live-indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulseLive 1.4s infinite;
        }

        @keyframes pulseLive {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.45; }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: #ffffff;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 13px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        }

        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .kpi-sub {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }

        .pengajuan-top-buttons {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .btn-tab-pengajuan {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            color: #1e293b;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            text-align: left;
            position: relative;
        }

        .btn-tab-pengajuan:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .btn-tab-pengajuan.has-pending:not(.active) {
            border-color: #ef4444;
            background: #fff5f5;
            color: #991b1b;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.14);
        }

        .btn-tab-pengajuan.active {
            background: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 6px 14px rgba(26, 79, 145, 0.22);
        }

        .tab-badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
            background: #f1f5f9;
            color: #475569;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .btn-tab-pengajuan.active .tab-badge-count {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .tab-badge-count.badge-merah-aktif,
        .btn-tab-pengajuan.active .tab-badge-count.badge-merah-aktif {
            background: #ef4444 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.45);
        }

        .pengajuan-section {
            display: none;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            animation: fadeInSection 0.2s ease;
        }

        .pengajuan-section.active {
            display: block;
        }

        @keyframes fadeInSection {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-head-row {
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .pengajuan-section-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .pengajuan-section-desc {
            font-size: 13px;
            color: #64748b;
        }

        .table-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .search-box {
            flex: 1 1 280px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 12px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 13px;
            color: #0f172a;
        }

        .filter-select {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            cursor: pointer;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .data-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .data-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #1e293b;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .data-table tbody tr.row-menunggu-highlight {
            background: #fffbeb;
        }

        .data-table tbody tr.row-menunggu-highlight:hover {
            background: #fef3c7;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-menunggu {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-disetujui {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .status-selesai {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status-ditolak {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .aksi-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .btn-aksi-sm {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-aksi-sm:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .btn-detail-ajuan {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .btn-terima-ajuan {
            background: #10b981;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        }

        .btn-tolak-ajuan {
            background: #ef4444;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
        }

        .rute-pindah-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            border-radius: 8px;
            margin-top: 4px;
            font-size: 12px;
        }

        .rute-asal {
            color: #475569;
            font-weight: 600;
        }

        .rute-arrow {
            color: var(--primary-color);
            font-weight: 800;
        }

        .rute-tujuan {
            color: #047857;
            font-weight: 800;
        }

        .sub-pengajuan-revoked-box {
            display: none;
            background: #ffffff;
            border: 1px solid #fecaca;
            border-top: 5px solid #ef4444;
            border-radius: 16px;
            padding: 40px 28px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.08);
        }

        .sub-pengajuan-revoked-box.active {
            display: block;
        }

        .alert-toast {
            padding: 12px 16px;
            border-radius: 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-left: 4px solid #10b981;
            color: #065f46;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-detail-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-detail-backdrop.show {
            display: flex;
        }

        .modal-detail-card {
            background: #ffffff;
            width: 100%;
            max-width: 660px;
            max-height: 90vh;
            border-radius: 18px;
            border-top: 5px solid var(--primary-color);
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: zoomInModal 0.18s ease;
        }

        @keyframes zoomInModal {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-detail-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #f8fafc;
        }

        .modal-detail-header h3 {
            font-size: 17px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .btn-close-detail {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-detail-body {
            padding: 20px 22px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .detail-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .detail-field-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 13px;
        }

        .detail-field-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }

        .detail-field-val {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .modal-detail-footer {
            padding: 15px 22px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .rt-notif-banner {
            position: fixed;
            top: 20px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            border-left: 4px solid #ef4444;
            padding: 13px 18px;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.25);
            font-size: 13px;
            font-weight: 600;
            z-index: 99999;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .rt-notif-banner.show {
            display: flex;
        }

        @media (max-width: 968px) {
            .pengajuan-top-buttons {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .detail-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    @include('layouts.adminsidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Verifikasi Pengajuan Aset (User)</h1>
                <p>Semua pengajuan dari aplikasi User otomatis masuk secara <strong>Real-Time (Tanpa Refresh)</strong> untuk diterima (ACC) atau ditolak oleh Admin.</p>
            </div>
            <div class="live-indicator-pill">
                <span class="live-indicator-dot"></span>
                <span>Sinkronisasi Real-Time Aktif</span>
            </div>
        </header>

        <div id="toast-status-pengajuan" class="alert-toast" style="display: none;">
            <span id="teks-toast-pengajuan"></span>
            <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;font-size:16px;font-weight:800;color:#065f46;cursor:pointer;">&times;</button>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card" style="border-left: 4px solid #1a4f91;">
                <span class="kpi-label">Total Ajuan Masuk</span>
                <span class="kpi-value" id="kpi-total-semua">{{ $totalSemua }}</span>
                <span class="kpi-sub">Dari seluruh kategori pengajuan User</span>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #ef4444;">
                <span class="kpi-label">Menunggu Keputusan</span>
                <span class="kpi-value" id="kpi-total-menunggu" style="color: #dc2626;">{{ $totalMenunggu }}</span>
                <span class="kpi-sub">Perlu diterima (ACC) / ditolak Admin</span>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #10b981;">
                <span class="kpi-label">Diterima (ACC)</span>
                <span class="kpi-value" id="kpi-total-acc" style="color: #059669;">{{ $totalDisetujui }}</span>
                <span class="kpi-sub">Ajuan telah disetujui Admin</span>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #64748b;">
                <span class="kpi-label">Ditolak</span>
                <span class="kpi-value" id="kpi-total-ditolak" style="color: #475569;">{{ $totalDitolak }}</span>
                <span class="kpi-sub">Ajuan tidak disetujui Admin</span>
            </div>
        </div>

        <div class="pengajuan-top-buttons"
             id="bar-tombol-pengajuan"
             data-init-hide="{{ !$menuUtamaPengajuanBoleh ? '1' : '0' }}">
            @foreach($daftarJenisValid as $jTab)
                @php
                    $jmlPendingTab = (int) ($pendingPerJenis[$jTab] ?? 0);
                @endphp
                <button type="button"
                        class="btn-tab-pengajuan {{ $jenisAktif === $jTab ? 'active' : '' }} {{ $jmlPendingTab > 0 ? 'has-pending' : '' }}"
                        data-tab-jenis="{{ $jTab }}"
                        data-subperm-key="pengajuan_{{ $jTab }}"
                        data-init-hide="{{ empty($izinPengajuan['pengajuan_' . $jTab]) ? '1' : '0' }}"
                        onclick="bukaTabPengajuan(this.dataset.tabJenis)">
                    <span>{{ $labelNamaPengajuan[$jTab] }}</span>
                    <span class="tab-badge-count {{ $jmlPendingTab > 0 ? 'badge-merah-aktif' : '' }}"
                          id="badge-tab-pengajuan-{{ $jTab }}"
                          title="{{ $jmlPendingTab }} ajuan menunggu verifikasi">{{ $jmlPendingTab }}</span>
                </button>
            @endforeach
        </div>

        <div id="box-akses-pengajuan-dicabut" class="sub-pengajuan-revoked-box {{ !$tabAktifSaatIniBoleh ? 'active' : '' }}">
            <h2 id="judul-akses-pengajuan-dicabut" style="color: #0f172a; font-size: 20px; font-weight: 800; margin-bottom: 8px;">
                Hak Akses {{ $labelNamaPengajuan[$jenisAktif] ?? 'Pengajuan' }} Dicabut
            </h2>
            <p id="pesan-akses-pengajuan-dicabut" style="color: #64748b; font-size: 13.5px; line-height: 1.6; max-width: 520px; margin: 0 auto 22px auto;">
                Master Admin telah menutup / menonaktifkan akses pada menu <strong>{{ $labelNamaPengajuan[$jenisAktif] ?? 'Pengajuan' }}</strong> untuk role Anda secara real-time. Silakan pilih kategori pengajuan lain yang masih tersedia atau kembali ke Dashboard.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="table-toolbar" id="toolbar-filter-pengajuan">
            <div class="search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="input-cari-pengajuan" placeholder="Cari no. ajuan, nama user, aset, ruangan asal/tujuan, cabang..." oninput="renderSemuaTabelPengajuan()">
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select id="filter-status-pengajuan" class="filter-select" onchange="renderSemuaTabelPengajuan()">
                    <option value="Semua">Semua Status</option>
                    <option value="Menunggu">Menunggu Keputusan</option>
                    <option value="Disetujui">Diterima (ACC)</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Ditolak">Ditolak</option>
                </select>
            </div>
        </div>

        @foreach($daftarJenisValid as $jKey)
            <section id="section-pengajuan-{{ $jKey }}"
                     class="pengajuan-section {{ ($jenisAktif === $jKey && $tabAktifSaatIniBoleh) ? 'active' : '' }}"
                     data-section-jenis="{{ $jKey }}">
                <div class="section-head-row">
                    <h2 class="pengajuan-section-title">{{ $labelNamaPengajuan[$jKey] }}</h2>
                    <p class="pengajuan-section-desc">{{ $deskripsiPengajuan[$jKey] }}</p>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NO. AJUAN</th>
                                <th>TANGGAL</th>
                                <th>USER PEMOHON</th>
                                <th>{{ $jKey === 'perpindahan' ? 'PINDAH DARI MANA KE MANA' : 'ASET & LOKASI / TUJUAN' }}</th>
                                <th>STATUS</th>
                                <th style="text-align: center;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-pengajuan-{{ $jKey }}">
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </main>

    <div class="modal-detail-backdrop" id="modalDetailPengajuan" onclick="if(event.target === this) tutupModalDetailPengajuan()">
        <div class="modal-detail-card">
            <div class="modal-detail-header">
                <div>
                    <h3 id="mdl-judul-pengajuan">Detail Pengajuan Aset</h3>
                    <div id="mdl-subjudul-nomor" style="font-size: 12px; font-weight: 700; color: #64748b; margin-top: 2px;"></div>
                </div>
                <button type="button" class="btn-close-detail" onclick="tutupModalDetailPengajuan()">&times;</button>
            </div>
            <div class="modal-detail-body" id="mdl-body-pengajuan">
            </div>
            <div class="modal-detail-footer" id="mdl-footer-pengajuan">
            </div>
        </div>
    </div>

    <div class="rt-notif-banner" id="banner-pengajuan-baru">
        <span style="background:#ef4444;color:#fff;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;">!</span>
        <span id="teks-banner-pengajuan-baru">Pengajuan baru masuk dari User!</span>
    </div>

    <input type="hidden" id="json-jenis-aktif-pengajuan" value="{{ json_encode($jenisAktif) }}">
    <input type="hidden" id="json-data-awal-pengajuan" value="{{ json_encode($semuaItemFormatted) }}">
    <input type="hidden" id="json-url-poll-pengajuan" value="{{ json_encode(route('admin.pengajuan.index')) }}">

    <script>
        const NAMA_KATEGORI_PENGAJUAN = {
            peminjaman: 'Pengajuan Peminjaman',
            permintaan: 'Pengajuan Permintaan',
            peleburan: 'Pengajuan Peleburan',
            perpindahan: 'Pengajuan Perpindahan',
            servis: 'Pengajuan Servis'
        };

        const CSRF_TOKEN_PENGAJUAN = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
        const URL_POLL_PENGAJUAN = JSON.parse(document.getElementById('json-url-poll-pengajuan').value || '""');

        document.querySelectorAll('[data-init-hide="1"]').forEach(function (el) { el.style.display = 'none'; });
        window.jenisPengajuanAktif = JSON.parse(document.getElementById('json-jenis-aktif-pengajuan').value || '"peminjaman"');

        let daftarPengajuanGlobal = [];
        try {
            daftarPengajuanGlobal = JSON.parse(document.getElementById('json-data-awal-pengajuan').value || '[]') || [];
        } catch (e) {
            daftarPengajuanGlobal = [];
        }

        let idPengajuanSedangDibuka = null;

        function escHtml(str) {
            return String(str === null || str === undefined ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/\x3c/g, '&lt;')
                .replace(/\x3e/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function buatBadgeStatusHtml(statusRaw) {
            const st = String(statusRaw || 'Menunggu');
            const lower = st.toLowerCase();
            let cls = 'status-menunggu';
            let label = 'Menunggu';
            if (lower === 'disetujui') {
                cls = 'status-disetujui';
                label = 'Diterima (ACC)';
            } else if (lower === 'selesai') {
                cls = 'status-selesai';
                label = 'Selesai';
            } else if (lower === 'ditolak') {
                cls = 'status-ditolak';
                label = 'Ditolak';
            }
            return '\x3cspan class="status-badge ' + cls + '"\x3e' + escHtml(label) + '\x3c/span\x3e';
        }

        function buatRingkasanPindahAtauLokasiHtml(item) {
            const namaAset = item.nama_aset && item.nama_aset !== '-' ? item.nama_aset : 'Aset Inventaris';
            const kodeAset = item.kode_aset && item.kode_aset !== '-' ? ' (' + item.kode_aset + ')' : '';
            const rAsal = (item.ruangan_asal && item.ruangan_asal !== '-') ? item.ruangan_asal : 'Tanpa Ruangan';
            const cAsal = (item.cabang_asal && item.cabang_asal !== '-') ? item.cabang_asal : '-';
            const rTujuan = (item.ruangan_tujuan && item.ruangan_tujuan !== '-') ? item.ruangan_tujuan : 'Tanpa Ruangan';
            const cTujuan = (item.cabang_tujuan && item.cabang_tujuan !== '-') ? item.cabang_tujuan : cAsal;

            if (item.jenis === 'perpindahan') {
                return (
                    '\x3cdiv style="font-weight: 700; color: #0f172a;"\x3e' + escHtml(namaAset) + '\x3cspan style="color: var(--primary-color); font-size: 12px;"\x3e' + escHtml(kodeAset) + '\x3c/span\x3e\x3c/div\x3e' +
                    '\x3cdiv class="rute-pindah-box"\x3e' +
                        '\x3cspan class="rute-asal"\x3eDari: ' + escHtml(rAsal) + ' (' + escHtml(cAsal) + ')\x3c/span\x3e' +
                        '\x3cspan class="rute-arrow"\x3e➔\x3c/span\x3e' +
                        '\x3cspan class="rute-tujuan"\x3eKe: ' + escHtml(rTujuan) + ' (' + escHtml(cTujuan) + ')\x3c/span\x3e' +
                    '\x3c/div\x3e'
                );
            }

            if (item.jenis === 'peminjaman' || item.jenis === 'permintaan') {
                const ruangPakai = (item.ruangan_tujuan && item.ruangan_tujuan !== '-') ? item.ruangan_tujuan : rAsal;
                const cabangPakai = (item.cabang_tujuan && item.cabang_tujuan !== '-') ? item.cabang_tujuan : cAsal;
                return (
                    '\x3cdiv style="font-weight: 700; color: #0f172a;"\x3e' + escHtml(namaAset) + '\x3cspan style="color: var(--primary-color); font-size: 12px;"\x3e' + escHtml(kodeAset) + '\x3c/span\x3e\x3c/div\x3e' +
                    '\x3cdiv style="font-size: 12px; color: #475569; margin-top: 3px;"\x3eLokasi/Tujuan: \x3cstrong\x3e' + escHtml(ruangPakai) + ' (' + escHtml(cabangPakai) + ')\x3c/strong\x3e • ' + escHtml(item.unit_jumlah || 1) + ' Unit\x3c/div\x3e'
                );
            }

            return (
                '\x3cdiv style="font-weight: 700; color: #0f172a;"\x3e' + escHtml(namaAset) + '\x3cspan style="color: var(--primary-color); font-size: 12px;"\x3e' + escHtml(kodeAset) + '\x3c/span\x3e\x3c/div\x3e' +
                '\x3cdiv style="font-size: 12px; color: #475569; margin-top: 3px;"\x3eLokasi: \x3cstrong\x3e' + escHtml(rAsal) + ' (' + escHtml(cAsal) + ')\x3c/strong\x3e\x3c/div\x3e'
            );
        }

        function buatBarisTabelHtml(item) {
            const isMenunggu = String(item.status || '').toLowerCase() === 'menunggu';
            const rowClass = isMenunggu ? 'baris-data-pengajuan row-menunggu-highlight' : 'baris-data-pengajuan';
            const divisiHtml = (item.divisi && item.divisi !== '-')
                ? '\x3cdiv style="font-size: 11.5px; color: #64748b;"\x3e' + escHtml(item.divisi) + '\x3c/div\x3e'
                : '';

            let tombolCepatHtml = '';
            if (isMenunggu) {
                tombolCepatHtml =
                    '\x3cbutton type="button" class="btn-aksi-sm btn-terima-ajuan" data-id="' + escHtml(item.id) + '" onclick="eksekusiVerifikasiPengajuan(this.dataset.id, \'Disetujui\', this)"\x3e✓ Terima\x3c/button\x3e' +
                    '\x3cbutton type="button" class="btn-aksi-sm btn-tolak-ajuan" data-id="' + escHtml(item.id) + '" onclick="eksekusiVerifikasiPengajuan(this.dataset.id, \'Ditolak\', this)"\x3e✕ Tolak\x3c/button\x3e';
            }

            return (
                '\x3ctr class="' + rowClass + '" data-id="' + escHtml(item.id) + '" data-status="' + escHtml(item.status) + '"\x3e' +
                    '\x3ctd style="font-weight: 800; color: var(--primary-color); white-space: nowrap;"\x3e' + escHtml(item.nomor_pengajuan) + '\x3c/td\x3e' +
                    '\x3ctd style="white-space: nowrap;"\x3e' + escHtml(item.tanggal_formatted || '-') + '\x3c/td\x3e' +
                    '\x3ctd\x3e' +
                        '\x3cdiv style="font-weight: 700; color: #0f172a;"\x3e' + escHtml(item.pemohon || '-') + '\x3c/div\x3e' +
                        divisiHtml +
                    '\x3c/td\x3e' +
                    '\x3ctd\x3e' + buatRingkasanPindahAtauLokasiHtml(item) + '\x3c/td\x3e' +
                    '\x3ctd\x3e' + buatBadgeStatusHtml(item.status) + '\x3c/td\x3e' +
                    '\x3ctd style="text-align: center;"\x3e' +
                        '\x3cdiv class="aksi-group"\x3e' +
                            '\x3cbutton type="button" class="btn-aksi-sm btn-detail-ajuan" data-id="' + escHtml(item.id) + '" onclick="bukaModalDetailPengajuan(this.dataset.id)"\x3eLihat Detail\x3c/button\x3e' +
                            tombolCepatHtml +
                        '\x3c/div\x3e' +
                    '\x3c/td\x3e' +
                '\x3c/tr\x3e'
            );
        }

        function perbaruiKpiDanBadgeMerah() {
            const totalSemua = daftarPengajuanGlobal.length;
            let totalMenunggu = 0;
            let totalAcc = 0;
            let totalDitolak = 0;
            const pendingPerJenis = {
                peminjaman: 0,
                permintaan: 0,
                peleburan: 0,
                perpindahan: 0,
                servis: 0
            };

            daftarPengajuanGlobal.forEach(function (it) {
                const st = String(it.status || '').toLowerCase();
                const j = String(it.jenis || 'peminjaman').toLowerCase();
                if (st === 'menunggu') {
                    totalMenunggu++;
                    if (pendingPerJenis[j] !== undefined) {
                        pendingPerJenis[j]++;
                    }
                } else if (st === 'disetujui' || st === 'selesai') {
                    totalAcc++;
                } else if (st === 'ditolak') {
                    totalDitolak++;
                }
            });

            const elSemua = document.getElementById('kpi-total-semua');
            const elMenunggu = document.getElementById('kpi-total-menunggu');
            const elAcc = document.getElementById('kpi-total-acc');
            const elDitolak = document.getElementById('kpi-total-ditolak');

            if (elSemua) elSemua.textContent = String(totalSemua);
            if (elMenunggu) elMenunggu.textContent = String(totalMenunggu);
            if (elAcc) elAcc.textContent = String(totalAcc);
            if (elDitolak) elDitolak.textContent = String(totalDitolak);

            ['peminjaman', 'permintaan', 'peleburan', 'perpindahan', 'servis'].forEach(function (j) {
                const cntPending = Number(pendingPerJenis[j] || 0);
                const badgeTab = document.getElementById('badge-tab-pengajuan-' + j);
                const btnTab = document.querySelector('.btn-tab-pengajuan[data-tab-jenis="' + j + '"]');
                if (badgeTab) {
                    badgeTab.textContent = String(cntPending);
                    badgeTab.classList.toggle('badge-merah-aktif', cntPending > 0);
                }
                if (btnTab) {
                    btnTab.classList.toggle('has-pending', cntPending > 0);
                }
            });

            if (typeof window.perbaruiBadgePengajuanSidebar === 'function') {
                window.perbaruiBadgePengajuanSidebar({
                    total_pending: totalMenunggu,
                    per_jenis: pendingPerJenis
                });
            }
        }

        window.renderSemuaTabelPengajuan = function () {
            const keyword = (document.getElementById('input-cari-pengajuan')?.value || '').toLowerCase().trim();
            const statusFilter = document.getElementById('filter-status-pengajuan')?.value || 'Semua';

            ['peminjaman', 'permintaan', 'peleburan', 'perpindahan', 'servis'].forEach(function (jKey) {
                const tbody = document.getElementById('tbody-pengajuan-' + jKey);
                if (!tbody) return;

                const rows = daftarPengajuanGlobal.filter(function (item) {
                    if (String(item.jenis || '').toLowerCase() !== jKey) return false;
                    if (statusFilter !== 'Semua' && String(item.status || '').toLowerCase() !== statusFilter.toLowerCase()) {
                        return false;
                    }
                    if (keyword) {
                        const haystack = [
                            item.nomor_pengajuan,
                            item.tanggal_formatted,
                            item.pemohon,
                            item.divisi,
                            item.nama_aset,
                            item.kode_aset,
                            item.kategori_nama,
                            item.cabang_asal,
                            item.ruangan_asal,
                            item.cabang_tujuan,
                            item.ruangan_tujuan,
                            item.keterangan,
                            item.status
                        ].join(' ').toLowerCase();
                        if (haystack.indexOf(keyword) === -1) return false;
                    }
                    return true;
                });

                if (rows.length === 0) {
                    tbody.innerHTML =
                        '\x3ctr class="baris-kosong-pengajuan"\x3e' +
                            '\x3ctd colspan="6" style="text-align: center; padding: 38px 20px; color: #64748b; font-weight: 600;"\x3e' +
                                'Belum ada data ' + escHtml(NAMA_KATEGORI_PENGAJUAN[jKey] || 'Pengajuan') + ' yang sesuai.' +
                            '\x3c/td\x3e' +
                        '\x3c/tr\x3e';
                } else {
                    tbody.innerHTML = rows.map(buatBarisTabelHtml).join('');
                }
            });

            perbaruiKpiDanBadgeMerah();
        };

        window.bukaModalDetailPengajuan = function (idCari) {
            const item = daftarPengajuanGlobal.find(function (x) { return String(x.id) === String(idCari); });
            if (!item) return;

            idPengajuanSedangDibuka = item.id;

            const modal = document.getElementById('modalDetailPengajuan');
            const judulEl = document.getElementById('mdl-judul-pengajuan');
            const subEl = document.getElementById('mdl-subjudul-nomor');
            const bodyEl = document.getElementById('mdl-body-pengajuan');
            const footerEl = document.getElementById('mdl-footer-pengajuan');

            if (judulEl) judulEl.textContent = 'Detail ' + (NAMA_KATEGORI_PENGAJUAN[item.jenis] || 'Pengajuan Aset');
            if (subEl) subEl.textContent = 'Nomor Ajuan: ' + (item.nomor_pengajuan || '-') + ' • Tanggal: ' + (item.tanggal_formatted || '-');

            const rAsal = (item.ruangan_asal && item.ruangan_asal !== '-') ? item.ruangan_asal : 'Tanpa Ruangan (-)';
            const cAsal = (item.cabang_asal && item.cabang_asal !== '-') ? item.cabang_asal : '-';
            const rTujuan = (item.ruangan_tujuan && item.ruangan_tujuan !== '-') ? item.ruangan_tujuan : 'Tanpa Ruangan (-)';
            const cTujuan = (item.cabang_tujuan && item.cabang_tujuan !== '-') ? item.cabang_tujuan : cAsal;

            let lampiranHtml = '\x3cspan style="color:#64748b;font-size:12.5px;"\x3eTidak ada file lampiran\x3c/span\x3e';
            if (item.lampiran && item.lampiran_url) {
                const ext = String(item.lampiran).split('.').pop().toLowerCase();
                const isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(ext) !== -1;
                const previewImg = isImg
                    ? '\x3cdiv style="margin-bottom:8px;"\x3e\x3cimg src="' + escHtml(item.lampiran_url) + '" alt="Lampiran" style="max-height:160px;max-width:100%;border-radius:8px;border:1px solid #cbd5e1;object-fit:contain;"\x3e\x3c/div\x3e'
                    : '';
                lampiranHtml =
                    previewImg +
                    '\x3cdiv style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;"\x3e' +
                        '\x3cspan style="font-size:12px;font-weight:700;color:#1e293b;"\x3e📎 ' + escHtml(item.lampiran_nama || item.lampiran) + '\x3c/span\x3e' +
                        '\x3ca href="' + escHtml(item.lampiran_url) + '" target="_blank" class="btn-aksi-sm btn-detail-ajuan" style="text-decoration:none;padding:5px 10px;"\x3eLihat File\x3c/a\x3e' +
                        '\x3ca href="' + escHtml(item.lampiran_download_url) + '" class="btn-aksi-sm" style="background:#e2e8f0;color:#1e293b;text-decoration:none;padding:5px 10px;"\x3eUnduh\x3c/a\x3e' +
                    '\x3c/div\x3e';
            }

            let infoTambahanHtml = '';
            if (item.jenis === 'peminjaman') {
                infoTambahanHtml =
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eEstimasi Pengembalian\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.tanggal_selesai_fmt || '-') + '\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eJumlah Dipinjam\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.unit_jumlah || 1) + ' Unit\x3c/div\x3e\x3c/div\x3e';
            } else if (item.jenis === 'permintaan') {
                infoTambahanHtml =
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3ePrioritas Permintaan\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.prioritas || 'Normal') + '\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eJumlah Diminta\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.unit_jumlah || 1) + ' Unit\x3c/div\x3e\x3c/div\x3e';
            } else if (item.jenis === 'peleburan') {
                infoTambahanHtml =
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eMetode & Estimasi Nilai\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.metode_peleburan || '-') + ' (' + escHtml(item.nilai_estimasi_fmt) + ')\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eNo. Berita Acara\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.no_berita_acara || '-') + '\x3c/div\x3e\x3c/div\x3e';
            } else if (item.jenis === 'servis') {
                infoTambahanHtml =
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eTeknisi & Estimasi Biaya\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.teknisi || '-') + ' (' + escHtml(item.nilai_estimasi_fmt) + ')\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eTarget Selesai / Servis Berikutnya\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.tanggal_selesai_fmt || '-') + '\x3c/div\x3e\x3c/div\x3e';
            }

            bodyEl.innerHTML =
                '\x3cdiv class="detail-grid-2"\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eUser Pemohon / Peminjam\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.pemohon || '-') + (item.divisi && item.divisi !== '-' ? ' (' + escHtml(item.divisi) + ')' : '') + '\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eStatus Saat Ini\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + buatBadgeStatusHtml(item.status) + '\x3c/div\x3e\x3c/div\x3e' +
                '\x3c/div\x3e' +
                '\x3cdiv class="detail-grid-2"\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eNama Aset & Kategori\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(item.nama_aset || '-') + ' • ' + escHtml(item.kategori_nama || '-') + '\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eKode Aset\x3c/div\x3e\x3cdiv class="detail-field-val" style="color:var(--primary-color);"\x3e' + escHtml(item.kode_aset || '-') + '\x3c/div\x3e\x3c/div\x3e' +
                '\x3c/div\x3e' +
                '\x3cdiv class="detail-grid-2"\x3e' +
                    '\x3cdiv class="detail-field-box" style="border-left: 3px solid #64748b;"\x3e\x3cdiv class="detail-field-label"\x3eDari (Ruangan & Cabang Asal)\x3c/div\x3e\x3cdiv class="detail-field-val"\x3e' + escHtml(rAsal) + ' — Cabang ' + escHtml(cAsal) + '\x3c/div\x3e\x3c/div\x3e' +
                    '\x3cdiv class="detail-field-box" style="border-left: 3px solid #10b981;"\x3e\x3cdiv class="detail-field-label"\x3eKe (Ruangan & Cabang Tujuan)\x3c/div\x3e\x3cdiv class="detail-field-val" style="color:#047857;"\x3e' + escHtml(rTujuan) + ' — Cabang ' + escHtml(cTujuan) + '\x3c/div\x3e\x3c/div\x3e' +
                '\x3c/div\x3e' +
                (infoTambahanHtml ? '\x3cdiv class="detail-grid-2"\x3e' + infoTambahanHtml + '\x3c/div\x3e' : '') +
                '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eDeskripsi / Alasan Pengajuan User\x3c/div\x3e\x3cdiv class="detail-field-val" style="font-weight:500;line-height:1.5;"\x3e' + escHtml(item.keterangan || '-') + '\x3c/div\x3e\x3c/div\x3e' +
                '\x3cdiv class="detail-field-box"\x3e\x3cdiv class="detail-field-label"\x3eLampiran Bukti / File\x3c/div\x3e\x3cdiv class="detail-field-val" style="margin-top:4px;"\x3e' + lampiranHtml + '\x3c/div\x3e\x3c/div\x3e' +
                '\x3cdiv class="detail-field-box"\x3e' +
                    '\x3cdiv class="detail-field-label"\x3eCatatan Admin (Opsional)\x3c/div\x3e' +
                    '\x3cinput type="text" id="input-catatan-admin-modal" value="' + escHtml(item.catatan_admin || '') + '" placeholder="Tambahkan catatan persetujuan atau alasan penolakan..." style="width:100%;margin-top:5px;padding:9px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;font-family:inherit;"\x3e' +
                '\x3c/div\x3e';

            const stLower = String(item.status || 'Menunggu').toLowerCase();
            let aksiModalHtml = '';
            if (stLower === 'menunggu') {
                aksiModalHtml =
                    '\x3cdiv style="display:flex;gap:8px;flex-wrap:wrap;"\x3e' +
                        '\x3cbutton type="button" class="btn btn-tolak-ajuan" data-id="' + escHtml(item.id) + '" onclick="eksekusiVerifikasiPengajuan(this.dataset.id, \'Ditolak\', this)"\x3e✕ Tolak Pengajuan\x3c/button\x3e' +
                        '\x3cbutton type="button" class="btn btn-terima-ajuan" data-id="' + escHtml(item.id) + '" onclick="eksekusiVerifikasiPengajuan(this.dataset.id, \'Disetujui\', this)"\x3e✓ Terima (ACC) Pengajuan\x3c/button\x3e' +
                    '\x3c/div\x3e';
            } else if (stLower === 'disetujui' && (item.jenis === 'peminjaman' || item.jenis === 'servis')) {
                aksiModalHtml =
                    '\x3cdiv style="display:flex;gap:8px;flex-wrap:wrap;"\x3e' +
                        '\x3cbutton type="button" class="btn" style="background:#2563eb;color:#fff;" data-id="' + escHtml(item.id) + '" onclick="eksekusiVerifikasiPengajuan(this.dataset.id, \'Selesai\', this)"\x3e✓ Tandai Selesai\x3c/button\x3e' +
                    '\x3c/div\x3e';
            } else {
                aksiModalHtml = '\x3cspan style="font-size:12.5px;color:#64748b;font-weight:700;"\x3ePengajuan ini telah diverifikasi (' + escHtml(item.status) + ').\x3c/span\x3e';
            }

            footerEl.innerHTML =
                '\x3cbutton type="button" class="btn" style="background:#e2e8f0;color:#1e293b;" onclick="tutupModalDetailPengajuan()"\x3eTutup\x3c/button\x3e' +
                aksiModalHtml;

            modal.classList.add('show');
        };

        window.tutupModalDetailPengajuan = function () {
            const modal = document.getElementById('modalDetailPengajuan');
            if (modal) modal.classList.remove('show');
            idPengajuanSedangDibuka = null;
        };

        window.eksekusiVerifikasiPengajuan = async function (idAjuan, statusTarget, btnEl) {
            const item = daftarPengajuanGlobal.find(function (x) { return String(x.id) === String(idAjuan); });
            if (!item) return;

            const catatanInput = document.getElementById('input-catatan-admin-modal');
            const catatanVal = catatanInput ? catatanInput.value.trim() : (item.catatan_admin || '');

            const teksAwal = btnEl ? btnEl.textContent : '';
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.textContent = 'Memproses...';
            }

            try {
                const res = await fetch(item.status_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN_PENGAJUAN
                    },
                    body: JSON.stringify({
                        status: statusTarget,
                        catatan_admin: catatanVal
                    })
                });

                const data = await res.json();
                if (data && data.success) {
                    if (data.item) {
                        const idx = daftarPengajuanGlobal.findIndex(function (x) { return String(x.id) === String(idAjuan); });
                        if (idx !== -1) {
                            daftarPengajuanGlobal[idx] = data.item;
                        }
                    } else {
                        item.status = statusTarget;
                        item.catatan_admin = catatanVal;
                    }

                    window.renderSemuaTabelPengajuan();
                    window.tutupModalDetailPengajuan();

                    const toastEl = document.getElementById('toast-status-pengajuan');
                    const teksToast = document.getElementById('teks-toast-pengajuan');
                    if (toastEl && teksToast) {
                        teksToast.textContent = data.message || ('Status pengajuan berhasil diperbarui menjadi ' + statusTarget);
                        toastEl.style.display = 'flex';
                        setTimeout(function () { toastEl.style.display = 'none'; }, 4000);
                    }
                } else {
                    alert((data && data.message) ? data.message : 'Gagal memperbarui status pengajuan.');
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.textContent = teksAwal;
                    }
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan saat memverifikasi pengajuan.');
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.textContent = teksAwal;
                }
            }
        };

        let sedangPollingPengajuan = false;
        async function pollDataPengajuanRealtime() {
            if (sedangPollingPengajuan || document.hidden || !URL_POLL_PENGAJUAN) return;
            sedangPollingPengajuan = true;

            try {
                const urlPoll = new URL(URL_POLL_PENGAJUAN, window.location.origin);
                urlPoll.searchParams.set('_poll_pengajuan_rt', Date.now());

                const res = await fetch(urlPoll.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data && data.success && Array.isArray(data.items)) {
                        const setIdLama = new Set(daftarPengajuanGlobal.map(function (x) { return String(x.id); }));
                        const itemBaruMasuk = data.items.filter(function (x) { return !setIdLama.has(String(x.id)); });

                        const jsonLama = JSON.stringify(daftarPengajuanGlobal);
                        const jsonBaru = JSON.stringify(data.items);

                        if (jsonLama !== jsonBaru) {
                            daftarPengajuanGlobal = data.items;
                            window.renderSemuaTabelPengajuan();

                            if (itemBaruMasuk.length > 0) {
                                const baru = itemBaruMasuk[0];
                                const banner = document.getElementById('banner-pengajuan-baru');
                                const teksBanner = document.getElementById('teks-banner-pengajuan-baru');
                                if (banner && teksBanner) {
                                    teksBanner.textContent = 'Pengajuan baru masuk: ' + (baru.nomor_pengajuan || '') + ' (' + (baru.pemohon || 'User') + ')';
                                    banner.classList.add('show');
                                    setTimeout(function () { banner.classList.remove('show'); }, 4500);
                                }
                            }
                        }
                    }
                }
            } catch (e) {
            } finally {
                sedangPollingPengajuan = false;
            }
        }

        window.sinkronkanStatusAksesPengajuan = function (perms) {
            const jenis = window.jenisPengajuanAktif || 'peminjaman';
            const namaLabel = NAMA_KATEGORI_PENGAJUAN[jenis] || 'Pengajuan';

            const subKeys = ['pengajuan_peminjaman', 'pengajuan_permintaan', 'pengajuan_peleburan', 'pengajuan_perpindahan', 'pengajuan_servis'];
            const adaSubBoleh = subKeys.some(function (sk) { return perms && perms[sk] ? !!perms[sk].view : true; });
            const menuUtamaBoleh = (perms && perms.pengajuan ? !!perms.pengajuan.view : true) && adaSubBoleh;
            const subIniBoleh = menuUtamaBoleh && (perms && perms['pengajuan_' + jenis] ? !!perms['pengajuan_' + jenis].view : true);

            const barTombol = document.getElementById('bar-tombol-pengajuan');
            if (barTombol) {
                barTombol.style.display = menuUtamaBoleh ? 'grid' : 'none';
            }

            const toolbarEl = document.getElementById('toolbar-filter-pengajuan');
            if (toolbarEl) {
                toolbarEl.style.display = subIniBoleh ? 'flex' : 'none';
            }

            const boxDicabut = document.getElementById('box-akses-pengajuan-dicabut');
            const judulDicabut = document.getElementById('judul-akses-pengajuan-dicabut');
            const pesanDicabut = document.getElementById('pesan-akses-pengajuan-dicabut');

            if (!subIniBoleh) {
                document.querySelectorAll('.pengajuan-section').forEach(function (sec) { sec.classList.remove('active'); });
                if (boxDicabut) boxDicabut.classList.add('active');
                if (judulDicabut) {
                    judulDicabut.textContent = menuUtamaBoleh
                        ? 'Hak Akses ' + namaLabel + ' Dicabut'
                        : 'Hak Akses Menu Pengajuan Dicabut';
                }
                if (pesanDicabut) {
                    pesanDicabut.textContent = menuUtamaBoleh
                        ? 'Master Admin telah menutup / menonaktifkan akses pada menu ' + namaLabel + ' untuk role Anda secara real-time. Silakan pilih kategori pengajuan lain yang masih tersedia atau kembali ke Dashboard.'
                        : 'Master Admin telah menutup seluruh akses pada menu Pengajuan Aset untuk role Anda secara real-time.';
                }
            } else {
                if (boxDicabut) boxDicabut.classList.remove('active');
                document.querySelectorAll('.pengajuan-section').forEach(function (sec) {
                    sec.classList.toggle('active', sec.getAttribute('data-section-jenis') === jenis);
                });
            }
        };

        window.bukaTabPengajuan = function (jenis, updateUrl = true) {
            const validJenis = ['peminjaman', 'permintaan', 'peleburan', 'perpindahan', 'servis'];
            if (validJenis.indexOf(jenis) === -1) {
                jenis = 'peminjaman';
            }

            window.jenisPengajuanAktif = jenis;

            document.querySelectorAll('.btn-tab-pengajuan').forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-tab-jenis') === jenis);
            });

            document.querySelectorAll('.submenu-pengajuan-btn').forEach(function (subBtn) {
                subBtn.classList.toggle('active', subBtn.getAttribute('data-jenis') === jenis);
            });

            const wrapSidebar = document.getElementById('wrap-menu-pengajuan');
            if (wrapSidebar) {
                wrapSidebar.classList.add('open');
            }

            const permsSaatIni = window.__adminCurrentPerms || null;
            if (permsSaatIni) {
                window.sinkronkanStatusAksesPengajuan(permsSaatIni);
            } else {
                document.querySelectorAll('.pengajuan-section').forEach(function (sec) {
                    sec.classList.toggle('active', sec.getAttribute('data-section-jenis') === jenis);
                });
            }

            if (updateUrl && window.history && window.history.replaceState) {
                const urlBaru = new URL(window.location.href);
                urlBaru.searchParams.delete('pengajuan');
                urlBaru.searchParams.set('jenis', jenis);
                window.history.replaceState({ jenis: jenis }, '', urlBaru.toString());
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.renderSemuaTabelPengajuan();
            const params = new URLSearchParams(window.location.search);
            const jenisAwal = params.get('jenis') || params.get('pengajuan') || window.jenisPengajuanAktif;
            window.bukaTabPengajuan(jenisAwal, false);
            setInterval(pollDataPengajuanRealtime, 2000);
        });
    </script>
</body>

</html>
