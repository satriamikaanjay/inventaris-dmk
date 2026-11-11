@php

    $__userLoginRiwayat = \Illuminate\Support\Facades\Auth::user();
    $__userIdRiwayat = \Illuminate\Support\Facades\Auth::id();
    $__roleUserRiwayat = $__userLoginRiwayat
        ? strtolower(str_replace(' ', '_', trim((string) ($__userLoginRiwayat->role ?? ''))))
        : '';

    $__isMasterAdminRiwayat = ($__roleUserRiwayat === 'master_admin' || $__roleUserRiwayat === 'masteradmin');

    if ($__isMasterAdminRiwayat) {
        session(['active_sidebar_panel' => 'masteradmin']);
    } else {
        session(['active_sidebar_panel' => 'admin']);
    }

    $__buildRiwayatQuery = function () use ($__isMasterAdminRiwayat, $__userIdRiwayat) {
        $q = \App\Models\RiwayatAktivitas::with('user');

        if (!$__isMasterAdminRiwayat) {
            $q->whereNotNull('user_id')->where('user_id', $__userIdRiwayat);
        }

        $searchVal = trim((string) request('search', ''));
        if ($searchVal !== '') {
            $q->where(function ($sub) use ($searchVal, $__isMasterAdminRiwayat) {
                $sub->where('aktivitas', 'like', '%' . $searchVal . '%')
                    ->orWhere('modul', 'like', '%' . $searchVal . '%')
                    ->orWhere('status', 'like', '%' . $searchVal . '%');
                if ($__isMasterAdminRiwayat) {
                    $sub->orWhereHas('user', function ($uSub) use ($searchVal) {
                        $uSub->where('nama_lengkap', 'like', '%' . $searchVal . '%')
                             ->orWhere('email', 'like', '%' . $searchVal . '%')
                             ->orWhere('role', 'like', '%' . $searchVal . '%');
                    });
                }
            });
        }

        $jenisVal = trim((string) request('jenis', ''));
        if ($jenisVal === 'Tambah') {
            $q->where(function ($sub) {
                $sub->where('aktivitas', 'like', '%tambah%')
                    ->orWhere('aktivitas', 'like', '%buat%')
                    ->orWhere('aktivitas', 'like', '%generate%')
                    ->orWhere('aktivitas', 'like', '%import%');
            });
        } elseif ($jenisVal === 'Ubah') {
            $q->where(function ($sub) {
                $sub->where('aktivitas', 'like', '%ubah%')
                    ->orWhere('aktivitas', 'like', '%perbarui%')
                    ->orWhere('aktivitas', 'like', '%update%')
                    ->orWhere('aktivitas', 'like', '%edit%')
                    ->orWhere('aktivitas', 'like', '%reset%');
            });
        } elseif ($jenisVal === 'Hapus') {
            $q->where(function ($sub) {
                $sub->where('aktivitas', 'like', '%hapus%')
                    ->orWhere('aktivitas', 'like', '%bersih%')
                    ->orWhere('aktivitas', 'like', '%clear%');
            });
        } elseif ($jenisVal === 'Sistem') {
            $q->where(function ($sub) {
                $sub->where('modul', 'like', '%Autentikasi%')
                    ->orWhere('modul', 'like', '%Login%')
                    ->orWhere('modul', 'like', '%Roles%')
                    ->orWhere('aktivitas', 'like', '%login%')
                    ->orWhere('aktivitas', 'like', '%logout%')
                    ->orWhere('aktivitas', 'like', '%akses%');
            });
        }

        $bulanVal = (int) request('bulan', 0);
        if ($bulanVal >= 1 && $bulanVal <= 12) {
            $q->whereMonth('created_at', $bulanVal);
        }

        $tahunVal = (int) request('tahun', 0);
        if ($tahunVal >= 2000 && $tahunVal <= 2100) {
            $q->whereYear('created_at', $tahunVal);
        }

        return $q->orderByDesc('created_at')->orderByDesc('id');
    };

    if (request()->has('_clear_riwayat_scoped')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        try {
            if ($__isMasterAdminRiwayat) {
                \App\Models\RiwayatAktivitas::query()->delete();
                $pesanClear = 'Seluruh catatan riwayat aktivitas berhasil dibersihkan oleh Master Admin.';
            } else {
                \App\Models\RiwayatAktivitas::where('user_id', $__userIdRiwayat)->delete();
                $pesanClear = 'Catatan riwayat aktivitas akun Anda berhasil dibersihkan.';
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $pesanClear,
            ]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Gagal membersihkan riwayat aktivitas.',
            ]);
        }
        exit;
    }

    if (request()->has('_poll_riwayat_realtime')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        try {
            $qPoll = $__buildRiwayatQuery();
            $totalCount = (clone $qPoll)->count();
            $perPage = 15;
            $currentPage = max(1, (int) request('page', 1));
            $rows = $qPoll->forPage($currentPage, $perPage)->get();

            $items = [];
            foreach ($rows as $idx => $r) {
                $idUnik = (is_object($r) && method_exists($r, 'getKey') && $r->getKey() !== null)
                    ? (string) $r->getKey()
                    : (string) ($r->id ?? md5(($r->created_at ?? '') . ($r->aktivitas ?? '') . $idx));

                $namaUser = $r->user ? (string) $r->user->nama_lengkap : 'Sistem / Tamu';
                $roleUserRaw = $r->user ? (string) $r->user->role : 'System';
                $roleLabel = str_replace('_', ' ', \Illuminate\Support\Str::title($roleUserRaw));
                $waktuStr = $r->created_at ? $r->created_at->format('d M Y, H:i') : '-';

                $items[] = [
                    'id'        => $idUnik,
                    'waktu'     => $waktuStr,
                    'nama_user' => $namaUser,
                    'role_user' => $roleLabel,
                    'is_self'   => (int) ($r->user_id ?? 0) === (int) $__userIdRiwayat,
                    'aktivitas' => (string) ($r->aktivitas ?? '-'),
                    'modul'     => (string) ($r->modul ?? '-'),
                    'status'    => (string) ($r->status ?? 'Informasi'),
                ];
            }

            $allIdsQuery = \App\Models\RiwayatAktivitas::query();
            if (!$__isMasterAdminRiwayat) {
                $allIdsQuery->whereNotNull('user_id')->where('user_id', $__userIdRiwayat);
            }
            $allScopedIds = $allIdsQuery->orderByDesc('id')->pluck('id')->map(fn($v) => (string) $v)->toArray();

            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo json_encode([
                'success'     => true,
                'is_master'   => $__isMasterAdminRiwayat,
                'total_count' => $totalCount,
                'items'       => $items,
                'scoped_ids'  => $allScopedIds,
            ]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false]);
        }
        exit;
    }

    try {
        $riwayats = $__buildRiwayatQuery()->paginate(15)->withQueryString();

        $qTahun = \App\Models\RiwayatAktivitas::query();
        if (!$__isMasterAdminRiwayat) {
            $qTahun->whereNotNull('user_id')->where('user_id', $__userIdRiwayat);
        }
        $tahunList = $qTahun->selectRaw('YEAR(created_at) as thn')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('thn')
            ->pluck('thn')
            ->filter()
            ->values()
            ->toArray();
        if (empty($tahunList)) {
            $tahunList = [(int) date('Y')];
        }
    } catch (\Throwable $e) {
    }

