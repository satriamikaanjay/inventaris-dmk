@php
if (request()->has('_log_riwayat') && class_exists(\App\Models\RiwayatAktivitas::class)) {
    try {
        $teksAktivitas = trim((string) request('_log_aktivitas', ''));
        $namaModul = trim((string) request('_log_modul', 'Kategori & Aset'));
        $statusLog = trim((string) request('_log_status', 'Berhasil'));
        $userId = \Illuminate\Support\Facades\Auth::id();

        if ($teksAktivitas !== '') {
            $sudahAda = \App\Models\RiwayatAktivitas::where('user_id', $userId)
                ->where('aktivitas', $teksAktivitas)
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();

            if (!$sudahAda) {
                $riwayatBaru = new \App\Models\RiwayatAktivitas();
                $tabelRiwayat = $riwayatBaru->getTable();
                $dataLog = [];

                if (\Illuminate\Support\Facades\Schema::hasColumn($tabelRiwayat, 'user_id')) {
                    $dataLog['user_id'] = $userId;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tabelRiwayat, 'aktivitas')) {
                    $dataLog['aktivitas'] = $teksAktivitas;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tabelRiwayat, 'modul')) {
                    $dataLog['modul'] = $namaModul;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tabelRiwayat, 'status')) {
                    $dataLog['status'] = $statusLog;
                }

                $riwayatBaru->forceFill($dataLog)->save();
            }
        }
    } catch (\Throwable $e) {
    }
}
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Kategori & Aset - Master Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        body.is-updating-global {
            cursor: wait !important;
        }

        body.is-updating-global .chip-wrapper {
            pointer-events: none !important;
            opacity: 0.7;
        }

        body.is-updating-global .chip-wrapper.is-editing {
            opacity: 1;
        }

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
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        main {
            margin-left: var(--sidebar-width);
            padding: 25px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 5px;
        }

        .btn {
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: #fff;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: #fff;
            border: none;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .card-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-color);
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 10px;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
        }

        .category-chips {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }

        .chip-wrapper {
            display: flex;
            flex-direction: column;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .chip-wrapper:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .chip-wrapper.active {
            background: #fff;
            border-color: var(--primary-color);
            transform: translateY(0);
            box-shadow: 0 0 0 2px rgba(26, 79, 145, 0.2);
        }

        .chip-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .chip-name {
            color: #1e293b;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .chip-code {
            background: var(--primary-color);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }

        .chip-wrapper.is-editing .chip-content {
            display: none;
        }

        .chip-wrapper.is-editing .chip-actions {
            display: none !important;
        }

        .chip-edit-form {
            display: none;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .chip-wrapper.is-editing .chip-edit-form {
            display: flex;
        }

        .chip-edit-form .edit-input-group {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .chip-edit-form input,
        .chip-edit-form select {
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            color: #000;
            width: 100%;
            outline: none;
        }

        .chip-edit-form input[name="kode_kategori"] {
            width: 70px;
            text-align: center;
            font-weight: 700;
            background: #f8fafc;
        }

        .btn-update-sm {
            background: var(--secondary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-cancel-sm {
            background: #e2e8f0;
            color: #1e293b;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-edit-sm {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-delete-sm {
            background: #fee2e2;
            color: var(--danger-color);
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .chip-actions {
            display: none;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
            width: 100%;
            animation: slideDown 0.2s ease;
        }

        .chip-wrapper.active .chip-actions {
            display: flex;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                margin-top: 0;
                padding-top: 0;
                max-height: 0;
            }

            to {
                opacity: 1;
                margin-top: 12px;
                padding-top: 12px;
                max-height: 50px;
            }
        }

        .spinner-btn {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin-btn 1s ease-in-out infinite;
            margin-right: 6px;
            vertical-align: middle;
        }

        .spinner-sm {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin-btn 1s ease-in-out infinite;
            margin-right: 4px;
            vertical-align: middle;
        }

        @keyframes spin-btn {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading {
            opacity: 0.7;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        .toast-popup {
            position: fixed;
            top: 30px;
            right: -100%;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast-popup.show {
            right: 30px;
        }

        .toast-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .toast-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .custom-pagination-wrap nav>div:first-child {
            display: none !important;
        }

        .custom-pagination-wrap nav>div:last-child {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }

        .custom-pagination-wrap nav p {
            font-size: 13px !important;
            color: var(--text-muted) !important;
            margin: 0 !important;
        }

        .custom-pagination-wrap nav p span {
            font-weight: 800 !important;
            color: var(--primary-color) !important;
        }

        .custom-pagination-wrap nav>div:last-child>div:last-child>span {
            display: inline-flex !important;
            border-radius: 8px !important;
            border: 1px solid var(--border-color) !important;
            background: #fff !important;
            overflow: hidden !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
        }

        .custom-pagination-wrap nav span>a,
        .custom-pagination-wrap nav span>span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 38px !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #475569 !important;
            text-decoration: none !important;
            background: #fff !important;
            border-right: 1px solid var(--border-color) !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            margin: 0 !important;
            border-top: none !important;
            border-bottom: none !important;
            border-left: none !important;
        }

        .custom-pagination-wrap nav span>*:last-child {
            border-right: none !important;
        }

        .custom-pagination-wrap nav span>a:hover {
            background: #f1f5f9 !important;
            color: var(--primary-color) !important;
        }

        .custom-pagination-wrap nav span[aria-current="page"]>span {
            background: var(--primary-color) !important;
            color: #fff !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }

        .custom-pagination-wrap nav span[aria-disabled="true"]>span {
            color: #cbd5e1 !important;
            background: #f8fafc !important;
            cursor: not-allowed !important;
        }

        .custom-pagination-wrap nav svg {
            width: 16px !important;
            height: 16px !important;
        }

        
    </style>
</head>

<body>

    @include('layouts.mastersidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Master Kategori & Aset</h1>
                <p>Pusat pengelolaan daftar nama kategori dan master awalan seri aset sistem.</p>
            </div>
        </header>


        @if(session('success'))
        <div id="toast-success" class="toast-popup toast-success show">
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div id="toast-error" class="toast-popup toast-error show">
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <!-- ========================================== -->
        <!-- BAGIAN 1: MANAJEMEN KATEGORI -->
        <!-- ========================================== -->
        <div class="layout-grid">
            <div class="card-box" style="align-self: start; border-top: 4px solid var(--primary-color);">
                <h2 class="section-title">Tambah Kategori Baru</h2>
                <form action="{{ route('kategori.store') }}" method="POST" id="form-kategori">
                    @csrf
                    <div class="form-group">
                        <label>Nama Kategori</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" placeholder="Contoh: ELEKTRONIK" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Kategori</label>
                        <input type="text" id="kode_kategori" name="kode_kategori" placeholder="Isi nama kategori terlebih dahulu..." required disabled style="background: #f1f5f9; cursor: not-allowed;">
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-kategori" style="width: 100%; margin-top: 10px;">
                        <span id="text-simpan-kategori">Simpan Kategori</span>
                    </button>
                </form>
            </div>

            <div class="card-box">
                <h2 class="section-title">Daftar Kategori Tersimpan</h2>
                <div class="category-chips" style="flex-direction: row; flex-wrap: wrap;">
                    @forelse($kategoris as $kat)
                    <div class="chip-wrapper" id="chip-wrapper-kat-{{ $kat->id }}" onclick="toggleChipActions(this)" style="width: auto;">
                        <div class="chip-content">
                            <span class="chip-name">{{ $kat->nama_kategori }}</span>
                            <span class="chip-code" style="background: var(--primary-color);">{{ $kat->kode_kategori }}</span>
                        </div>

                        <form action="{{ url('/masteradmin/kategori/'.$kat->id) }}" method="POST" class="chip-edit-form" id="form-edit-kat-{{ $kat->id }}" onsubmit="setLoadingUpdate(event, 'kat-{{ $kat->id }}')" onclick="event.stopPropagation()">
                            @csrf
                            @method('PUT')
                            <div class="edit-input-group">
                                <input type="text" id="input-nama-kat-{{ $kat->id }}" name="nama_kategori" value="{{ $kat->nama_kategori }}" required placeholder="Nama">
                                <input type="text" id="input-kode-kat-{{ $kat->id }}" name="kode_kategori" value="{{ $kat->kode_kategori }}" required placeholder="Kode">
                            </div>
                            <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 6px;">
                                <button type="button" class="btn-cancel-sm" onclick="cancelEdit('kat-{{ $kat->id }}')">Batal</button>
                                <button type="submit" class="btn-update-sm" id="btn-update-kat-{{ $kat->id }}">Update</button>
                            </div>
                        </form>

                        <div class="chip-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-edit-sm" onclick="enableInlineEdit('kat-{{ $kat->id }}')">Edit</button>
                            <form action="{{ url('/masteradmin/kategori/'.$kat->id) }}" method="POST" class="form-delete-kategori" data-log-aktivitas="Menghapus kategori aset: {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})" data-log-modul="Kategori Aset" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <p style="font-size: 13px; color: var(--text-muted);">Belum ada data kategori yang disimpan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BAGIAN 2: MANAJEMEN MASTER ASET -->
        <!-- ========================================== -->
        <div class="layout-grid">

            <!-- Kotak Kiri: Form Tambah Master Aset -->
            <div class="card-box" style="align-self: start; border-top: 4px solid var(--secondary-color);">
                <h2 class="section-title">Tambah Master Aset Baru</h2>
                <form action="{{ route('items.store') }}" method="POST" id="form-aset">
                    @csrf

                    <div class="form-group">
                        <label>Pilih Kategori <span style="color: var(--danger-color);">*</span></label>
                        @php
                        $itemModelClass = null;
                        $itemTableName = null;

                        if (isset($items) && $items->isNotEmpty() && is_object($items->first())) {
                            $itemModelClass = get_class($items->first());
                            if (method_exists($items->first(), 'getTable')) {
                                $itemTableName = $items->first()->getTable();
                            }
                        }

                        if (!$itemModelClass) {
                            $candidateClasses = [
                                \App\Models\Item::class,
                                \App\Models\MasterAset::class,
                                \App\Models\Aset::class,
                                \App\Models\MasterItem::class,
                                \App\Models\KategoriAset::class,
                            ];
                            foreach ($candidateClasses as $candidateClass) {
                                if (class_exists($candidateClass)) {
                                    $itemModelClass = $candidateClass;
                                    $itemTableName = (new $candidateClass)->getTable();
                                    break;
                                }
                            }
                        }

                        if (!$itemModelClass && function_exists('app_path')) {
                            foreach (glob(app_path('Models/*.php')) ?: [] as $modelFile) {
                                $modelBaseName = basename($modelFile, '.php');
                                if ($modelBaseName === 'Kategori') {
                                    continue;
                                }
                                $candidateClass = str_replace('Kategori', $modelBaseName, \App\Models\Kategori::class);
                                if (class_exists($candidateClass) && is_subclass_of($candidateClass, \Illuminate\Database\Eloquent\Model::class)) {
                                    try {
                                        $inst = new $candidateClass;
                                        if (\Illuminate\Support\Facades\Schema::hasColumns($inst->getTable(), ['kategori_id', 'nama_aset'])) {
                                            $itemModelClass = $candidateClass;
                                            $itemTableName = $inst->getTable();
                                            break;
                                        }
                                    } catch (\Throwable $e) {
                                    }
                                }
                            }
                        }

                        if (!$itemTableName) {
                            foreach (['items', 'master_asets', 'asets', 'master_items'] as $candidateTable) {
                                if (\Illuminate\Support\Facades\Schema::hasTable($candidateTable)) {
                                    $itemTableName = $candidateTable;
                                    break;
                                }
                            }
                        }

                        $dbMaxUrutan = [];
                        $dbUsedUrutan = [];
                        if ($itemTableName) {
                            $allItemRows = \Illuminate\Support\Facades\DB::table($itemTableName)
                                ->select('kategori_id', 'urutan')
                                ->whereNotNull('kategori_id')
                                ->get();

                            foreach ($allItemRows as $row) {
                                $kId = (string) $row->kategori_id;
                                $urutVal = (int) preg_replace('/[^0-9]/', '', (string) ($row->urutan ?? 0));
                                if ($urutVal <= 0) {
                                    $urutVal = 1;
                                }
                                if (!isset($dbMaxUrutan[$kId]) || $urutVal > $dbMaxUrutan[$kId]) {
                                    $dbMaxUrutan[$kId] = $urutVal;
                                }
                                $dbUsedUrutan[$kId][] = $urutVal;
                            }
                        }

                        $maxUrutanData = [];
                        $usedUrutanData = [];
                        foreach ($kategoris as $kat) {
                            $kId = (string) $kat->id;
                            $maxUrut = $dbMaxUrutan[$kId] ?? (int) $items->where('kategori_id', $kat->id)->max('urutan');
                            $usedList = isset($dbUsedUrutan[$kId]) ? array_values(array_unique($dbUsedUrutan[$kId])) : [];
                            sort($usedList);
                            $maxUrutanData[$kId] = $maxUrut;
                            $usedUrutanData[$kId] = $usedList;
                        }

                        // Dukungan pencarian berdasarkan kode penuh (misal: C.01), kode kategori saja (misal: C), maupun nama/seri/kategori
                        if (request()->filled('search') && $itemModelClass) {
                            $rawSearch = trim((string) request('search'));
                            $cleanSearchUpper = strtoupper(rtrim($rawSearch, '.'));

                            // Peta kode kategori -> daftar ID kategori
                            $kodeToKatIds = [];
                            foreach ($kategoris as $kat) {
                                $kodeBersih = strtoupper(rtrim(trim((string) $kat->kode_kategori), '.'));
                                if ($kodeBersih !== '') {
                                    $kodeToKatIds[$kodeBersih][] = $kat->id;
                                }
                            }

                            $searchQuery = $itemModelClass::query();

                            // 1. Jika formatnya KODE.URUTAN (contoh: C.01, c.01, C.1, B.40) atau KODE-URUTAN / KODE URUTAN
                            if (preg_match('/^([a-zA-Z0-9_-]+)[.\s-]+0*(\d+)$/', $rawSearch, $matches) && isset($kodeToKatIds[strtoupper($matches[1])])) {
                                $targetKatIds = $kodeToKatIds[strtoupper($matches[1])];
                                $targetUrut = (int) $matches[2];
                                $searchQuery->whereIn('kategori_id', $targetKatIds)
                                    ->where(function ($q) use ($targetUrut, $matches) {
                                        $q->whereRaw('CAST(urutan AS UNSIGNED) = ?', [$targetUrut])
                                          ->orWhere('urutan', $targetUrut)
                                          ->orWhere('urutan', $matches[2]);
                                    });
                            }
                            // 2. Jika hanya mengetik kode kategori saja (contoh: "C" atau "c" atau "C.") -> tampilkan khusus kategori itu saja
                            elseif (isset($kodeToKatIds[$cleanSearchUpper])) {
                                $targetKatIds = $kodeToKatIds[$cleanSearchUpper];
                                $searchQuery->whereIn('kategori_id', $targetKatIds)
                                    ->orderByRaw('CAST(urutan AS UNSIGNED) ASC');
                            }
                            // 3. Pencarian umum (nama aset, awalan serial, atau nama kategori)
                            else {
                                $matchedKatIdsByName = $kategoris->filter(function ($kat) use ($rawSearch) {
                                    return stripos((string) $kat->nama_kategori, $rawSearch) !== false
                                        || stripos((string) $kat->kode_kategori, $rawSearch) !== false;
                                })->pluck('id')->all();

                                $searchQuery->where(function ($q) use ($rawSearch, $matchedKatIdsByName) {
                                    $q->where('nama_aset', 'like', '%' . $rawSearch . '%')
                                      ->orWhere('awal_serial', 'like', '%' . $rawSearch . '%');
                                    if (!empty($matchedKatIdsByName)) {
                                        $q->orWhereIn('kategori_id', $matchedKatIdsByName);
                                    }
                                    if (ctype_digit($rawSearch)) {
                                        $q->orWhereRaw('CAST(urutan AS UNSIGNED) = ?', [(int) $rawSearch]);
                                    }
                                });
                            }

                            $perPage = method_exists($items, 'perPage') ? $items->perPage() : 10;
                            $pageName = method_exists($items, 'getPageName') ? $items->getPageName() : 'item_page';
                            $items = $searchQuery
                                ->orderBy('kategori_id', 'asc')
                                ->orderByRaw('CAST(urutan AS UNSIGNED) ASC')
                                ->paginate($perPage, ['*'], $pageName)
                                ->withQueryString();

                            $katById = $kategoris->keyBy('id');
                            foreach ($items as $it) {
                                if ($it->kategori_id && isset($katById[$it->kategori_id])) {
                                    $it->setRelation('kategori', $katById[$it->kategori_id]);
                                }
                            }
                        }
                        @endphp
                        <select name="kategori_id" id="input_kategori_aset" required onchange="kategoriBerubah()">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($kategoris as $kat)
                            @php
                                $kIdStr = (string) $kat->id;
                                $nextUrutOption = ($maxUrutanData[$kIdStr] ?? 0) + 1;
                                while (in_array($nextUrutOption, $usedUrutanData[$kIdStr] ?? [], true)) {
                                    $nextUrutOption++;
                                }
                            @endphp
                            <option value="{{ $kat->id }}" data-kode="{{ $kat->kode_kategori }}" data-next-urutan="{{ $nextUrutOption }}">{{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Urutan Master (ke-) <span style="color: var(--danger-color);">*</span></label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="input_kode_kategori_preview" readonly style="width: 70px; background: #f1f5f9; text-align: center; font-weight: bold; color: var(--primary-color);" placeholder="Kode">
                            <input type="number" name="urutan" id="input_urutan_aset" value="1" min="1" required style="flex: 1;" oninput="updatePreviewMaster()">
                        </div>
                        <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px;">Kode Master: <strong id="preview_kode_master" style="color: var(--primary-color);">[PILIH KATEGORI]</strong></small>
                    </div>

                    <div class="form-group">
                        <label>Nama Aset <span style="color: var(--danger-color);">*</span></label>
                        <input type="text" id="input_nama_aset" name="nama_aset" required placeholder="Contoh: MEJA BELAJAR">
                    </div>

                    <div class="form-group">
                        <label>Awalan Serial Otomatis <span style="color: var(--text-muted); font-weight: normal; font-size: 11px;">(Opsional)</span></label>
                        <input type="text" id="input_awal_serial" name="awal_serial" placeholder="Isi nama aset terlebih dahulu..." disabled oninput="this.value = this.value.toUpperCase(); updatePreviewSerial();" style="background: #f1f5f9; cursor: not-allowed;">
                        <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px;">Tersimpan: <strong id="preview-serial" style="color: var(--primary-color);">[TIDAK ADA SERI]</strong></small>
                    </div>

                    <div class="form-group">
                        <label>Warna Label Seri</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="color" name="warna_seri" id="input_warna_seri" value="#10b981" style="width: 45px; height: 35px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; padding: 2px;">
                            <span style="font-size: 11px; color: var(--text-muted);">Pilih warna identitas seri ini.</span>
                        </div>

                        <!-- DAFTAR WARNA CEPAT (CURRENT & RECENT COLORS) -->
                        <div style="display: flex; gap: 6px; margin-top: 8px; align-items: center; flex-wrap: wrap;">
                            <span style="font-size: 11px; color: var(--text-muted); margin-right: 4px;">Pilihan Cepat:</span>
                            @foreach($items->unique('warna_seri') as $existingItem)
                            @if(!empty($existingItem->warna_seri))
                            <div onclick="document.getElementById('input_warna_seri').value='{{ $existingItem->warna_seri }}'"
                                {!! 'style="width: 22px; height: 22px; border-radius: 50%; background: ' . $existingItem->warna_seri . '; cursor: pointer; border: 2px solid #cbd5e1; transition: transform 0.1s;"' !!}
                                title="Gunakan warna ini: {{ $existingItem->warna_seri }}"
                                onmouseover="this.style.transform='scale(1.15)'"
                                onmouseout="this.style.transform='scale(1)'">
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn" style="background: var(--secondary-color); color: #fff; border: none; width: 100%; margin-top: 10px;" id="btn-simpan-aset">
                        <span id="text-simpan-aset">Simpan Master Aset</span>
                    </button>
                </form>
            </div>

            <!-- Kotak Kanan: Daftar Master Aset -->
            <div class="card-box">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px; margin-bottom: 15px;">
                    <h2 class="section-title" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">Daftar Master Aset Tersimpan</h2>
                </div>

                <!-- SEARCH BAR KHUSUS MASTER ASET (Dimasukkan ke sini) -->
                <form action="{{ url()->current() }}" method="GET" style="margin-bottom: 15px; display: flex; gap: 8px; align-items: center;">
                    <div style="position: relative; flex: 1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8;">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode (C.01 / C), nama aset, seri, atau kategori..." autocomplete="off" style="width: 100%; padding: 8px 8px 8px 34px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; outline: none; background: #f8fafc;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px;">Cari</button>
                    @if(request('search'))
                    <a href="{{ url()->current() }}" class="btn" style="background: #fee2e2; color: #ef4444; border: none; text-decoration: none; padding: 8px 12px; font-size: 12px;">Reset</a>
                    @endif
                </form>
                <div class="category-chips">
                    @forelse($items as $item)
                    <div class="chip-wrapper" id="chip-wrapper-aset-{{ $item->id }}" onclick="toggleChipActions(this)">

                        <!-- Mode View -->
                        <div class="chip-content">
                            <span class="chip-name">
                                <strong style="color: var(--primary-color); border-right: 2px solid #cbd5e1; padding-right: 10px; margin-right: 10px;">
                                    @if($item->kategori_id && $item->kategori)
                                    {{ rtrim($item->kategori->kode_kategori, '.') }}.{{ str_pad($item->urutan ?? 1, 2, '0', STR_PAD_LEFT) }}
                                    @else
                                    -
                                    @endif
                                </strong>
                                {{ $item->nama_aset }}
                            </span>
                            <span class="chip-code" id="chip-code-{{ $item->id }}" data-color="{{ $item->warna_seri ?? '#10b981' }}">
                                {{ $item->awal_serial }}
                            </span>
                        </div>

                        <script>
                            (function() {
                                let codeEl = document.getElementById('chip-code-{{ $item->id }}');
                                if (codeEl) {
                                    codeEl.style.setProperty('background', codeEl.getAttribute('data-color'), 'important');
                                }
                            })();
                        </script>

                        <!-- Mode Edit Inline -->
                        <form action="{{ route('items.update', $item->id) }}" method="POST" class="chip-edit-form" id="form-edit-aset-{{ $item->id }}" onsubmit="setLoadingUpdate(event, 'aset-{{ $item->id }}')" onclick="event.stopPropagation()">
                            @csrf
                            @method('PUT')

                            <div class="edit-input-group" style="margin-bottom: 4px;">
                                <select name="kategori_id" required style="flex: 2; cursor: pointer;">
                                    <option value="">-- Kategori --</option>
                                    @foreach($kategoris as $kat)
                                    <option value="{{ $kat->id }}" {{ ($item->kategori_id == $kat->id) ? 'selected' : '' }}>
                                        {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})
                                    </option>
                                    @endforeach
                                </select>
                                <input type="number" name="urutan" value="{{ $item->urutan ?? 1 }}" min="1" required style="flex: 1; text-align: center;" placeholder="Urut">
                            </div>

                            <div class="edit-input-group">
                                <input type="text" id="input-nama-aset-{{ $item->id }}" name="nama_aset" value="{{ $item->nama_aset }}" required style="flex: 2;">
                                @php
                                $cleanSerial = explode('-', $item->awal_serial)[0] ?? '';
                                $currentYear = date('Y');
                                @endphp
                                <input type="text" id="input-kode-aset-{{ $item->id }}" name="awal_serial" value="{{ $cleanSerial }}" style="flex: 1; text-transform: uppercase;">
                                <span style="background: #e2e8f0; color: #1e293b; padding: 6px 8px; border-radius: 6px; font-size: 11px; font-weight: 800;">-{{ $currentYear }}</span>
                            </div>

                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 6px;">
                                <input type="color" name="warna_seri" value="{{ $item->warna_seri ?? '#10b981' }}" style="width: 25px; height: 25px; border: none; padding: 0; cursor: pointer;">
                                <div style="flex-grow: 1; display: flex; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn-cancel-sm" onclick="cancelEdit('aset-{{ $item->id }}')">Batal</button>
                                    <button type="submit" class="btn-update-sm" id="btn-update-aset-{{ $item->id }}">Update</button>
                                </div>
                            </div>
                        </form>

                        <div class="chip-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-edit-sm" onclick="enableInlineEdit('aset-{{ $item->id }}')">Edit</button>
                            <form action="{{ url('/masteradmin/items/'.$item->id) }}" method="POST" class="form-delete-kategori" data-log-aktivitas="Menghapus master aset: {{ ($item->kategori_id && $item->kategori) ? rtrim($item->kategori->kode_kategori, '.') . '.' . str_pad($item->urutan ?? 1, 2, '0', STR_PAD_LEFT) . ' - ' : '' }}{{ $item->nama_aset }}" data-log-modul="Master Aset" style="display:inline;" onclick="event.stopPropagation()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <p style="font-size: 13px; color: var(--text-muted);">Belum ada master aset yang disimpan.</p>
                    @endforelse
                </div>

                @if($items->hasPages())
                <div class="custom-pagination-wrap" style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border-color);">
                    {{ $items->links() }}
                </div>
                @endif
            </div>

        </div>

        <div class="layout-grid">
            <div class="card-box" style="align-self: start; border-top: 4px solid var(--warning-color);">
                <h2 class="section-title">Tambah Status Aset Baru</h2>
                <form action="{{ route('status-aset.store') ?? '#' }}" method="POST" id="form-status-aset">
                    @csrf
                    <div class="form-group">
                        <label>Nama Status</label>
                        <input type="text" name="nama_status" placeholder="Contoh: BERJALAN / TIDAK BERJALAN" required>
                    </div>
                    <div class="form-group">
                        <label>Warna Badge Status</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="color" name="warna_status" value="#10b981" style="width: 45px; height: 35px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer; padding: 2px;">
                            <span style="font-size: 11px; color: var(--text-muted);">Pilih warna identitas status ini.</span>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="background: var(--warning-color); color: #fff; border: none; width: 100%; margin-top: 10px;" id="btn-simpan-status">
                        <span>Simpan Status Aset</span>
                    </button>
                </form>
            </div>

            <div class="card-box">
                <h2 class="section-title">Daftar Status Aset Tersedia</h2>
                <div class="category-chips" style="flex-direction: row; flex-wrap: wrap;">
                    @forelse($statuses ?? [] as $st)
                    <div class="chip-wrapper" id="chip-wrapper-status-{{ $st->id }}" onclick="toggleChipActions(this)" style="width: auto;">
                        <div class="chip-content">
                            <span class="chip-name" style="gap: 8px; display: flex; align-items: center;">
                                <span style="width: 12px; height: 12px; border-radius: 50%; background: {{ $st->warna_status ?? '#10b981' }}; display: inline-block;"></span>
                                {{ $st->nama_status }}
                            </span>
                        </div>

                        <!-- Form Edit Inline Status -->
                        <form action="{{ url('/masteradmin/status-aset/'.$st->id) }}" method="POST" class="chip-edit-form" id="form-edit-status-{{ $st->id }}" onsubmit="setLoadingUpdate(event, 'status-{{ $st->id }}')" onclick="event.stopPropagation()">
                            @csrf
                            @method('PUT')
                            <div class="edit-input-group">
                                <input type="text" name="nama_status" value="{{ $st->nama_status }}" required>
                                <input type="color" name="warna_status" value="{{ $st->warna_status ?? '#10b981' }}" style="width: 35px; height: 32px; border: none; cursor: pointer;">
                            </div>
                            <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 6px;">
                                <button type="button" class="btn-cancel-sm" onclick="cancelEdit('status-{{ $st->id }}')">Batal</button>
                                <button type="submit" class="btn-update-sm" id="btn-update-status-{{ $st->id }}">Update</button>
                            </div>
                        </form>

                        <div class="chip-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-edit-sm" onclick="enableInlineEdit('status-{{ $st->id }}')">Edit</button>
                            <form action="{{ url('/masteradmin/status-aset/'.$st->id) }}" method="POST" class="form-delete-kategori" data-log-aktivitas="Menghapus status aset: {{ $st->nama_status }}" data-log-modul="Status Aset" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <p style="font-size: 13px; color: var(--text-muted);">Belum ada data status aset tersimpan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </main>

    <!-- Script Fungsional -->
    <script>
        const maxUrutanPerKategori = @json($maxUrutanData);
        const usedUrutanPerKategori = @json($usedUrutanData);

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelectorAll('.toast-popup').forEach(el => el.classList.remove('show'));
            }, 2500);
            const selectKat = document.getElementById('input_kategori_aset');
            if (selectKat && selectKat.value !== "") {
                kategoriBerubah();
            } else {
                updatePreviewMaster();
            }
        });

        function showCustomToast(message, type) {
            let toast = document.createElement('div');
            toast.className = `toast-popup toast-${type === 'success' ? 'success' : 'error'} show`;
            toast.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 2500);
        }

        // 2. Fungsi saat dropdown kategori dipilih
        function kategoriBerubah() {
            const select = document.getElementById('input_kategori_aset');
            const selectedOption = select.options[select.selectedIndex];
            const katId = String(select.value || '');
            const urutInput = document.getElementById('input_urutan_aset');

            if (katId !== '') {
                const attrNext = selectedOption ? parseInt(selectedOption.getAttribute('data-next-urutan'), 10) : NaN;
                let nilaiMaksimal = maxUrutanPerKategori[katId] !== undefined ? parseInt(maxUrutanPerKategori[katId], 10) : 0;
                if (isNaN(nilaiMaksimal)) nilaiMaksimal = 0;

                let nextUrut = !isNaN(attrNext) && attrNext > 0 ? attrNext : (nilaiMaksimal + 1);
                if ((nilaiMaksimal + 1) > nextUrut) {
                    nextUrut = nilaiMaksimal + 1;
                }

                const usedList = Array.isArray(usedUrutanPerKategori[katId]) ? usedUrutanPerKategori[katId].map(Number) : [];
                while (usedList.includes(nextUrut)) {
                    nextUrut++;
                }

                urutInput.value = nextUrut;
            } else {
                urutInput.value = 1;
            }

            updatePreviewMaster();
        }

        // 3. Fungsi untuk memperbarui teks kode (C.01, C.02, C.03, dst)
        function updatePreviewMaster() {
            const select = document.getElementById('input_kategori_aset');
            const katId = String(select.value || '');
            let kodeKat = select.options[select.selectedIndex]?.getAttribute('data-kode') || '';
            const urutInputVal = parseInt(document.getElementById('input_urutan_aset').value, 10) || 1;

            const previewInput = document.getElementById('input_kode_kategori_preview');
            const previewText = document.getElementById('preview_kode_master');

            if (kodeKat === '') {
                previewInput.value = '';
                previewText.innerText = '[PILIH KATEGORI]';
                previewText.style.color = 'var(--primary-color)';
            } else {
                // Bersihkan titik di akhir kode jika ada (misal "C." menjadi "C")
                kodeKat = kodeKat.replace(/\.$/, '').trim().toUpperCase();

                previewInput.value = kodeKat;
                const formatUrut = String(urutInputVal).padStart(2, '0');
                const usedList = Array.isArray(usedUrutanPerKategori[katId]) ? usedUrutanPerKategori[katId].map(Number) : [];

                if (usedList.includes(urutInputVal)) {
                    previewText.innerText = `${kodeKat}.${formatUrut} (Sudah ada!)`;
                    previewText.style.color = 'var(--danger-color)';
                } else {
                    previewText.innerText = `${kodeKat}.${formatUrut}`;
                    previewText.style.color = 'var(--primary-color)';
                }
            }
        }

        // Interaksi Form Kategori (Buka Kode)
        const inputNamaKat = document.getElementById('nama_kategori');
        const inputKodeKat = document.getElementById('kode_kategori');
        if (inputNamaKat && inputKodeKat) {
            inputNamaKat.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    inputKodeKat.removeAttribute('disabled');
                    inputKodeKat.style.background = '#fff';
                    inputKodeKat.style.cursor = 'text';
                    inputKodeKat.placeholder = "Contoh: ELK-01";
                } else {
                    inputKodeKat.setAttribute('disabled', 'true');
                    inputKodeKat.style.background = '#f1f5f9';
                    inputKodeKat.style.cursor = 'not-allowed';
                    inputKodeKat.placeholder = "Isi nama kategori terlebih dahulu...";
                    inputKodeKat.value = '';
                }
            });
        }

        // Interaksi Form Aset (Buka Kode Serial)
        const inputNamaAset = document.getElementById('input_nama_aset');
        const inputAwalSerial = document.getElementById('input_awal_serial');
        if (inputNamaAset && inputAwalSerial) {
            inputNamaAset.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    inputAwalSerial.removeAttribute('disabled');
                    inputAwalSerial.style.background = '#fff';
                    inputAwalSerial.style.cursor = 'text';
                    inputAwalSerial.placeholder = "Contoh: MJB";
                } else {
                    inputAwalSerial.setAttribute('disabled', 'true');
                    inputAwalSerial.style.background = '#f1f5f9';
                    inputAwalSerial.style.cursor = 'not-allowed';
                    inputAwalSerial.placeholder = "Isi nama aset terlebih dahulu...";
                    inputAwalSerial.value = '';
                    updatePreviewSerial();
                }
            });
        }

        function updatePreviewSerial() {
            const inputVal = document.getElementById('input_awal_serial').value.trim();
            const currentYear = new Date().getFullYear();
            const previewSerialEl = document.getElementById('preview-serial');

            if (inputVal === '') {
                previewSerialEl.innerText = '[TIDAK ADA SERI]';
            } else {
                previewSerialEl.innerText = `${inputVal}-${currentYear}-`;
            }
        }

        function toggleChipActions(element) {
            document.querySelectorAll('.chip-wrapper').forEach(el => {
                if (el !== element) el.classList.remove('active');
            });
            element.classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.chip-wrapper')) {
                document.querySelectorAll('.chip-wrapper').forEach(el => el.classList.remove('active'));
            }
        });

        function enableInlineEdit(id) {
            document.querySelectorAll('.chip-wrapper').forEach(el => el.classList.remove('is-editing'));
            document.getElementById('chip-wrapper-' + id).classList.add('is-editing');
        }

        function cancelEdit(id) {
            const wrapper = document.getElementById('chip-wrapper-' + id);
            if (!wrapper) return;
            wrapper.classList.remove('is-editing', 'active');
        }

        function setLoadingUpdate(event, id) {
            const btnUpdate = document.getElementById('btn-update-' + id);
            if (btnUpdate.disabled) {
                event.preventDefault();
                return;
            }
            document.body.classList.add('is-updating-global');
            btnUpdate.classList.add('btn-loading');
            btnUpdate.innerHTML = '<span class="spinner-sm"></span>...';
        }

        function resetBtn(btn, originalText) {
            if (btn) {
                btn.classList.remove('btn-loading');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function catatRiwayatJS(aktivitas, modul, status = 'Berhasil') {
            if (!aktivitas) return Promise.resolve();
            const params = new URLSearchParams({
                _log_riwayat: '1',
                _log_aktivitas: aktivitas,
                _log_modul: modul || 'Kategori & Aset',
                _log_status: status || 'Berhasil'
            });
            return fetch(`{{ url()->current() }}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).catch(() => {});
        }

        document.getElementById('form-kategori').addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            let namaKat = (formData.get('nama_kategori') || '').toString().trim();
            let kodeKat = (formData.get('kode_kategori') || '').toString().trim();

            let btnSubmit = document.getElementById('btn-simpan-kategori');
            btnSubmit.classList.add('btn-loading');
            btnSubmit.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(res => res.json().then(data => ({
                    status: res.status,
                    body: data
                })))
                .then(async res => {
                    if (res.status === 200 && res.body.success) {
                        await catatRiwayatJS(
                            `Menambah kategori aset baru: ${namaKat} (${kodeKat})`,
                            'Kategori Aset',
                            'Berhasil'
                        );
                        showCustomToast(res.body.message, 'success');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        showCustomToast('Gagal menyimpan kategori.', 'error');
                        resetBtn(btnSubmit, 'Simpan Kategori');
                    }
                }).catch(err => {
                    showCustomToast('Terjadi kesalahan.', 'error');
                    resetBtn(btnSubmit, 'Simpan Kategori');
                });
        });

        document.getElementById('form-aset').addEventListener('submit', function(e) {
            e.preventDefault();
            const selectKat = document.getElementById('input_kategori_aset');
            const katId = String(selectKat.value || '');
            const urutVal = parseInt(document.getElementById('input_urutan_aset').value, 10) || 1;
            const usedList = Array.isArray(usedUrutanPerKategori[katId]) ? usedUrutanPerKategori[katId].map(Number) : [];
            if (usedList.includes(urutVal)) {
                showCustomToast('Nomor urut tersebut sudah digunakan pada kategori ini!', 'error');
                return;
            }
            let formData = new FormData(this);
            let namaAset = (formData.get('nama_aset') || '').toString().trim();
            let kodeMasterPreview = (document.getElementById('preview_kode_master')?.innerText || '').trim();

            let inputSerial = formData.get('awal_serial');
            if (inputSerial && inputSerial.trim() !== '') {
                let currentYear = new Date().getFullYear();
                if (!inputSerial.toUpperCase().includes(currentYear)) {
                    formData.set('awal_serial', `${inputSerial.trim().toUpperCase()}-${currentYear}`);
                }
            } else {
                formData.set('awal_serial', '');
            }

            let btnSubmit = document.getElementById('btn-simpan-aset');
            btnSubmit.classList.add('btn-loading');
            btnSubmit.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(res => res.json().then(data => ({
                    status: res.status,
                    body: data
                })))
                .then(async res => {
                    // JIKA BERHASIL
                    if (res.status === 200 && res.body.success) {
                        await catatRiwayatJS(
                            `Menambah master aset baru: ${kodeMasterPreview} - ${namaAset}`,
                            'Master Aset',
                            'Berhasil'
                        );
                        showCustomToast(res.body.message, 'success');
                        setTimeout(() => location.reload(), 600);
                    }
                    // JIKA KENA VALIDASI LARAVEL (Error 422)
                    else if (res.status === 422) {
                        let errorMsg = 'Data tidak valid.';
                        if (res.body.errors) {
                            let firstErrorKey = Object.keys(res.body.errors)[0];
                            errorMsg = res.body.errors[firstErrorKey][0];
                        }
                        showCustomToast(errorMsg, 'error');
                        resetBtn(btnSubmit, 'Simpan Master Aset');
                    }
                    // JIKA ERROR LAINNYA
                    else {
                        showCustomToast(res.body.message || 'Gagal menyimpan aset.', 'error');
                        resetBtn(btnSubmit, 'Simpan Master Aset');
                    }
                }).catch(err => {
                    showCustomToast('Terjadi kesalahan koneksi.', 'error');
                    resetBtn(btnSubmit, 'Simpan Master Aset');
                });
        });

        const formStatusAset = document.getElementById('form-status-aset');
        if (formStatusAset) {
            formStatusAset.addEventListener('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let namaStatus = (formData.get('nama_status') || '').toString().trim();
                let btnSubmit = document.getElementById('btn-simpan-status');
                if (btnSubmit) {
                    btnSubmit.classList.add('btn-loading');
                    btnSubmit.innerHTML = '<span class="spinner-sm"></span> Menyimpan...';
                }

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(async res => {
                        let isSuccess = res.ok;
                        let msg = 'Status aset baru berhasil ditambahkan!';
                        try {
                            let data = await res.clone().json();
                            if (data && data.message) msg = data.message;
                            if (data && data.success === false) isSuccess = false;
                        } catch (err) {
                            // Jika controller mereturn redirect HTML biasa, res.ok tetap true
                        }

                        if (isSuccess) {
                            await catatRiwayatJS(
                                `Menambah status aset baru: ${namaStatus}`,
                                'Status Aset',
                                'Berhasil'
                            );
                            showCustomToast(msg, 'success');
                            setTimeout(() => location.reload(), 600);
                        } else {
                            showCustomToast('Gagal menyimpan status aset.', 'error');
                            resetBtn(btnSubmit, '<span>Simpan Status Aset</span>');
                        }
                    }).catch(err => {
                        showCustomToast('Terjadi kesalahan koneksi.', 'error');
                        resetBtn(btnSubmit, '<span>Simpan Status Aset</span>');
                    });
            });
        }

        document.querySelectorAll('.chip-edit-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let fullId = this.id.replace('form-edit-', '');
                let btnUpdate = document.getElementById('btn-update-' + fullId);
                let formData = new FormData(this);

                let logAktivitas = '';
                let logModul = 'Kategori & Aset';
                if (fullId.startsWith('kat-')) {
                    logAktivitas = `Memperbarui kategori aset: ${formData.get('nama_kategori') || ''} (${formData.get('kode_kategori') || ''})`;
                    logModul = 'Kategori Aset';
                } else if (fullId.startsWith('aset-')) {
                    logAktivitas = `Memperbarui master aset: ${formData.get('nama_aset') || ''}`;
                    logModul = 'Master Aset';
                } else if (fullId.startsWith('status-')) {
                    logAktivitas = `Memperbarui status aset: ${formData.get('nama_status') || ''}`;
                    logModul = 'Status Aset';
                }

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json().then(data => ({
                        status: res.status,
                        body: data
                    })))
                    .then(async res => {
                        document.body.classList.remove('is-updating-global');
                        if (res.status === 200 && res.body.success) {
                            await catatRiwayatJS(logAktivitas, logModul, 'Informasi');
                            showCustomToast(res.body.message, 'success');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            showCustomToast('Gagal update.', 'error');
                            resetBtn(btnUpdate, 'Update');
                        }
                    }).catch(err => {
                        document.body.classList.remove('is-updating-global');
                        resetBtn(btnUpdate, 'Update');
                    });
            });
        });

        document.querySelectorAll('.form-delete-kategori').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Yakin ingin menghapus data ini?')) return;
                let btnDelete = this.querySelector('.btn-delete-sm');
                let chipWrapper = this.closest('.chip-wrapper');
                let logAktivitas = this.getAttribute('data-log-aktivitas') || 'Menghapus data pada halaman Kategori & Aset';
                let logModul = this.getAttribute('data-log-modul') || 'Kategori & Aset';

                document.body.classList.add('is-updating-global');
                if (btnDelete) btnDelete.innerHTML = '<span class="spinner-sm"></span>';

                fetch(this.action, {
                        method: 'POST',
                        body: new FormData(this),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(async res => {
                        let isSuccess = res.ok;
                        let msg = 'Data berhasil dihapus!';
                        try {
                            let data = await res.clone().json();
                            if (data && data.message) msg = data.message;
                            if (data && data.success === false) isSuccess = false;
                        } catch (err) {
                        }
                        return { isSuccess, msg };
                    })
                    .then(async res => {
                        document.body.classList.remove('is-updating-global');
                        if (res.isSuccess) {
                            await catatRiwayatJS(logAktivitas, logModul, 'Peringatan');
                            showCustomToast(res.msg, 'success');
                            chipWrapper.style.transform = 'scale(0.8)';
                            chipWrapper.style.opacity = '0';
                            setTimeout(() => chipWrapper.remove(), 300);
                        } else {
                            showCustomToast('Gagal menghapus.', 'error');
                            if (btnDelete) btnDelete.innerHTML = 'Hapus';
                        }
                    }).catch(err => {
                        document.body.classList.remove('is-updating-global');
                        if (btnDelete) btnDelete.innerHTML = 'Hapus';
                    });
            });
        });
    </script>
</body>

</html>