@php
    $__roleUserNotif = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace([' ', '-'], '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';

    $resetReqFilePath = storage_path('app/password_reset_requests.json');
    $lockFilePath = storage_path('app/admin_login_locks.json');

    $daftarResetRequests = [];
    if (is_file($resetReqFilePath)) {
        $decReq = json_decode((string) file_get_contents($resetReqFilePath), true);
        if (is_array($decReq)) {
            $daftarResetRequests = $decReq;
        }
    }

    if (request()->has('_notif_reset_action')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if ($__roleUserNotif !== 'master_admin' && $__roleUserNotif !== 'masteradmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya Master Admin yang berwenang.']);
            exit;
        }

        $act = (string) request('_notif_reset_action');

        if ($act === 'poll_requests') {
            $pendingList = array_values(array_filter($daftarResetRequests, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
            echo json_encode([
                'success'       => true,
                'pending_count' => count($pendingList),
                'requests'      => $pendingList,
            ]);
            exit;
        }

        if ($act === 'process_reset') {
            $reqId = trim((string) request('req_id', ''));
            $newPass = trim((string) request('new_password', ''));
            $targetEmail = '';
            $targetNama = 'Admin';

            foreach ($daftarResetRequests as &$rItem) {
                if (($rItem['id'] ?? '') === $reqId) {
                    $rItem['status'] = 'completed';
                    $rItem['completed_at'] = now()->format('d M Y, H:i');
                    $targetEmail = (string) ($rItem['email'] ?? '');
                    $targetNama = (string) ($rItem['nama'] ?? 'Admin');
                    break;
                }
            }
            unset($rItem);

            if ($targetEmail !== '') {
                foreach ($daftarResetRequests as &$rItem2) {
                    if (strtolower((string) ($rItem2['email'] ?? '')) === strtolower($targetEmail)) {
                        $rItem2['status'] = 'completed';
                    }
                }
                unset($rItem2);
            }

            try {
                file_put_contents($resetReqFilePath, json_encode($daftarResetRequests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }

            $pesanPassword = 'Blokir login Admin berhasil dibuka.';
            if ($newPass !== '' && $targetEmail !== '') {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                        $affected = \Illuminate\Support\Facades\DB::table('users')
                            ->where('email', $targetEmail)
                            ->whereIn('role', ['admin', 'Admin', 'user', 'User'])
                            ->update([
                                'password' => \Illuminate\Support\Facades\Hash::make($newPass),
                            ]);
                        if ($affected === 0 && \Illuminate\Support\Facades\Schema::hasColumn('users', 'nama_lengkap')) {
                            $affected = \Illuminate\Support\Facades\DB::table('users')
                                ->where('nama_lengkap', $targetEmail)
                                ->whereIn('role', ['admin', 'Admin', 'user', 'User'])
                                ->update([
                                    'password' => \Illuminate\Support\Facades\Hash::make($newPass),
                                ]);
                        }
                        if ($affected > 0) {
                            $pesanPassword = 'Password baru untuk ' . $targetEmail . ' berhasil disimpan & blokir login dibuka!';
                        } else {
                            $pesanPassword = 'Permintaan diselesaikan & blokir login dibuka (Email ' . $targetEmail . ').';
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            $resetLock = [
                'failed_count' => 0,
                'is_blocked'   => false,
                'last_email'   => '',
                'blocked_at'   => null,
                'updated_at'   => round(microtime(true) * 1000),
                'clients'      => [],
            ];
            try {
                file_put_contents($lockFilePath, json_encode($resetLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }

            if (class_exists(\App\Models\RiwayatAktivitas::class)) {
                try {
                    $riwayat = new \App\Models\RiwayatAktivitas();
                    $tbl = $riwayat->getTable();
                    $dataLog = [];
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'user_id')) {
                        $dataLog['user_id'] = \Illuminate\Support\Facades\Auth::id();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'aktivitas')) {
                        $infoPass = $newPass !== '' ? ' dan memperbarui password baru' : '';
                        $dataLog['aktivitas'] = 'Menyetujui permintaan reset password' . $infoPass . ' serta membuka blokir login Admin: ' . $targetNama . ' (' . $targetEmail . ').';
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'modul')) {
                        $dataLog['modul'] = 'Notifikasi & Keamanan';
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'status')) {
                        $dataLog['status'] = 'Berhasil';
                    }
                    $riwayat->forceFill($dataLog)->save();
                } catch (\Throwable $e) {
                }
            }

            $sisaPending = count(array_filter($daftarResetRequests, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
            echo json_encode([
                'success'       => true,
                'message'       => $pesanPassword,
                'pending_count' => $sisaPending,
            ]);
            exit;
        }

        if ($act === 'dismiss_reset') {
            $reqId = trim((string) request('req_id', ''));
            foreach ($daftarResetRequests as &$rItem) {
                if (($rItem['id'] ?? '') === $reqId) {
                    $rItem['status'] = 'dismissed';
                    break;
                }
            }
            unset($rItem);
            try {
                file_put_contents($resetReqFilePath, json_encode($daftarResetRequests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }
            $sisaPending = count(array_filter($daftarResetRequests, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
            echo json_encode(['success' => true, 'pending_count' => $sisaPending]);
            exit;
        }

        echo json_encode(['success' => false]);
        exit;
    }

    $pendingResetList = array_values(array_filter($daftarResetRequests, fn($r) => ($r['status'] ?? 'pending') === 'pending'));
    $jumlahPendingReset = count($pendingResetList);

    $__roleUserNotif = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';
    $__isMasterAdminNotif = ($__roleUserNotif === 'master_admin' || $__roleUserNotif === 'masteradmin') && request('panel') !== 'admin';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Master Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --text-color: #000;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.15);
            --sidebar-width: 270px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; display: flex; min-height: 100vh; overflow-x: hidden; }
        main { margin-left: var(--sidebar-width); padding: 25px; width: calc(100% - var(--sidebar-width)); display: flex; flex-direction: column; gap: 20px; }

        .main-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; }
        .header-title h1 { font-size: 24px; font-weight: 700; color: var(--primary-color); }
        .header-title p { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }

        .btn { padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); display: inline-flex; justify-content: center; align-items: center; text-decoration: none; }
        .btn-primary { background: var(--primary-color); color: #fff; border: none; }
        .btn:hover { opacity: 0.9; }

        .notif-ringkas { display: grid; grid-template-columns: repeat(auto-fit, minmax(165px, 1fr)); gap: 14px; margin-bottom: 20px; }
        @media (max-width: 1200px) { .notif-ringkas { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 800px) { .notif-ringkas { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 500px) { .notif-ringkas { grid-template-columns: 1fr; } }

        .notif-kartu { --warna: #1a4f91; display: flex; flex-direction: column; background: #fff; border: 1px solid var(--border-color); border-top: 4px solid var(--warna); border-radius: 14px; padding: 16px 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .notif-kartu .angka { font-size: 30px; font-weight: 800; color: var(--warna); line-height: 1.1; }
        .notif-kartu .judul { font-size: 14px; font-weight: 700; margin-top: 4px; color: var(--text-color); }
        .notif-kartu .ket { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .kartu-semua { --warna: #1a4f91; }
        .kartu-rusak { --warna: #e11d48; }
        .kartu-terlewat { --warna: #dc2626; }
        .kartu-waktunya { --warna: #f59e0b; }
        .kartu-mendekati { --warna: #0ea5e9; }
        .kartu-reset-pw { --warna: #7c3aed; }

        .reset-req-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 10px;
        }
        .reset-req-section:empty {
            display: none;
        }
        .reset-req-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            background: #faf5ff;
            border: 1px solid #d8b4fe;
            border-left: 5px solid #7c3aed;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.08);
            animation: slideInReset 0.35s ease;
        }
        @keyframes slideInReset {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .reset-req-badge {
            background: #7c3aed;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 11px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.3px;
        }
        .reset-req-form-inline {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .reset-req-input-pw {
            padding: 8px 12px;
            border: 1px solid #c084fc;
            border-radius: 8px;
            font-size: 12.5px;
            outline: none;
            background: #fff;
            min-width: 210px;
        }
        .reset-req-input-pw:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }
        .btn-proses-reset {
            background: #7c3aed;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-proses-reset:hover {
            background: #6d28d9;
        }
        .btn-abaikan-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-abaikan-reset:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .toast-notif-rt {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #0f172a;
            color: #fff;
            border-left: 4px solid #a855f7;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 12px 28px rgba(0,0,0,0.22);
            z-index: 99999;
            transform: translateX(120%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-notif-rt.show {
            transform: translateX(0);
        }

        .notif-filter { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 0; width: 100%; }
        .notif-filter .cari-wrap {
            position: relative;
            flex: 1 1 260px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 8px 14px;
            transition: all 0.2s ease;
        }
        .notif-filter .cari-wrap:focus-within {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.12);
        }
        .notif-filter input[type="text"] {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 13px;
            color: var(--text-color);
            outline: none;
        }
        .notif-filter select {
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            background: #fff;
            color: var(--text-color);
            transition: all 0.2s ease;
        }
        .notif-filter select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.12);
        }
        .notif-filter .reset {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 9px 14px;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #fecaca;
            transition: 0.2s;
        }
        .notif-filter .reset:hover {
            background: #fecaca;
        }

        .notif-list { display: flex; flex-direction: column; gap: 12px; }
        .notif-item { display: flex; gap: 16px; align-items: flex-start; background: #fff; border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 18px; transition: 0.2s; }
        .notif-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .notif-badge { text-align: center; padding: 6px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; color: #fff; display: inline-block; white-space: nowrap; }

        .notif-isi { flex: 1; min-width: 0; }
        .notif-nama { font-size: 15px; font-weight: 700; }
        .notif-kode { font-size: 12px; color: var(--text-muted); margin-top: 4px; word-break: break-all; }
        .notif-meta { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 8px; font-size: 12.5px; color: #334155; }
        .notif-meta b { color: var(--text-muted); font-weight: 600; }
        .notif-aksi { flex-shrink: 0; align-self: center; }
        .btn-lihat { display: inline-block; padding: 8px 14px; border-radius: 8px; background: rgba(26, 79, 145, 0.1); color: var(--primary-color); font-size: 12.5px; font-weight: 700; text-decoration: none; }
        .btn-lihat:hover { background: var(--primary-color); color: #fff; }

        .notif-kosong { text-align: center; padding: 50px 20px; color: var(--text-muted); background: #fff; border: 1px dashed var(--border-color); border-radius: 14px; }
        .notif-kosong .judul { font-size: 16px; font-weight: 700; color: #0f766e; margin-bottom: 4px; }
        .notif-info { font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }

        .custom-pagination-wrap nav > div:first-child { display: none !important; }
        .custom-pagination-wrap nav > div:last-child { display: flex !important; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; width: 100%; }
        .custom-pagination-wrap nav p { font-size: 13px !important; color: var(--text-muted) !important; margin: 0 !important; }
        .custom-pagination-wrap nav p span { font-weight: 800 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav > div:last-child > div:last-child > span { display: inline-flex !important; border-radius: 8px !important; border: 1px solid var(--border-color) !important; background: #fff !important; overflow: hidden !important; }
        .custom-pagination-wrap nav span > a, .custom-pagination-wrap nav span > span { display: inline-flex !important; align-items: center !important; justify-content: center !important; min-width: 38px !important; padding: 8px 12px !important; font-size: 13px !important; font-weight: 600 !important; color: #475569 !important; text-decoration: none !important; background: #fff !important; border-right: 1px solid var(--border-color) !important; cursor: pointer !important; margin: 0 !important; border-top: none !important; border-bottom: none !important; border-left: none !important; }
        .custom-pagination-wrap nav span > *:last-child { border-right: none !important; }
        .custom-pagination-wrap nav span > a:hover { background: #f1f5f9 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav span[aria-current="page"] > span { background: var(--primary-color) !important; color: #fff !important; }
        .custom-pagination-wrap nav span[aria-disabled="true"] > span { color: #cbd5e1 !important; background: #f8fafc !important; cursor: not-allowed !important; }
        .custom-pagination-wrap nav svg { width: 16px !important; height: 16px !important; }

        .main-header {
            position: fixed !important;
            top: 0 !important;
            left: var(--sidebar-width, 270px) !important;
            right: 0 !important;
            width: calc(100% - var(--sidebar-width, 270px)) !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 16px 25px !important;
            margin: 0 !important;
            border-bottom: 2px solid var(--border-color) !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06) !important;
            z-index: 95 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
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

        .navbar-date-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            padding: 6px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }

        main {
            padding-top: 165px !important;
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

    </style>
</head>

<body>

    @include('layouts.mastersidebar')

    <main>
        @php
            $paramDasar = array_filter(['q' => request('q'), 'cabang' => request('cabang')], fn ($v) => $v !== null && $v !== '');
            $adaFilter  = $tipe !== 'semua' || !empty($paramDasar);
        @endphp

        <header class="main-header" id="navbar-notifikasi-fixed">
            <div class="navbar-top-row">
                <div class="header-title">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span>Pusat Notifikasi Aset</span>
                    </h1>
                    <p>Memantau aset yang mendekati jadwal servis atau yang sedang berstatus rusak.</p>
                </div>

                <div class="navbar-date-pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>
                        Hari ini: {{ $hariIni->format('d/m/Y') }}
                        @if($notifs->total() > 0)
                            &middot; Menampilkan {{ $notifs->firstItem() }}-{{ $notifs->lastItem() }} dari {{ $notifs->total() }} data
                        @endif
                    </span>
                </div>
            </div>

            <div class="navbar-search-row">
                <form method="GET" action="{{ route('notifikasi.index') }}" class="notif-filter" id="form-filter-notifikasi">

                    <div class="cari-wrap">
                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: var(--text-muted); flex-shrink: 0;">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                        <input type="text" id="input-search-notifikasi" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama aset, merk, ruangan, cabang, atau teknisi..." autocomplete="off">
                    </div>

                    <select name="tipe" id="select-tipe-notifikasi" onchange="this.form.submit()">
                        <option value="semua" {{ $tipe === 'semua' ? 'selected' : '' }}>Semua Jenis Peringatan</option>
                        <option value="rusak" {{ $tipe === 'rusak' ? 'selected' : '' }}>Hanya Aset Rusak</option>
                        <option value="terlewat" {{ $tipe === 'terlewat' ? 'selected' : '' }}>Hanya Terlewat Servis</option>
                        <option value="waktunya" {{ $tipe === 'waktunya' ? 'selected' : '' }}>Hanya Servis Mendesak</option>
                        <option value="mendekati" {{ $tipe === 'mendekati' ? 'selected' : '' }}>Hanya Mendekati Servis</option>
                    </select>

                    <select name="cabang" id="select-cabang-notifikasi" onchange="this.form.submit()">
                        <option value="">Semua Cabang</option>
                        @foreach($cabangs as $cabang)
                            <option value="{{ $cabang->getKey() }}" {{ (string) request('cabang') === (string) $cabang->getKey() ? 'selected' : '' }}>{{ $cabang->nama_cabang }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary" style="padding: 9px 18px; border-radius: 10px; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Terapkan</span>
                    </button>
                    @if($adaFilter)
                        <a href="{{ route('notifikasi.index') }}" class="reset">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            <span>Reset filter</span>
                        </a>
                    @endif
                </form>
            </div>
        </header>

        <div class="notif-ringkas">
            <div class="notif-kartu kartu-semua">
                <div class="angka">{{ $hitung['semua'] }}</div>
                <div class="judul">Semua Peringatan</div>
                <div class="ket">Servis & Kerusakan</div>
            </div>
            <div class="notif-kartu kartu-rusak">
                <div class="angka">{{ $hitung['rusak'] }}</div>
                <div class="judul">Aset Rusak</div>
                <div class="ket">Parah, Sedang, & Ringan</div>
            </div>
            <div class="notif-kartu kartu-terlewat">
                <div class="angka">{{ $hitung['terlewat'] }}</div>
                <div class="judul">Terlewat Servis</div>
                <div class="ket">Sudah lewat tanggal</div>
            </div>
            <div class="notif-kartu kartu-waktunya">
                <div class="angka">{{ $hitung['waktunya'] }}</div>
                <div class="judul">Mendesak</div>
                <div class="ket">0 s/d {{ $batas['waktunya'] }} hari lagi</div>
            </div>
            <div class="notif-kartu kartu-mendekati">
                <div class="angka">{{ $hitung['mendekati'] }}</div>
                <div class="judul">Mendekati Servis</div>
                <div class="ket">{{ $batas['waktunya'] + 1 }} - {{ $batas['mendekati'] }} hari</div>
            </div>
            @if($__isMasterAdminNotif)
            <div class="notif-kartu kartu-reset-pw">
                <div class="angka" id="angka-pending-reset">{{ $jumlahPendingReset }}</div>
                <div class="judul">Permintaan Reset</div>
                <div class="ket">Reset Sandi & Blokir Admin</div>
            </div>
            @endif
        </div>

        @if($__isMasterAdminNotif)

        <div id="container-reset-requests" class="reset-req-section">
            @foreach($pendingResetList as $reqItem)
                <div class="reset-req-card" id="reset-card-{{ $reqItem['id'] }}" data-req-id="{{ $reqItem['id'] }}">
                    <div style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 260px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="reset-req-badge"> PERMINTAAN RESET PASSWORD ADMIN</span>
                            @if(!empty($reqItem['is_blocked']))
                                <span class="notif-badge" style="background: #dc2626;">AKUN TERBLOKIR (3/3 GAGAL)</span>
                            @endif
                            <span style="font-size: 11.5px; color: #64748b; font-weight: 600;"> {{ $reqItem['created_at'] ?? 'Baru saja' }}</span>
                        </div>
                        <div style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 2px;">
                            {{ $reqItem['nama'] ?? 'Admin' }} <span style="font-weight: 600; color: #7c3aed; font-size: 13px;">({{ $reqItem['email'] ?? '-' }})</span>
                        </div>
                        <div style="font-size: 12.5px; color: #475569; line-height: 1.4;">
                            <b>Pesan Admin:</b> "{{ $reqItem['catatan'] ?? 'Meminta peresetan password & pembukaan blokir akun.' }}"
                        </div>
                    </div>
                    <div class="reset-req-form-inline">
                        <input type="text" id="input-new-pw-{{ $reqItem['id'] }}" class="reset-req-input-pw" placeholder="Isi password baru (opsional)...">
                        <button type="button" class="btn-proses-reset" onclick="prosesResetPasswordAdmin('{{ $reqItem['id'] }}')">
                            Reset & Buka Blokir
                        </button>
                        <button type="button" class="btn-abaikan-reset" onclick="abaikanPermintaanReset('{{ $reqItem['id'] }}')" title="Tutup permintaan ini">
                            Tutup
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="notif-list">
            @forelse($notifs as $aset)
            @php

                $warnaRusak = '#ef4444';
                if(isset($statuses)) {
                    $match = $statuses->where('nama_status', $aset->status_aset)->first();
                    if($match && !empty($match->warna_status)) $warnaRusak = $match->warna_status;
                }

                $warnaServis = '#1a4f91';
                $teksServis = '';
                $labelServis = '';

                if ($aset->jenis_servis) {
                    $teksServis = $aset->jenis_servis === 'terlewat'
                        ? 'Terlewat ' . abs($aset->selisih) . ' hari'
                        : ($aset->selisih === 0 ? 'Hari ini' : $aset->selisih . ' hari lagi');

                    $warnaServis = $aset->jenis_servis === 'mendekati' ? '#f59e0b' : '#ef4444';
                    $labelServis = ['terlewat' => 'TERLEWAT SERVIS', 'waktunya' => 'MENDESAK SERVIS', 'mendekati' => 'MENDEKATI SERVIS'][$aset->jenis_servis];
                }

                $borderCard = $aset->is_rusak ? $warnaRusak : $warnaServis;
                $hex = ltrim($borderCard, '#');
                $bgCard = 'rgba(26, 79, 145, 0.03)';
                if (strlen($hex) == 6) {
                    $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                    $bgCard = "rgba($r, $g, $b, 0.05)";
                }
            @endphp

            <div class="notif-item" data-border-left="{{ $borderCard }}" data-bg="{{ $bgCard }}">

                <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; min-width: 130px;">
                    @if($aset->is_rusak)
                        <div class="notif-badge" data-bg="{{ $warnaRusak }}" style="border: 1px solid rgba(0,0,0,0.1);" title="Status Kerusakan Aset">
                            {{ strtoupper($aset->status_aset) }}
                        </div>
                    @endif

                    @if($aset->jenis_servis)
                        <div class="notif-badge" data-bg="{{ $warnaServis }}" style="border: 1px solid rgba(0,0,0,0.1);" title="{{ $labelServis }}">
                            {{ $teksServis }}
                        </div>
                    @endif
                </div>

                <div class="notif-isi">
                    <div class="notif-nama">{{ $aset->item->nama_aset ?? 'Aset tidak diketahui' }}</div>
                    <div class="notif-kode">
                        {{ $aset->kode_aset }}
                        @if($aset->is_rusak) &middot; <span data-color="{{ $warnaRusak }}" style="font-weight: 700;">{{ $aset->status_aset }}</span> @endif
                        @if($aset->jenis_servis) &middot; <span data-color="{{ $warnaServis }}" style="font-weight: 700;">{{ $labelServis }}</span> @endif
                    </div>

                    <div class="notif-meta">
                        <span><b>Cabang:</b> {{ $aset->cabang->nama_cabang ?? '-' }}</span>
                        <span><b>Kategori:</b> {{ $aset->kategori->nama_kategori ?? '-' }}</span>
                        <span><b>Ruangan:</b> {{ $aset->Ruangan ?: '-' }}</span>
                        <span><b>Merk:</b> {{ $aset->merk ?: '-' }}</span>
                        <span><b>Servis berikutnya:</b> {{ $aset->servis_berikutnya ? \Carbon\Carbon::parse($aset->servis_berikutnya)->format('d/m/Y') : 'Tidak diatur' }}</span>
                        <span><b>Teknisi:</b> {{ $aset->teknisi ?: '-' }}</span>
                    </div>
                </div>

                <div class="notif-aksi">
                    <a class="btn-lihat" href="{{ route('aset.index', ['search' => $aset->kode_aset]) }}">Lihat Data</a>
                </div>
            </div>
            @empty
            <div class="notif-kosong">
                @if($adaFilter)
                    <div class="judul">Tidak ada notifikasi yang cocok</div>
                    <div>Coba ubah kata kunci, cabang, atau jenis peringatan di kotak dropdown.</div>
                @else
                    <div class="judul">Sistem Bebas Peringatan</div>
                    <div>Semua aset dalam kondisi baik. Belum ada aset yang rusak atau yang jadwal servisnya mendekati dalam {{ $batas['mendekati'] }} hari ke depan.</div>
                @endif
            </div>
            @endforelse
        </div>

        @if($notifs->hasPages())
        <div class="custom-pagination-wrap" style="margin-top: 20px;">
            {{ $notifs->links() }}
        </div>
        @endif
    </main>
    @if($__isMasterAdminNotif)
    <script>
        (function () {
            const knownResetIds = new Set();
            document.querySelectorAll('.reset-req-card[data-req-id]').forEach(el => {
                knownResetIds.add(String(el.getAttribute('data-req-id')));
            });

            function showNotifToast(pesan) {
                document.querySelectorAll('.toast-notif-rt').forEach(el => el.remove());
                const t = document.createElement('div');
                t.className = 'toast-notif-rt';
                t.innerHTML = `<span>${pesan}</span>`;
                document.body.appendChild(t);
                requestAnimationFrame(() => t.classList.add('show'));
                setTimeout(() => {
                    t.classList.remove('show');
                    setTimeout(() => t.remove(), 400);
                }, 4000);
            }

            function updateCounterReset(jumlah) {
                const angkaEl = document.getElementById('angka-pending-reset');
                if (angkaEl) angkaEl.textContent = String(jumlah);
            }

            window.prosesResetPasswordAdmin = async function (reqId) {
                const pwInput = document.getElementById('input-new-pw-' + reqId);
                const newPassword = pwInput ? pwInput.value.trim() : '';

                try {
                    const params = new URLSearchParams({
                        _notif_reset_action: 'process_reset',
                        req_id: reqId,
                        new_password: newPassword
                    });
                    const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    const data = await res.json();
                    if (data && data.success) {
                        const card = document.getElementById('reset-card-' + reqId);
                        if (card) card.remove();
                        updateCounterReset(data.pending_count ?? 0);
                        showNotifToast(' ' + (data.message || 'Permintaan reset password berhasil diproses!'));
                    }
                } catch (e) {
                    showNotifToast('Gagal memproses permintaan reset.');
                }
            };

            window.abaikanPermintaanReset = async function (reqId) {
                try {
                    const params = new URLSearchParams({
                        _notif_reset_action: 'dismiss_reset',
                        req_id: reqId
                    });
                    const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    const data = await res.json();
                    if (data && data.success) {
                        const card = document.getElementById('reset-card-' + reqId);
                        if (card) card.remove();
                        updateCounterReset(data.pending_count ?? 0);
                    }
                } catch (e) {}
            };

            function buatKartuResetHTML(reqItem) {
                const card = document.createElement('div');
                card.className = 'reset-req-card';
                card.id = 'reset-card-' + reqItem.id;
                card.setAttribute('data-req-id', reqItem.id);

                const badgeBlocked = reqItem.is_blocked
                    ? '<span class="notif-badge" style="background: #dc2626;">AKUN TERBLOKIR (3/3 GAGAL)</span>'
                    : '';

                card.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 260px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="reset-req-badge"> PERMINTAAN RESET PASSWORD ADMIN</span>
                            ${badgeBlocked}
                            <span style="font-size: 11.5px; color: #64748b; font-weight: 600;"> ${reqItem.created_at || 'Baru saja'}</span>
                        </div>
                        <div style="font-size: 15px; font-weight: 800; color: #1e293b; margin-top: 2px;">
                            ${reqItem.nama || 'Admin'} <span style="font-weight: 600; color: #7c3aed; font-size: 13px;">(${reqItem.email || '-'})</span>
                        </div>
                        <div style="font-size: 12.5px; color: #475569; line-height: 1.4;">
                            <b>Pesan Admin:</b> "${reqItem.catatan || 'Meminta peresetan password & pembukaan blokir akun.'}"
                        </div>
                    </div>
                    <div class="reset-req-form-inline">
                        <input type="text" id="input-new-pw-${reqItem.id}" class="reset-req-input-pw" placeholder="Isi password baru (opsional)...">
                        <button type="button" class="btn-proses-reset" onclick="prosesResetPasswordAdmin('${reqItem.id}')">
                            Reset & Buka Blokir
                        </button>
                        <button type="button" class="btn-abaikan-reset" onclick="abaikanPermintaanReset('${reqItem.id}')" title="Tutup permintaan ini">
                            Tutup
                        </button>
                    </div>
                `;
                return card;
            }

            setInterval(async () => {
                if (document.hidden) return;
                try {
                    const res = await fetch(`{{ url()->current() }}?_notif_reset_action=poll_requests&_t=${Date.now()}`, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data && data.success && Array.isArray(data.requests)) {
                        updateCounterReset(data.pending_count ?? data.requests.length);
                        const container = document.getElementById('container-reset-requests');
                        if (!container) return;

                        data.requests.slice().reverse().forEach(reqItem => {
                            const strId = String(reqItem.id);
                            if (!knownResetIds.has(strId)) {
                                knownResetIds.add(strId);
                                const newCard = buatKartuResetHTML(reqItem);
                                container.prepend(newCard);
                                showNotifToast(` Permintaan Reset Password baru dari Admin (${reqItem.email})!`);
                            }
                        });
                    }
                } catch (e) {}
            }, 3000);
        })();
    </script>
    @endif

    <script>
        (function () {
            function sesuaikanPaddingMainDenganHeaderFixed() {
                const mainEl = document.querySelector('main');
                const headerEl = document.getElementById('navbar-notifikasi-fixed') || (mainEl ? mainEl.querySelector('.main-header') : document.querySelector('.main-header'));
                if (mainEl && headerEl) {
                    const tinggi = headerEl.offsetHeight || 135;
                    mainEl.style.setProperty('padding-top', (tinggi + 22) + 'px', 'important');
                }
            }
            document.addEventListener('DOMContentLoaded', sesuaikanPaddingMainDenganHeaderFixed);
            window.addEventListener('load', sesuaikanPaddingMainDenganHeaderFixed);
            window.addEventListener('resize', sesuaikanPaddingMainDenganHeaderFixed);
            if ('ResizeObserver' in window) {
                document.addEventListener('DOMContentLoaded', function () {
                    const headerEl = document.getElementById('navbar-notifikasi-fixed') || document.querySelector('.main-header');
                    if (headerEl) {
                        new ResizeObserver(sesuaikanPaddingMainDenganHeaderFixed).observe(headerEl);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const inputSearch = document.getElementById('input-search-notifikasi');
                const notifList = document.querySelector('.notif-list');
                if (!inputSearch || !notifList) return;

                inputSearch.addEventListener('input', function () {
                    const keyword = (inputSearch.value || '').trim().toLowerCase();
                    const items = notifList.querySelectorAll('.notif-item');
                    if (items.length === 0) return;

                    let visibleCount = 0;
                    items.forEach(function (item) {
                        const text = (item.textContent || '').toLowerCase();
                        const match = keyword === '' || text.indexOf(keyword) !== -1;
                        item.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });

                    let emptyClientEl = document.getElementById('notif-kosong-live-search');
                    if (visibleCount === 0 && keyword !== '') {
                        if (!emptyClientEl) {
                            emptyClientEl = document.createElement('div');
                            emptyClientEl.id = 'notif-kosong-live-search';
                            emptyClientEl.className = 'notif-kosong';
                            emptyClientEl.innerHTML = '<div class="judul">Tidak ada notifikasi di halaman ini yang cocok</div><div>Tekan Enter atau klik tombol <b>Terapkan</b> di navbar untuk mencari di seluruh halaman database.</div>';
                            notifList.appendChild(emptyClientEl);
                        }
                        emptyClientEl.style.display = '';
                    } else if (emptyClientEl) {
                        emptyClientEl.style.display = 'none';
                    }
                });
            });
        })();
    </script>

    <script>
        (function () {
            document.querySelectorAll('[data-bg]').forEach(function (el) {
                el.style.background = el.getAttribute('data-bg');
            });
            document.querySelectorAll('[data-color]').forEach(function (el) {
                el.style.color = el.getAttribute('data-color');
            });
            document.querySelectorAll('[data-border-left]').forEach(function (el) {
                el.style.borderLeft = '5px solid ' + el.getAttribute('data-border-left');
            });
        })();
    </script>
</body>

</html>