@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas - Sistem {{ $__isMasterAdminRiwayat ? 'Master Admin' : 'Admin' }}</title>
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

        main {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 25px;
            padding-top: 158px !important;
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: calc(100% - var(--sidebar-width));
        }

        .main-header {
            position: fixed !important;
            top: 0 !important;
            left: var(--sidebar-width, 270px) !important;
            right: 0 !important;
            width: calc(100% - var(--sidebar-width, 270px)) !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 16px 25px !important;
            margin: 0 !important;
            border-bottom: 2px solid var(--border-color) !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06) !important;
            z-index: 95 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
        }

        .navbar-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            width: 100%;
        }

        .navbar-search-row {
            width: 100%;
            padding-top: 10px;
            border-top: 1px dashed rgba(0, 0, 0, 0.12);
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
            align-items: center;
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

        .scope-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .scope-banner.master-mode {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }

        .scope-banner.admin-mode {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #065f46;
        }

        .rt-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #ffffff;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .rt-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseLiveDot 1.8s infinite;
        }

        @keyframes pulseLiveDot {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

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

        .user-self-tag {
            display: inline-block;
            margin-left: 6px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .rt-toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 13px 18px;
            border-radius: 12px;
            border-left: 4px solid #10b981;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 12px 28px rgba(0,0,0,0.22);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(130%);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .rt-toast-notif.show {
            transform: translateY(0);
            opacity: 1;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            .main-header {
                left: 0 !important;
                width: 100% !important;
                padding: 14px 16px !important;
            }
        }

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
    </style>
</head>
<body>

    @if($__isMasterAdminRiwayat)
        @include('layouts.mastersidebar')
    @else
        @include('layouts.adminsidebar')
    @endif

    <main>

        <header class="main-header" id="navbar-riwayat-fixed">
            <div class="navbar-top-row">
                <div class="header-title">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Riwayat Aktivitas</span>
                    </h1>
                    <p>
                        @if($__isMasterAdminRiwayat)
                            Pantau seluruh catatan log aktivitas dari Master Admin dan seluruh Admin secara real-time
                        @else
                            Pantau catatan log aktivitas pribadi Anda secara real-time
                        @endif
                    </p>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div class="rt-live-pill" title="Tabel ini otomatis memperbarui data tanpa perlu refresh halaman">
                        <span class="rt-live-dot"></span>
                        <span>Live Real-Time (<span id="label-jumlah-riwayat">{{ method_exists($riwayats, 'total') ? $riwayats->total() : $riwayats->count() }}</span> log)</span>
                    </div>
                    <button type="button" class="btn" id="btn-baca-semua-riwayat" onclick="prosesBacaSemuaRiwayat()" style="background: #10b981; color: #ffffff; border: 1px solid #059669; font-weight: 700;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Baca Semua</span>
                        <span id="badge-btn-baca-semua" style="display: none; background: #ffffff; color: #059669; font-size: 11px; font-weight: 800; padding: 1px 7px; border-radius: 999px; margin-left: 2px;">0</span>
                    </button>
                    <form id="form-clear-riwayat" action="{{ route('riwayat_aktivitas.clear') }}" method="POST" onsubmit="return prosesBersihkanLogRealtime(event);">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger-soft" id="btn-bersihkan-log" style="background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; font-weight: 700;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span>{{ $__isMasterAdminRiwayat ? 'Bersihkan Semua Log' : 'Bersihkan Log Saya' }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="navbar-search-row">
                <form method="GET" action="{{ url()->current() }}" class="filter-form" id="form-filter-riwayat">
                    <div class="search-box">
                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: var(--text-muted); flex-shrink: 0;"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input type="text" id="input-search-riwayat" name="search" value="{{ request('search') }}" placeholder="{{ $__isMasterAdminRiwayat ? 'Cari aktivitas, modul, atau nama pengguna...' : 'Cari aktivitas atau modul Anda...' }}" autocomplete="off">
                    </div>

                    <select name="jenis" id="select-jenis-riwayat" class="filter-select">
                        <option value="">-- Semua Jenis Aktivitas --</option>
                        <option value="Tambah" {{ request('jenis') == 'Tambah' ? 'selected' : '' }}>Penambahan Data</option>
                        <option value="Ubah" {{ request('jenis') == 'Ubah' ? 'selected' : '' }}>Perubahan Data</option>
                        <option value="Hapus" {{ request('jenis') == 'Hapus' ? 'selected' : '' }}>Penghapusan Data</option>
                        <option value="Sistem" {{ request('jenis') == 'Sistem' ? 'selected' : '' }}>Aktivitas Sistem (Login/Lainnya)</option>
                    </select>

                    <select name="bulan" id="select-bulan-riwayat" class="filter-select">
                        <option value="">-- Semua Bulan --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>

                    <select name="tahun" id="select-tahun-riwayat" class="filter-select">
                        <option value="">-- Semua Tahun --</option>
                        @foreach($tahunList ?? [] as $thn)
                            <option value="{{ $thn }}" {{ request('tahun') == $thn ? 'selected' : '' }}>
                                {{ $thn }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(request('search') || request('bulan') || request('tahun') || request('jenis'))
                        <a href="{{ url()->current() }}" class="btn" style="background: #e2e8f0; color: #334155;">Reset</a>
                    @endif
                </form>
            </div>
        </header>

        <div class="scope-banner {{ $__isMasterAdminRiwayat ? 'master-mode' : 'admin-mode' }}">
            <div style="display: flex; align-items: center; gap: 8px;">
                @if($__isMasterAdminRiwayat)
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span><strong>Akses Penuh Master Admin:</strong> Menampilkan seluruh riwayat aktivitas dari <strong>Master Admin</strong> maupun <strong>semua Admin</strong>.</span>
                @else
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <span><strong>Privasi Riwayat Admin:</strong> Anda hanya melihat riwayat aktivitas milik akun Anda sendiri (<strong>{{ $__userLoginRiwayat->nama_lengkap ?? 'Admin' }}</strong>).</span>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div id="alert-session-riwayat" style="background: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; border-left: 4px solid #10b981;">
                {{ session('success') }}
            </div>
        @endif

        <div class="card-box">

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
                    <tbody id="tbody-riwayat-aktivitas">
                        @forelse($riwayats as $riwayat)
                            @php
                                $idUnikRiwayat = (is_object($riwayat) && method_exists($riwayat, 'getKey') && $riwayat->getKey() !== null)
                                    ? $riwayat->getKey()
                                    : ($riwayat->id ?? md5(($riwayat->created_at ?? '') . ($riwayat->aktivitas ?? '') . $loop->index));
                                $isMilikSendiri = (int) ($riwayat->user_id ?? 0) === (int) $__userIdRiwayat;
                            @endphp
                            <tr class="riwayat-row" data-riwayat-id="{{ $idUnikRiwayat }}">
                                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                    <span class="waktu-text">{{ $riwayat->created_at ? $riwayat->created_at->format('d M Y, H:i') : '-' }}</span>
                                    <span class="badge-baru">BARU</span>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">
                                        {{ $riwayat->user ? $riwayat->user->nama_lengkap : 'Sistem / Tamu' }}
                                        @if($isMilikSendiri)
                                            <span class="user-self-tag">Anda</span>
                                        @endif
                                    </div>
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
                            <tr id="row-empty-riwayat">
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

    <div id="rt-toast-riwayat" class="rt-toast-notif">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <span id="rt-toast-riwayat-text">Aktivitas baru tercatat secara real-time!</span>
    </div>

    <input type="hidden" id="json-is-master-riwayat" value="{{ json_encode($__isMasterAdminRiwayat) }}">

    <script>
        (function () {
            const IS_MASTER_ADMIN = JSON.parse(document.getElementById('json-is-master-riwayat').value || 'false');
            let observerBaris = null;
            let toastTimer = null;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function tampilkanToastRealtime(pesan) {
                const toast = document.getElementById('rt-toast-riwayat');
                const textEl = document.getElementById('rt-toast-riwayat-text');
                if (!toast || !textEl) return;
                textEl.textContent = pesan;
                toast.classList.add('show');
                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toast.classList.remove('show');
                }, 3200);
            }

            let latestScopedIds = [];

            function prosesOtomatisTerbaca(tr) {
                if (!tr) return;
                const id = tr.getAttribute('data-riwayat-id');
                if (typeof window.tandaiSatuRiwayatDibaca === 'function') {
                    window.tandaiSatuRiwayatDibaca(id);
                }
                tr.classList.remove('belum-dibaca');
            }

            function daftarkanObserverKeBaris(tr) {
                if (!tr) return;
                tr.addEventListener('click', function () {
                    if (tr.classList.contains('belum-dibaca')) {
                        prosesOtomatisTerbaca(tr);
                    }
                });
            }

            window.prosesBacaSemuaRiwayat = function () {
                if (typeof window.tandaiSemuaRiwayatDibaca === 'function') {
                    window.tandaiSemuaRiwayatDibaca(latestScopedIds);
                }
                document.querySelectorAll('tr.riwayat-row.belum-dibaca').forEach(function (tr) {
                    tr.classList.remove('belum-dibaca');
                });
                if (typeof window.hitungRiwayatBelumDibaca === 'function') {
                    window.hitungRiwayatBelumDibaca();
                }
                tampilkanToastRealtime('Seluruh riwayat aktivitas telah ditandai sudah dibaca.');
            };

            function buatHtmlBarisRiwayat(item, paksaHighlightBaru = false) {
                const id = String(item.id || '');
                const sudahDibaca = !paksaHighlightBaru && (typeof window.apakahRiwayatSudahDibaca === 'function'
                    ? window.apakahRiwayatSudahDibaca(id)
                    : true);

                let badgeStatusHtml = `<span class="badge badge-info">${escapeHtml(item.status || 'Informasi')}</span>`;
                if (item.status === 'Berhasil') {
                    badgeStatusHtml = `<span class="badge badge-success">Berhasil</span>`;
                } else if (item.status === 'Peringatan') {
                    badgeStatusHtml = `<span class="badge badge-warning">Peringatan</span>`;
                }

                const tagAndaHtml = item.is_self ? `<span class="user-self-tag">Anda</span>` : '';

                const tr = document.createElement('tr');
                tr.className = 'riwayat-row' + (!sudahDibaca ? ' belum-dibaca' : '');
                tr.setAttribute('data-riwayat-id', id);
                tr.innerHTML = `
                    <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                        <span class="waktu-text">${escapeHtml(item.waktu)}</span>
                        <span class="badge-baru">BARU</span>
                    </td>
                    <td>
                        <div style="font-weight: 600;">
                            ${escapeHtml(item.nama_user)}
                            ${tagAndaHtml}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            ${escapeHtml(item.role_user)}
                        </div>
                    </td>
                    <td>${escapeHtml(item.aktivitas)}</td>
                    <td><span style="font-weight: 500;">${escapeHtml(item.modul)}</span></td>
                    <td>${badgeStatusHtml}</td>
                `;
                return tr;
            }

            let sedangPollingRiwayat = false;
            async function sinkronkanRiwayatRealtime() {
                if (sedangPollingRiwayat || document.hidden) return;
                sedangPollingRiwayat = true;

                try {
                    const pollUrl = new URL(window.location.href);
                    pollUrl.searchParams.set('_poll_riwayat_realtime', Date.now());

                    const res = await fetch(pollUrl.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (!res.ok) return;
                    const data = await res.json();
                    if (! data || ! data.success || ! Array.isArray(data.items)) return;

                    if (Array.isArray(data.scoped_ids)) {
                        latestScopedIds = data.scoped_ids.map(String);
                        window.__extraScopedRiwayatIds = latestScopedIds;
                    }

                    const labelJumlah = document.getElementById('label-jumlah-riwayat');
                    if (labelJumlah && typeof data.total_count === 'number') {
                        labelJumlah.textContent = String(data.total_count);
                    }

                    const tbody = document.getElementById('tbody-riwayat-aktivitas');
                    if (!tbody) return;

                    if (data.items.length === 0) {
                        if (!document.getElementById('row-empty-riwayat')) {
                            tbody.innerHTML = `
                                <tr id="row-empty-riwayat">
                                    <td colspan="5" class="empty-state">
                                        Belum ada catatan riwayat aktivitas yang terekam.
                                    </td>
                                </tr>
                            `;
                        }
                        return;
                    }

                    const existingRows = Array.from(tbody.querySelectorAll('tr.riwayat-row[data-riwayat-id]'));
                    const existingIds = existingRows.map(r => r.getAttribute('data-riwayat-id'));
                    const incomingIds = data.items.map(it => String(it.id));

                    const itemBaruList = data.items.filter(it => !existingIds.includes(String(it.id)));

                    if (itemBaruList.length > 0) {
                        const rowEmpty = document.getElementById('row-empty-riwayat');
                        if (rowEmpty) rowEmpty.remove();

                        for (let i = itemBaruList.length - 1; i >= 0; i--) {
                            const itemBaru = itemBaruList[i];
                            const trBaru = buatHtmlBarisRiwayat(itemBaru, true);
                            tbody.prepend(trBaru);
                            daftarkanObserverKeBaris(trBaru);
                        }

                        const semuaBarisSekarang = Array.from(tbody.querySelectorAll('tr.riwayat-row[data-riwayat-id]'));
                        if (semuaBarisSekarang.length > 15) {
                            semuaBarisSekarang.slice(15).forEach(trLama => trLama.remove());
                        }

                        const infoTerbaru = itemBaruList[0];
                        const pesanNotif = IS_MASTER_ADMIN
                            ? `Aktivitas baru (${infoTerbaru.nama_user}): ${infoTerbaru.aktivitas}`
                            : `Aktivitas baru tercatat: ${infoTerbaru.aktivitas}`;
                        if (typeof window.hitungRiwayatBelumDibaca === 'function') {
                            window.hitungRiwayatBelumDibaca();
                        }
                        tampilkanToastRealtime(pesanNotif);
                    } else if (JSON.stringify(existingIds) !== JSON.stringify(incomingIds)) {

                        tbody.innerHTML = '';
                        data.items.forEach(item => {
                            const tr = buatHtmlBarisRiwayat(item, false);
                            tbody.appendChild(tr);
                            daftarkanObserverKeBaris(tr);
                        });
                        if (typeof window.hitungRiwayatBelumDibaca === 'function') {
                            window.hitungRiwayatBelumDibaca();
                        }
                    }
                } catch (err) {
                } finally {
                    sedangPollingRiwayat = false;
                }
            }

            window.prosesBersihkanLogRealtime = async function (e) {
                if (e) e.preventDefault();
                const pesanKonfirmasi = IS_MASTER_ADMIN
                    ? 'Apakah Anda yakin ingin membersihkan SELURUH riwayat aktivitas (Master Admin & semua Admin)?'
                    : 'Apakah Anda yakin ingin membersihkan riwayat aktivitas milik akun Anda sendiri?';

                if (!confirm(pesanKonfirmasi)) {
                    return false;
                }

                const btn = document.getElementById('btn-bersihkan-log');
                const teksAsli = btn ? btn.textContent : '';
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Membersihkan...';
                }

                try {
                    const clearUrl = new URL(window.location.href);
                    clearUrl.searchParams.set('_clear_riwayat_scoped', Date.now());
                    const res = await fetch(clearUrl.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.success) {
                            const tbody = document.getElementById('tbody-riwayat-aktivitas');
                            if (tbody) {
                                tbody.innerHTML = `
                                    <tr id="row-empty-riwayat">
                                        <td colspan="5" class="empty-state">
                                            Belum ada catatan riwayat aktivitas yang terekam.
                                        </td>
                                    </tr>
                                `;
                            }
                            const labelJumlah = document.getElementById('label-jumlah-riwayat');
                            if (labelJumlah) labelJumlah.textContent = '0';
                            tampilkanToastRealtime(data.message || 'Riwayat aktivitas berhasil dibersihkan.');
                        }
                    }
                } catch (err) {
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = teksAsli;
                    }
                }
                return false;
            };

            function sesuaikanPaddingMainDenganNavbarFixed() {
                const mainEl = document.querySelector('main');
                const headerEl = document.getElementById('navbar-riwayat-fixed') || document.querySelector('.main-header');
                if (mainEl && headerEl) {
                    const tinggi = headerEl.offsetHeight || 135;
                    mainEl.style.setProperty('padding-top', (tinggi + 22) + 'px', 'important');
                }
            }

            window.addEventListener('load', sesuaikanPaddingMainDenganNavbarFixed);
            window.addEventListener('resize', sesuaikanPaddingMainDenganNavbarFixed);

            document.addEventListener('DOMContentLoaded', function () {
                sesuaikanPaddingMainDenganNavbarFixed();
                if ('ResizeObserver' in window) {
                    const headerEl = document.getElementById('navbar-riwayat-fixed') || document.querySelector('.main-header');
                    if (headerEl) {
                        new ResizeObserver(sesuaikanPaddingMainDenganNavbarFixed).observe(headerEl);
                    }
                }

                const barisList = document.querySelectorAll('tr.riwayat-row[data-riwayat-id]');

                barisList.forEach(function (tr) {
                    const id = tr.getAttribute('data-riwayat-id');
                    const sudahDibaca = typeof window.apakahRiwayatSudahDibaca === 'function'
                        ? window.apakahRiwayatSudahDibaca(id)
                        : true;

                    if (!sudahDibaca) {
                        tr.classList.add('belum-dibaca');
                    }
                    daftarkanObserverKeBaris(tr);
                });

                if (typeof window.hitungRiwayatBelumDibaca === 'function') {
                    window.hitungRiwayatBelumDibaca();
                }

                setInterval(sinkronkanRiwayatRealtime, 2000);
            });
        })();
    </script>

</body>
</html>
