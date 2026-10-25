<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Aset - Sistem Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --text-color: #000;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.15);
            --sidebar-width: 270px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); color: var(--text-color); display: flex; min-height: 100vh; overflow-x: hidden; }

        main { margin-left: var(--sidebar-width); flex-grow: 1; padding: 25px; display: flex; flex-direction: column; gap: 25px; width: calc(100% - var(--sidebar-width)); }

        .main-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; }
        .header-title h1 { font-size: 26px; font-weight: 700; color: var(--primary-color); }
        .header-title p { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }

        .btn { padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; }
        .btn-primary { background: var(--primary-color); color: #fff; border: none; padding: 10px 20px; }
        .btn-secondary { background: #64748b; color: #fff; border: none; padding: 10px 20px; }
        .btn-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; }
        .btn-danger:hover { background: var(--danger-color); color: #fff; }
        .btn:hover { opacity: 0.9; }

        .card-box { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid var(--border-color); margin-bottom: 20px; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #1e293b; }
        .form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #fff; transition: 0.2s; color: var(--text-color); }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1); }
        
        .form-group input:disabled, .form-group select:disabled { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; opacity: 1; -webkit-text-fill-color: #334155; }

        /* MODAL DETAIL & EDIT */
        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center; z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s ease-in-out; backdrop-filter: blur(2px); }
        .modal-backdrop.show { opacity: 1; visibility: visible; }
        .modal-container { background: #f8fafc; width: 95%; max-width: 950px; max-height: 92vh; border-radius: 16px; display: flex; flex-direction: column; transform: scale(0.95); transition: 0.3s; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25); overflow: hidden; }
        .modal-backdrop.show .modal-container { transform: scale(1); }
        .modal-header { padding: 18px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .modal-header h3 { font-size: 18px; color: var(--primary-color); font-weight: 700; }
        .btn-close-modal { background: transparent; border: none; font-size: 24px; font-weight: bold; cursor: pointer; color: var(--text-muted); }
        .btn-close-modal:hover { color: var(--danger-color); }
        .modal-body { padding: 25px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 20px; }
        .modal-footer { padding: 15px 25px; background: #fff; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }

        /* SIDEBAR TAMBAH ASET */
        .sidebar-form-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; opacity: 0; visibility: hidden; transition: all 0.3s ease; backdrop-filter: blur(2px); }
        .sidebar-form-backdrop.show { opacity: 1; visibility: visible; }
        .sidebar-form-panel { position: fixed; top: 0; right: -550px; width: 100%; max-width: 500px; height: 100vh; background: #f8fafc; display: flex; flex-direction: column; transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1); z-index: 10001; box-shadow: -5px 0 30px rgba(0,0,0,0.15); }
        .sidebar-form-backdrop.show .sidebar-form-panel { right: 0; }
        .sidebar-header { padding: 20px 25px; background: #fff; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header h3 { font-size: 18px; color: var(--primary-color); font-weight: 700; }
        .sidebar-body { padding: 20px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 20px; }
        .sidebar-footer { padding: 15px 25px; background: #fff; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px; }

        /* KOTAK LANGKAH (STEP SECTION) */
        .step-box { background: #fff; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .step-header { display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; margin-bottom: 15px; }
        .step-number { background: var(--primary-color); color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
        .step-title { font-size: 14px; font-weight: 700; color: #1e293b; }
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 12px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        /* BANNER PREVIEW KODE */
        .preview-banner { background: linear-gradient(135deg, #1e3a8a, #1a4f91); color: #fff; padding: 15px 20px; border-radius: 12px; display: grid; grid-template-columns: 1fr; gap: 15px; border: 2px solid #3b82f6; }
        .preview-banner label { color: #93c5fd !important; font-size: 11px !important; text-transform: uppercase; letter-spacing: 0.5px; }
        .preview-banner input { background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.4) !important; color: #fff !important; font-size: 16px !important; font-weight: 800 !important; }

        /* TABEL */
        .table-container { width: 100%; overflow-x: auto; max-height: 65vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 10px; position: relative; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 3300px; }
        .data-table th { 
            position: sticky; top: 0; z-index: 10; 
            font-size: 12px; color: var(--text-muted); text-align: left; padding: 12px 15px; 
            background: #f8fafc; /* Wajib ada background agar data tidak tembus */
            white-space: nowrap; border-bottom: 1px solid var(--border-color); 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
        }
        .data-table th.aksi-col { position: sticky; top: 0; right: 0; z-index: 20; background: #f8fafc; }
        .data-table td { font-size: 13px; padding: 12px 15px; border-bottom: 1px solid rgba(0, 0, 0, 0.05); white-space: nowrap; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .toast-popup { position: fixed; top: 30px; right: -100%; padding: 15px 20px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); font-size: 14px; font-weight: 600; z-index: 99999; display: flex; align-items: center; gap: 12px; transition: right 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        .toast-popup.show { right: 30px; }
        .toast-success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .toast-error { background-color: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }

        .click-tooltip-container { position: relative; display: inline-flex; cursor: pointer; outline: none; }
        .click-tooltip-text { visibility: hidden; opacity: 0; width: max-content; background-color: #1e293b; color: #fff; text-align: center; border-radius: 6px; padding: 6px 12px; position: absolute; z-index: 999; bottom: 135%; left: 50%; transform: translateX(-50%); transition: all 0.2s ease-in-out; font-size: 11px; font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.15); pointer-events: none; }
        .click-tooltip-text::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #1e293b transparent transparent transparent; }
        .click-tooltip-container:focus .click-tooltip-text { visibility: visible; opacity: 1; bottom: 150%; }
    
        /* ===== PENCARIAN ASET ===== */
        .aset-toolbar { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .aset-search-wrap { position: relative; flex: 1 1 320px; max-width: 480px; }
        .aset-search-wrap input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; outline: none; font-family: inherit; }
        .aset-search-wrap input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1); }
        .aset-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
        .aset-suggest { display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #fff; border: 1px solid var(--border-color); border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12); max-height: 340px; overflow-y: auto; z-index: 50; }
        .aset-suggest.show { display: block; }
        .sg-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; font-size: 13px; border-bottom: 1px solid rgba(0, 0, 0, 0.04); }
        .sg-item:last-child { border-bottom: none; }
        .sg-item:hover, .sg-item.aktif { background: #eef4fb; }
        .sg-kolom { font-size: 11px; font-weight: 700; color: var(--primary-color); background: rgba(26, 79, 145, 0.08); padding: 2px 8px; border-radius: 6px; white-space: nowrap; }
        .sg-teks { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sg-teks mark { background: #fde68a; color: inherit; border-radius: 2px; padding: 0 1px; }
        .sg-jumlah { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
        .sg-kosong { padding: 14px; font-size: 13px; color: var(--text-muted); text-align: center; }
        .aset-chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 12px; }
        .aset-chips:empty { display: none; }
        .aset-chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(26, 79, 145, 0.08); color: var(--primary-color); border: 1px solid rgba(26, 79, 145, 0.25); padding: 4px 6px 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .aset-chip button { border: none; background: transparent; color: inherit; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 5px; border-radius: 50%; }
        .aset-chip button:hover { background: rgba(26, 79, 145, 0.15); }
        .aset-chip-hapus { font-size: 12px; color: #ef4444; background: none; border: none; cursor: pointer; font-weight: 600; }
        .aset-info { font-size: 12px; color: var(--text-muted); margin-top: 10px; }
        .hidden.sm\:flex-1 { display: flex !important; justify-content: space-between; align-items: center; margin-top: 20px; }
        .hidden.sm\:flex-1 p { font-size: 13px; color: var(--text-muted); margin: 0; }
        .hidden.sm\:flex-1 nav { display: inline-flex; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .hidden.sm\:flex-1 nav a, .hidden.sm\:flex-1 nav span { padding: 8px 14px; border: 1px solid var(--border-color); background: #fff; color: var(--text-color); text-decoration: none; font-size: 13px; margin-left: -1px; display: inline-flex; align-items: center; justify-content: center; }
        .hidden.sm\:flex-1 nav a:hover { background: #f8fafc; color: var(--primary-color); }
        .hidden.sm\:flex-1 nav span[aria-current="page"] > span { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .hidden.sm\:flex-1 svg { width: 16px; height: 16px; }
        .flex.justify-between.flex-1 { display: none; }
        .custom-pagination-wrap nav > div:first-child {
            display: none !important;
        }

        /* 2. Tampilkan baris desktop utama (Teks Info & Tombol Angka) */
        .custom-pagination-wrap nav > div:last-child {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            width: 100%;
        }

        /* 3. Styling Teks "Showing 1 to 100 of X results" */
        .custom-pagination-wrap nav p {
            font-size: 13px !important;
            color: var(--text-muted) !important;
            margin: 0 !important;
        }
        .custom-pagination-wrap nav p span {
            font-weight: 800 !important;
            color: var(--primary-color) !important;
        }

        /* 4. Kotak pembungkus deretan angka */
        .custom-pagination-wrap nav > div:last-child > div:last-child > span {
            display: inline-flex !important;
            border-radius: 8px !important;
            border: 1px solid var(--border-color) !important;
            background: #fff !important;
            overflow: hidden !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
        }

        /* 5. Bentuk tiap kotak angka dan panah */
        .custom-pagination-wrap nav span > a,
        .custom-pagination-wrap nav span > span {
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
        
        .custom-pagination-wrap nav span > *:last-child {
            border-right: none !important;
        }

        /* 6. Efek Hover */
        .custom-pagination-wrap nav span > a:hover {
            background: #f1f5f9 !important;
            color: var(--primary-color) !important;
        }

        /* 7. Halaman Aktif saat ini (Berwarna Biru) */
        .custom-pagination-wrap nav span[aria-current="page"] > span {
            background: var(--primary-color) !important;
            color: #fff !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* 8. Tombol mati (Disabled - contoh panah Prev di halaman 1) */
        .custom-pagination-wrap nav span[aria-disabled="true"] > span {
            color: #cbd5e1 !important;
            background: #f8fafc !important;
            cursor: not-allowed !important;
        }

        /* 9. Ukuran icon panah SVG */
        .custom-pagination-wrap nav svg {
            width: 16px !important;
            height: 16px !important;
        }   
        
    </style>
</head>

<body>

    @php
    $totalAset = isset($detailAsets) ? $detailAsets->count() : 0;
    $seriItemData = [];
    if(isset($items) && isset($detailAsets)) {
        foreach($items as $item) {
            $seriItemData[$item->id] = $detailAsets->where('item_id', $item->id)->count();
        }
    }
    // Urutan kode dihitung PER CABANG: angka paling kanan pada kode_aset (mis. .../25062023/17 -> 17)
    $urutanPerCabang = [];
    foreach (\App\Models\DetailAset::select('cabang_id', 'kode_aset')->get() as $barisAset) {
        $akhir = str_contains((string) $barisAset->kode_aset, '/') ? (int) substr(strrchr($barisAset->kode_aset, '/'), 1) : 0;
        $urutanPerCabang[$barisAset->cabang_id] = max($urutanPerCabang[$barisAset->cabang_id] ?? 0, $akhir);
    }
    // Asumsi: Anda mempassing variabel $cabangs dari controller
    @endphp

    <input type="hidden" id="json-total-aset" value="{{ $totalAset }}">
    <input type="hidden" id="json-urutan-cabang" value="{{ json_encode($urutanPerCabang) }}">
    <input type="hidden" id="json-seri-item" value="{{ json_encode($seriItemData) }}">

    @include('layouts.adminsidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Data Aset Inventaris</h1>
                <p>Pusat pencatatan perangkat dan fasilitas kantor.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="openModalImport()" style="background: var(--primary-color);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Import dari Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="openSidebarTambah()" style="background: var(--secondary-color);">
                    + Tambah Data Fisik Aset Baru
                </button>
            </div>
        </header>

        @if(session('success'))
        <div id="toast-success" class="toast-popup toast-success show"><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
        <div id="toast-error" class="toast-popup toast-error show"><span>{{ $errors->first() }}</span></div>
        @endif


        <!-- ================= SIDEBAR TAMBAH ASET ================= -->
        <div class="sidebar-form-backdrop" id="sidebarTambahAset">
            <div class="sidebar-form-panel" id="sidebarPanel">
                <div class="sidebar-header">
                    <h3>+ Tambah Aset Baru</h3>
                    <button type="button" class="btn-close-modal" onclick="closeSidebarTambah()">&times;</button>
                </div>

                <form action="{{ route('detailaset.store') }}" method="POST" id="form-tambah-fisik" enctype="multipart/form-data" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
                    @csrf
                    <div class="sidebar-body">
                        <div class="preview-banner">
                            <div class="form-group">
                                <label>Kode Aset Final (Otomatis)</label>
                                <input type="text" id="hasil_kode_final" name="kode_aset" readonly required>
                            </div>
                            <div class="form-group">
                                <label>Nomor Seri Final (Otomatis)</label>
                                <input type="text" id="hasil_seri_final" name="nomor_seri" readonly required>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">1</div>
                                <div class="step-title">Klasifikasi & Cabang</div>
                            </div>
                            <div class="grid-1">
                                <div class="form-group">
                                    <label>Pilih Kategori Master <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="pilih_kategori" name="kategori_id" required onchange="filterAsetByKategori(); kalkulasiUrutanOtomatis(); updateAutoGenerate();">
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach($kategoris as $kat)
                                        <option value="{{ $kat->id }}" data-kode="{{ $kat->kode_kategori }}">{{ $kat->nama_kategori }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Pilih Nama Aset Master <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="pilih_aset" name="item_id" required onchange="kalkulasiUrutanOtomatis(); updateAutoGenerate()">
                                        <option value="">-- Pilih Nama Aset --</option>
                                        @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-kategori="{{ $item->kategori_id }}" data-serial="{{ $item->awal_serial }}" data-urutan="{{ $item->urutan ?? 1 }}">{{ $item->nama_aset }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Pilih Cabang <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="pilih_cabang" name="cabang_id" required onchange="kalkulasiUrutanOtomatis(); updateAutoGenerate()">
                                        <option value="">-- Pilih Cabang --</option>
                                        @if(isset($cabangs))
                                            @foreach($cabangs as $cabang)
                                            <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">2</div>
                                <div class="step-title">Pengaturan Format Kode</div>
                            </div>
                            <div class="grid-1">
                                <div class="form-group">
                                    <label>Tanggal Pembuatan Kode <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <input type="date" id="input_tanggal_kode" required onchange="updateAutoGenerate()">
                                    <span style="font-size: 11px; color: var(--text-muted);">Format otomatis di dalam kode: HariBulanTahun (DDMMYYYY)</span>
                                </div>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                <div class="form-group"><label>Urut Kode <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="text" id="input_urut_kode" value="1" oninput="updateAutoGenerate()"></div>
                                <div class="form-group"><label>Urut Seri <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="text" id="input_urut_seri" value="001" oninput="updateAutoGenerate()"></div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-title">Data Pengadaan & Spesifikasi</div>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label>Merk / Brand <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="merk" placeholder="Contoh: Dell, Acer"></div>
                                <div class="form-group"><label>Unit/Jumlah <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="number" name="unit" value="1" min="1" required></div>
                                <div class="form-group"><label>Tgl Pengadaan <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="date" name="tanggal_pengadaan" id="input_tanggal_pengadaan" required></div>
                                <div class="form-group"><label>Harga Beli (Rp) <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="number" name="harga_beli" placeholder="0"></div>
                            </div>
                            <div class="grid-1" style="margin-top: 12px;">
                                <div class="form-group"><label>Vendor Pengadaan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="vendor_pengadaan" placeholder="Nama Supplier"></div>
                                <div class="form-group"><label>No. Berita Acara <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="no_berita_acara" placeholder="No Dokumen"></div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">4</div>
                                <div class="step-title">Operasional & Status</div>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label>Lokasi Spesifik Saat Ini <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label>
                                    <input type="text" name="lokasi_saat_ini" placeholder="Contoh: Meja Sudut Kiri">
                                </div>
                                <div class="form-group"><label>Nama Penanggung Jawab <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="penanggung_jawab" placeholder="Nama Staff/Divisi"></div>
                            </div>
                            <div class="grid-2" style="margin-top: 12px;">
                                <div class="form-group"><label>Status Aset Umum <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select name="status_aset" required>
                                        <option value="">-- Pilih Status --</option>
                                        @foreach($statuses ?? [] as $status)
                                        <option value="{{ $status->nama_status }}">{{ $status->nama_status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group"><label>Tipe Aset <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select name="status_berjalan" required>
                                        <option value="Tidak Ada" selected>Tidak Ada</option>
                                        <option value="Aset Berjalan">Aset Berjalan</option>
                                        <option value="Aset Tidak Berjalan">Aset Tidak Berjalan</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Ruangan (Diisi - untuk default dari form jika di web opsional) -->
                            <div class="form-group" style="margin-top: 12px;">
                                <label>Keterangan Ruangan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label>
                                <input type="text" name="Ruangan" value="-" placeholder="-">
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">5</div>
                                <div class="step-title">Maintenance & Lainnya</div>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label>Status Maint. <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="status_maintenance" placeholder="Normal"></div>
                                <div class="form-group"><label>Nama Teknisi <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" name="teknisi" placeholder="Teknisi IT"></div>
                                <div class="form-group"><label>Servis Terakhir <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" name="servis_terakhir"></div>
                                <div class="form-group"><label>Servis Berikutnya <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" name="servis_berikutnya"></div>
                            </div>
                        </div>
                        
                        <div class="step-box" style="margin-bottom: 20px;">
                            <div class="step-header">
                                <div class="step-number">6</div>
                                <div class="step-title">Lampiran File <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></div>
                            </div>
                            <div class="grid-1">
                                <div class="form-group">
                                    <label>Upload Foto / Dokumen (Bisa lebih dari 1)</label>
                                    <input type="file" name="lampiran[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" style="background: #fff; border: 1px dashed #cbd5e1; cursor: pointer; padding: 12px;">
                                    <div style="margin-top: 6px; font-size: 12px; color: var(--primary-color); font-weight: 600;">
                                        🗓️ Penanda Waktu Lampiran: <span>{{ date('d F Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeSidebarTambah()">Batal</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--secondary-color);">Simpan Data Aset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= FORM HAPUS (HIDDEN) ================= -->
        <form id="form-delete-fisik" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>

       
        <!-- ================= MODAL DETAIL & EDIT ================= -->
        <div class="modal-backdrop" id="modalDetailAset">
            <div class="modal-container">
                <div class="modal-header">
                    <h3 id="modal-title-text">Detail Informasi Aset</h3>
                    <button type="button" class="btn-close-modal" onclick="closeModalDetail()">&times;</button>
                </div>
                
                <form id="form-edit-fisik" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; overflow: hidden; max-height: calc(92vh - 65px);">
                    @csrf
                    @method('PUT')
                    
                    <!-- AREA PENAMPUNG FILE YANG DIHAPUS SAAT EDIT -->
                    <div id="hidden-hapus-lampiran"></div>

                    <div class="modal-body">
                        <!-- PERINGATAN UPDATE BARCODE -->
                        <div id="edit-barcode-warning" style="display: none; background: #fef3c7; border: 1px solid #f59e0b; padding: 12px 16px; border-radius: 8px; font-size: 13px; color: #b45309; font-weight: 600; line-height: 1.5; margin-bottom: 5px;">
                            ⚠️ <strong>Peringatan:</strong> Jika Anda mengubah <strong>Kode Aset</strong>, Anda wajib melakukan pembaruan (Update QR) pada menu <strong>Barcode Aset</strong> setelah menyimpan perubahan ini agar QR Code tetap sinkron.
                        </div>

                        <!-- PREVIEW -->
                        <div class="preview-banner" style="grid-template-columns: 1fr 1fr;">
                            <div class="form-group"><label>Kode Aset Final</label><input type="text" id="edit_kode_aset" name="kode_aset" readonly></div>
                            <div class="form-group"><label>Nomor Seri Final</label><input type="text" id="edit_nomor_seri" name="nomor_seri" readonly></div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">1</div>
                                <div class="step-title">Klasifikasi & Cabang</div>
                            </div>
                            <div class="grid-3">
                                <div class="form-group"><label>Kategori Master <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="edit_kategori" name="kategori_id" required>
                                        @foreach($kategoris as $kat) <option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="form-group"><label>Nama Aset Master <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="edit_item" name="item_id" required>
                                        @foreach($items as $item) <option value="{{ $item->id }}">{{ $item->nama_aset }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Cabang <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="edit_cabang" name="cabang_id" required>
                                        <option value="">-- Pilih Cabang --</option>
                                        @if(isset($cabangs))
                                            @foreach($cabangs as $cabang)
                                            <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">2</div>
                                <div class="step-title">Data Pengadaan & Spesifikasi</div>
                            </div>
                            <div class="grid-3">
                                <div class="form-group"><label>Merk / Brand <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_merk" name="merk"></div>
                                <div class="form-group"><label>Unit/Jumlah <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="number" id="edit_unit" name="unit" min="1" required></div>
                                <div class="form-group"><label>Tgl Pengadaan <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label><input type="date" id="edit_tgl_pengadaan" name="tanggal_pengadaan" required></div>
                                <div class="form-group"><label>Harga Beli (Rp) <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="number" id="edit_harga_beli" name="harga_beli"></div>
                                <div class="form-group"><label>Vendor Pengadaan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_vendor" name="vendor_pengadaan"></div>
                                <div class="form-group"><label>No. Berita Acara <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_no_ba" name="no_berita_acara"></div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-title">Operasional & Status</div>
                            </div>
                            <div class="grid-2">
                                <div class="form-group"><label>Lokasi Spesifik Saat Ini <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_lokasi_spesifik" name="lokasi_saat_ini"></div>
                                <div class="form-group"><label>Penanggung Jawab <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_pj" name="penanggung_jawab"></div>
                                <div class="form-group"><label>Status Aset Umum <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="edit_status_aset" name="status_aset" required>
                                        @foreach($statuses ?? [] as $status) <option value="{{ $status->nama_status }}">{{ $status->nama_status }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="form-group"><label>Tipe Aset <span style="color: var(--danger-color); font-weight: bold; font-size: 14px;">*</span></label>
                                    <select id="edit_status_berjalan" name="status_berjalan" required>
                                        <option value="Tidak Ada">Tidak Ada</option>
                                        <option value="Aset Berjalan">Aset Berjalan</option>
                                        <option value="Aset Tidak Berjalan">Aset Tidak Berjalan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 12px;"><label>Keterangan Ruangan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label>
                                <input type="text" id="edit_ruangan" name="Ruangan" placeholder="-">
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">4</div>
                                <div class="step-title">Maintenance & Mutasi</div>
                            </div>
                            <div class="grid-3">
                                <div class="form-group"><label>Status Maint. <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_status_maintenance" name="status_maintenance"></div>
                                <div class="form-group"><label>Nama Teknisi <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_teknisi" name="teknisi"></div>
                                <div class="form-group"><label>Biaya Servis (Rp) <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="number" id="edit_biaya_servis" name="biaya_servis"></div>
                                <div class="form-group"><label>Servis Terakhir <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" id="edit_servis_terakhir" name="servis_terakhir"></div>
                                <div class="form-group"><label>Servis Berikutnya <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" id="edit_servis_berikutnya" name="servis_berikutnya"></div>
                                <div class="form-group"><label>Riwayat Mutasi <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_riwayat_mutasi" name="riwayat_mutasi"></div>
                                <div class="form-group"><label>Tgl Mutasi <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" id="edit_tgl_mutasi" name="tanggal_mutasi"></div>
                                <div class="form-group"><label>Status Peleburan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="text" id="edit_status_peleburan" name="status_peleburan"></div>
                                <div class="form-group"><label>Tgl Peleburan <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="date" id="edit_tgl_peleburan" name="tanggal_peleburan"></div>
                                <div class="form-group"><label>Nilai Jual (Rp) <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label><input type="number" id="edit_nilai_jual" name="nilai_jual"></div>
                            </div>
                        </div>

                        <div class="step-box">
                            <div class="step-header">
                                <div class="step-number">5</div>
                                <div class="step-title">Daftar Lampiran Tersimpan</div>
                            </div>
                            <div class="grid-1">
                                <div class="form-group">
                                    <div id="daftar_lampiran_terpasang" style="display: flex; flex-wrap: wrap; gap: 12px; padding: 12px; background: #e2e8f0; border-radius: 8px; min-height: 40px;">
                                        <!-- Card lampiran akan diisi otomatis oleh JS -->
                                    </div>
                                </div>
                                <!-- Kolom Tambah Baru (Hanya Muncul Saat Mode Edit) -->
                                <div class="form-group" id="upload-baru-section" style="margin-top: 10px; display: none;">
                                    <label>Tambahkan File Baru <span style="color: var(--text-muted); font-size: 11px;">(Opsional)</span></label>
                                    <input type="file" name="lampiran[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" style="background: #fff; border: 1px dashed #cbd5e1; cursor: pointer; padding: 12px;">
                                    <div style="margin-top: 6px; font-size: 12px; color: var(--primary-color); font-weight: 600;">
                                        🗓️ Penanda Waktu Lampiran: <span>{{ date('d F Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER KETIKA MODE VIEW (READ-ONLY) -->
                    <div class="modal-footer" id="footer-view-mode">
                        <button type="button" class="btn btn-secondary" onclick="closeModalDetail()">Tutup</button>
                        <button type="button" class="btn btn-primary" onclick="enableEditMode()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            Edit Data Aset
                        </button>
                    </div>

                    <!-- FOOTER KETIKA MODE EDIT -->
                    <div class="modal-footer" id="footer-edit-mode" style="display: none;">
                        <div>
                            <button type="button" class="btn btn-danger" onclick="submitHapusAset()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                Hapus Aset
                            </button>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="cancelEditMode()">Batal Edit</button>
                            <button type="submit" class="btn btn-primary" style="background: var(--secondary-color);">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL IMPORT EXCEL ================= -->
        <div class="modal-backdrop" id="modalImportExcel">
            <div class="modal-container" style="max-width: 450px;">
                <div class="modal-header">
                    <h3>Import Data Aset</h3>
                    <button type="button" class="btn-close-modal" onclick="closeModalImport()">&times;</button>
                </div>
                
                <form action="{{ route('aset.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Unggah File Excel (.xlsx / .xls)</label>
                            <input type="file" name="file_excel" accept=".xlsx, .xls, .csv" required style="border: 1px dashed #cbd5e1; padding: 25px 15px; cursor: pointer; text-align: center;">
                            <small style="color: var(--text-muted); font-size: 11px; margin-top: 6px;">Sistem akan otomatis mendeteksi Master Kategori, Master Aset, dan Cabang berdasarkan nama dari kolom Excel Anda.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModalImport()">Batal</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--secondary-color);" onclick="this.innerHTML='<span class=\'spinner-sm\'></span> Memproses...'; this.style.opacity='0.7';">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL DATA -->
        <div class="card-box">
            <h3 style="font-size: 16px; color: var(--primary-color); margin-bottom: 15px;">Daftar Aset Terdaftar</h3>
            <form action="{{ url()->current() }}" method="GET" class="aset-toolbar" style="margin-bottom: 15px;">
                <div class="aset-search-wrap" style="display: flex; gap: 10px; align-items: center;">
                    <div style="position: relative; flex: 1; min-width: 300px;">
                        <svg class="aset-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        
                        <!-- Input Search -->
                        <input type="text" name="search" value="{{ request('search') }}" id="searchAset" placeholder="Cari kode, nama, cabang, merk, lokasi..." autocomplete="off">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">Cari Data</button>
                    
                    <!-- Tombol Reset Muncul jika sedang mencari sesuatu -->
                    @if(request('search') || (request('filter_tipe') && request('filter_tipe') != 'Semua'))
                        <a href="{{ url()->current() }}" class="btn btn-danger" style="text-decoration: none; padding: 9px 18px;">Reset</a>
                    @endif
                </div>
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="filterStatusBerjalan" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Filter Tipe Aset:</label>
                    <!-- Dropdown Filter -->
                    <select name="filter_tipe" id="filterStatusBerjalan" onchange="this.form.submit()" style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; outline: none; cursor: pointer;">
                        <option value="Semua" {{ request('filter_tipe') == 'Semua' ? 'selected' : '' }}>Tampilkan Semua</option>
                        <option value="Aset Berjalan" {{ request('filter_tipe') == 'Aset Berjalan' ? 'selected' : '' }}>Aset Berjalan</option>
                        <option value="Aset Tidak Berjalan" {{ request('filter_tipe') == 'Aset Tidak Berjalan' ? 'selected' : '' }}>Aset Tidak Berjalan</option>
                        <option value="Tidak Ada" {{ request('filter_tipe') == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                    </select>
                </div>
            </form>
            <div id="searchChips" class="aset-chips"></div>
            <div id="searchInfo" class="aset-info"></div>
            <div class="table-container" style="margin-top: 15px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <!-- URUTAN KOLOM YANG DIPERBARUI -->
                            <th>KODE ASET</th>
                            <th>NAMA ASET</th>
                            <th>KATEGORI</th>
                            <th>CABANG</th>
                            <th>RUANGAN</th> 
                            <th>MERK</th>
                            <th>NOMOR SERI</th>
                            <th>UNIT</th>
                            <th>TGL PENGADAAN</th>
                            <th>HARGA BELI</th>
                            <th>LOKASI SPESIFIK SAAT INI</th>
                            <th>PENANGGUNG JAWAB</th>
                            <th>STATUS ASET</th>
                            <th>TIPE ASET</th>
                            <th>STATUS MAINT.</th>
                            <th>TEKNISI</th>
                            <th>BIAYA SERVIS</th>
                            <th>SERVIS TERAKHIR</th>
                            <th>SERVIS BERIKUTNYA</th>
                            <th>RIWAYAT MUTASI</th>
                            <th>TGL MUTASI</th>
                            <th>STATUS PELEBURAN</th>
                            <th>TGL PELEBURAN</th>
                            <th>METODE PELEBURAN</th>
                            <th>NILAI JUAL</th>
                            <th>NO. BERITA ACARA</th>
                            <th>VENDOR PENGADAAN</th>
                            <th class="aksi-col" style="text-align: center;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detailAsets ?? [] as $detail)
                        @php
                            $warnaBg = '#f59e0b'; 
                            if(isset($statuses)) {
                                $match = $statuses->where('nama_status', $detail->status_aset)->first();
                                if($match && !empty($match->warna_status)) $warnaBg = $match->warna_status;
                            }
                            
                            $hex = ltrim($warnaBg, '#');
                            $rowBg = 'rgba(245, 158, 11, 0.08)'; 
                            if (strlen($hex) == 6) {
                                $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                                $rowBg = "rgba($r, $g, $b, 0.08)";
                            } elseif (strlen($hex) == 3) {
                                $r = hexdec(str_repeat(substr($hex, 0, 1), 2)); $g = hexdec(str_repeat(substr($hex, 1, 1), 2)); $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
                                $rowBg = "rgba($r, $g, $b, 0.08)";
                            }
                        @endphp
                        <tr style="background-color: {{ $rowBg }};">
                            <td style="font-weight: 700; color: var(--primary-color);">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span>{{ $detail->kode_aset ?? '-' }}</span>
                                    
                                    @if(!empty($detail->servis_berikutnya) && $detail->servis_berikutnya !== '-')
                                        @php
                                            $tglServisKode = \Carbon\Carbon::parse($detail->servis_berikutnya)->startOfDay();
                                            $hariIniKode = \Carbon\Carbon::now()->startOfDay();
                                            $selisihKode = $hariIniKode->diffInDays($tglServisKode, false); 
                                        @endphp
                                        
                                        @if($selisihKode <= 30 && $selisihKode > 7)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #f59e0b; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(245,158,11,0.6);">!</div>
                                                <span class="click-tooltip-text">Waktu servis: {{ $selisihKode }} hari lagi</span>
                                            </div>
                                        @elseif($selisihKode <= 7 && $selisihKode >= 0)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #ef4444; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(239,68,68,0.6);">!</div>
                                                <span class="click-tooltip-text">Mendesak: Servis {{ $selisihKode }} hari lagi!</span>
                                            </div>
                                        @elseif($selisihKode < 0)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #ef4444; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(239,68,68,0.6);">!</div>
                                                <span class="click-tooltip-text">Terlewat {{ abs($selisihKode) }} hari!</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td>{{ $detail->item->nama_aset ?? '-' }}</td>
                            <td>{{ $detail->kategori->nama_kategori ?? '-' }}</td>
                            <td style="font-weight: 600; color: #1e293b;">{{ $detail->cabang->nama_cabang ?? '-' }}</td>
                            
                            <!-- DATA RUANGAN LAMA TAMPIL DI SINI -->
                            <td style="color: #475569;">{{ $detail->Ruangan ?? '-' }}</td>
                            
                            <td>{{ $detail->merk ?? '-' }}</td>
                            <td>{{ $detail->nomor_seri ?? '-' }}</td>
                            <td>{{ $detail->unit ?? '-' }}</td>
                            <td>{{ $detail->tanggal_pengadaan ?? '-' }}</td>
                            <td>{{ $detail->harga_beli ? 'Rp ' . number_format($detail->harga_beli, 0, ',', '.') : '-' }}</td>
                            
                            <!-- LOKASI SPESIFIK (KOSONG DEFAULT DARI IMPORT) -->
                            <td>{{ $detail->lokasi_saat_ini ?? '-' }}</td>
                            
                            <td>{{ $detail->penanggung_jawab ?? '-' }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $warnaBg }}; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $detail->status_aset ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="status-berjalan-label badge" style="background-color: rgba(0,0,0,0.05); color: #1e293b; border: 1px solid rgba(0,0,0,0.1);">{{ $detail->status_berjalan ?? 'Tidak Ada' }}</span>
                            </td>
                            <td>{{ $detail->status_maintenance ?? '-' }}</td>
                            <td>{{ $detail->teknisi ?? '-' }}</td>
                            <td>{{ $detail->biaya_servis ? 'Rp ' . number_format($detail->biaya_servis, 0, ',', '.') : '-' }}</td>
                            <td>{{ $detail->servis_terakhir ?? '-' }}</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span>{{ $detail->servis_berikutnya ?? '-' }}</span>
                                    
                                    @if(!empty($detail->servis_berikutnya) && $detail->servis_berikutnya !== '-')
                                        @php
                                            $tglServis = \Carbon\Carbon::parse($detail->servis_berikutnya)->startOfDay();
                                            $hariIni = \Carbon\Carbon::now()->startOfDay();
                                            $selisih = $hariIni->diffInDays($tglServis, false); 
                                        @endphp
                                        
                                        @if($selisih <= 30 && $selisih > 7)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #f59e0b; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(245,158,11,0.6);">!</div>
                                                <span class="click-tooltip-text">Waktu servis: {{ $selisih }} hari lagi</span>
                                            </div>
                                        @elseif($selisih <= 7 && $selisih >= 0)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #ef4444; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(239,68,68,0.6);">!</div>
                                                <span class="click-tooltip-text">Mendesak: Servis {{ $selisih }} hari lagi!</span>
                                            </div>
                                        @elseif($selisih < 0)
                                            <div class="click-tooltip-container" tabindex="0">
                                                <div style="background: #ef4444; color: #fff; width: 18px; height: 18px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 11px; font-weight: 900; box-shadow: 0 0 6px rgba(239,68,68,0.6);">!</div>
                                                <span class="click-tooltip-text">Terlewat {{ abs($selisih) }} hari!</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td>{{ $detail->riwayat_mutasi ?? '-' }}</td>
                            <td>{{ $detail->tanggal_mutasi ?? '-' }}</td>
                            <td>{{ $detail->status_peleburan ?? '-' }}</td>
                            <td>{{ $detail->tanggal_peleburan ?? '-' }}</td>
                            <td>{{ $detail->metode_peleburan ?? '-' }}</td>
                            <td>{{ $detail->nilai_jual ? 'Rp ' . number_format($detail->nilai_jual, 0, ',', '.') : '-' }}</td>
                            <td>{{ $detail->no_berita_acara ?? '-' }}</td>
                            <td>{{ $detail->vendor_pengadaan ?? '-' }}</td>
                            <td style="text-align: center; position: sticky; right: 0; background: inherit; z-index: 1;">
                                <button type="button" class="btn" style="background: var(--primary-color); color: #fff; padding: 6px 12px; font-size: 12px; border-radius: 6px;" onclick="openModalDetail({{ json_encode($detail) }})">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="row-empty-table"><td colspan="28" style="text-align: center; color: var(--text-muted); padding: 25px;">Belum ada data fisik aset.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="custom-pagination-wrap" style="margin-top: 15px;">
                {{ $detailAsets->links() }}
            </div>
        </div>
    </main>

    <!-- Script Utama -->
    <script>
        const TOTAL_ASET_GLOBAL = parseInt(document.getElementById('json-total-aset').value) || 0;
        const DATA_SERI_ITEM = JSON.parse(document.getElementById('json-seri-item').value || '{}');
        const URUTAN_CABANG = JSON.parse(document.getElementById('json-urutan-cabang').value || '{}');
        
        let currentAsetData = null; 

        // SIDEBAR TAMBAH
        function openSidebarTambah() {
            document.getElementById('sidebarTambahAset').classList.add('show');
            kalkulasiUrutanOtomatis();
            updateAutoGenerate();
        }

        function closeSidebarTambah() {
            document.getElementById('sidebarTambahAset').classList.remove('show');
        }

        // ================= MODAL DETAIL & EDIT LOGIC =================
        // Isi pilihan select dari data baris. Kalau daftar pilihan dari controller tidak memuat
        // nilainya (mis. daftar dibatasi/pagination), buat pilihan sementara memakai nama dari relasi.
        function setSelectDenganCadangan(selectId, nilai, teks) {
            const sel = document.getElementById(selectId);
            if (!sel) return;
            sel.querySelectorAll('option[data-sementara]').forEach(o => o.remove());   // bersihkan sisa baris sebelumnya
            if (nilai === null || nilai === undefined || nilai === '') { sel.value = ''; return; }
            const ada = Array.from(sel.options).some(o => String(o.value) === String(nilai));
            if (!ada) {
                const opt = document.createElement('option');
                opt.value = nilai;
                opt.textContent = teks || ('(ID ' + nilai + ' tidak ditemukan)');
                opt.setAttribute('data-sementara', '1');
                sel.appendChild(opt);
            }
            sel.value = nilai;
        }

        function openModalDetail(data) {
            currentAsetData = data; 
            let id = data.aset_id || data.id;
            
            document.getElementById('form-edit-fisik').action = `/admin/data-aset/${id}`;
            document.getElementById('form-delete-fisik').action = `/admin/data-aset/${id}`;

            document.getElementById('edit_kode_aset').value = data.kode_aset || '';
            document.getElementById('edit_nomor_seri').value = data.nomor_seri || '';
            setSelectDenganCadangan('edit_kategori', data.kategori_id, data.kategori && data.kategori.nama_kategori);
            setSelectDenganCadangan('edit_item', data.item_id, data.item && data.item.nama_aset);
            setSelectDenganCadangan('edit_cabang', data.cabang_id, data.cabang && data.cabang.nama_cabang);
            document.getElementById('edit_ruangan').value = data.Ruangan || '';
            document.getElementById('edit_status_aset').value = data.status_aset || 'Tersedia';
            document.getElementById('edit_status_berjalan').value = data.status_berjalan || 'Tidak Ada';
            document.getElementById('edit_merk').value = data.merk || '';
            document.getElementById('edit_unit').value = data.unit || 1;
            document.getElementById('edit_tgl_pengadaan').value = data.tanggal_pengadaan || '';
            document.getElementById('edit_harga_beli').value = data.harga_beli || '';
            document.getElementById('edit_vendor').value = data.vendor_pengadaan || '';
            document.getElementById('edit_no_ba').value = data.no_berita_acara || '';
            document.getElementById('edit_lokasi_spesifik').value = data.lokasi_saat_ini || '';
            document.getElementById('edit_pj').value = data.penanggung_jawab || '';
            document.getElementById('edit_status_maintenance').value = data.status_maintenance || '';
            document.getElementById('edit_teknisi').value = data.teknisi || '';
            document.getElementById('edit_biaya_servis').value = data.biaya_servis || '';
            document.getElementById('edit_servis_terakhir').value = data.servis_terakhir || '';
            document.getElementById('edit_servis_berikutnya').value = data.servis_berikutnya || '';
            document.getElementById('edit_riwayat_mutasi').value = data.riwayat_mutasi || '';
            document.getElementById('edit_tgl_mutasi').value = data.tanggal_mutasi || '';
            document.getElementById('edit_status_peleburan').value = data.status_peleburan || '';
            document.getElementById('edit_tgl_peleburan').value = data.tanggal_peleburan || '';
            document.getElementById('edit_nilai_jual').value = data.nilai_jual || '';

            // Render Lampiran
            let kotakLampiran = document.getElementById('daftar_lampiran_terpasang');
            kotakLampiran.innerHTML = ''; 
            document.getElementById('hidden-hapus-lampiran').innerHTML = ''; // Reset file terhapus
            
            if (data.lampiran && data.lampiran !== "null" && data.lampiran !== "") {
                let files = [];
                try { files = typeof data.lampiran === 'string' ? JSON.parse(data.lampiran) : data.lampiran; } catch(e) { files = []; }

                if(Array.isArray(files) && files.length > 0) {
                    
                    // ==========================================
                    // FITUR BARU: Ambil Tanggal Upload Otomatis
                    // ==========================================
                    let rawDate = data.created_at;
                    let tglUpload = "-";
                    if (rawDate) {
                        let d = new Date(rawDate);
                        tglUpload = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    }

                    files.forEach(function(filePath, index) {
                        let filename = filePath.split('/').pop();
                        let ext = filename.split('.').pop().toLowerCase();
                        let isImage = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
                        
                        let previewElement = isImage 
                            ? `<img src="/admin/view-lampiran/${filename}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 6px; margin-bottom: 6px; border: 1px solid #e2e8f0;">`
                            : `<div style="width: 100%; height: 75px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px; font-weight: 800; color: #64748b; font-size: 16px;">${ext.toUpperCase()}</div>`;

                        let elementId = `lampiran-card-${index}`;
                        kotakLampiran.innerHTML += `
                            <div id="${elementId}" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px; border-radius: 8px; width: 140px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: relative;">
                                <button type="button" class="btn-hapus-lampiran" onclick="tandaiHapusLampiran('${filename}', '${elementId}')" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; font-weight: bold; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 10; box-shadow: 0 2px 4px rgba(239,68,68,0.4);">&times;</button>
                                
                                ${previewElement}
                                <span style="font-size: 10px; font-weight: 600; color: #1e293b; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; text-align: center;" title="${filename}">${filename}</span>
                                
                                <!-- ========================================== -->
                                <!-- FITUR BARU: Menampilkan Teks Tanggal Upload -->
                                <!-- ========================================== -->
                                <span style="font-size: 9px; color: #64748b; font-weight: 600; margin-bottom: 8px; text-align: center; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Diunggah: ${tglUpload}</span>
                                
                                <div style="display: flex; gap: 4px; width: 100%;">
                                    <a href="/admin/view-lampiran/${filename}" target="_blank" style="flex: 1; background: var(--primary-color); color: #fff; padding: 5px 0; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 600; text-align: center;">Lihat</a>
                                    <a href="/admin/download-lampiran/${filename}" style="flex: 1; background: var(--secondary-color); color: #fff; padding: 5px 0; border-radius: 4px; font-size: 11px; text-decoration: none; font-weight: 600; text-align: center;">Unduh</a>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    kotakLampiran.innerHTML = '<span style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 5px;">Tidak ada file terlampir.</span>';
                }
            } else {
                kotakLampiran.innerHTML = '<span style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 5px;">Tidak ada file terlampir.</span>';
            }

            setViewMode();
            document.getElementById('modalDetailAset').classList.add('show');
        }

        function setViewMode() {
            document.getElementById('modal-title-text').innerText = "Detail Informasi Aset";
            document.querySelectorAll('#form-edit-fisik input:not([type="hidden"]), #form-edit-fisik select').forEach(el => { el.disabled = true; });
            document.getElementById('edit_kode_aset').readOnly = true;
            document.getElementById('edit_nomor_seri').readOnly = true;
            document.getElementById('edit-barcode-warning').style.display = 'none';
            
            document.getElementById('upload-baru-section').style.display = 'none';
            document.getElementById('footer-view-mode').style.display = 'flex';
            document.getElementById('footer-edit-mode').style.display = 'none';
            
            document.querySelectorAll('.btn-hapus-lampiran').forEach(el => el.style.display = 'none');
        }

        function enableEditMode() {
            document.getElementById('modal-title-text').innerText = "Edit Data Aset";
            document.querySelectorAll('#form-edit-fisik input:not([type="hidden"]), #form-edit-fisik select').forEach(el => { 
                el.disabled = false; 
            });
            document.getElementById('edit_kode_aset').readOnly = false;
            document.getElementById('edit_nomor_seri').readOnly = false;
            document.getElementById('edit-barcode-warning').style.display = 'block';
            
            document.getElementById('upload-baru-section').style.display = 'block';
            document.getElementById('footer-view-mode').style.display = 'none';
            document.getElementById('footer-edit-mode').style.display = 'flex';

            document.querySelectorAll('.btn-hapus-lampiran').forEach(el => el.style.display = 'flex');
        }

        function cancelEditMode() {
            openModalDetail(currentAsetData);
        }

        function tandaiHapusLampiran(filename, elementId) {
            if(confirm('Yakin ingin menghapus lampiran ini? File akan terhapus permanen setelah Anda klik Simpan Perubahan.')) {
                document.getElementById(elementId).style.display = 'none';
                document.getElementById('hidden-hapus-lampiran').innerHTML += `<input type="hidden" name="hapus_lampiran[]" value="${filename}">`;
            }
        }

        function closeModalDetail() {
            document.getElementById('modalDetailAset').classList.remove('show');
        }

        function submitHapusAset() {
            if(confirm('Peringatan: Yakin ingin menghapus data aset ini secara permanen? Aksi ini tidak dapat dibatalkan.')) {
                document.getElementById('form-delete-fisik').submit();
            }
        }

        window.onclick = function(event) {
            let sidebarTambah = document.getElementById('sidebarTambahAset');
            let modalDetail = document.getElementById('modalDetailAset');
            if (event.target === sidebarTambah) closeSidebarTambah();
            if (event.target === modalDetail) closeModalDetail();
        }
        
        function kalkulasiUrutanOtomatis() {
            // Urut kode per cabang: nomor terakhir cabang yang dipilih + 1 (cabang belum dipilih -> 1)
            let cabangId = document.getElementById('pilih_cabang').value;
            document.getElementById('input_urut_kode').value = (cabangId ? (URUTAN_CABANG[cabangId] || 0) : 0) + 1;
            let itemId = document.getElementById('pilih_aset').value;
            let inputSeri = document.getElementById('input_urut_seri');
            if (itemId && DATA_SERI_ITEM[itemId] !== undefined) {
                inputSeri.value = String(parseInt(DATA_SERI_ITEM[itemId]) + 1).padStart(3, '0');
            } else {
                inputSeri.value = '001';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => { document.querySelectorAll('.toast-popup').forEach(el => el.classList.remove('show')); }, 2500);
            
            let today = new Date();
            let yyyy = today.getFullYear();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let dd = String(today.getDate()).padStart(2, '0');
            let todayFormat = `${yyyy}-${mm}-${dd}`;
            
            let inputTglPengadaan = document.getElementById('input_tanggal_pengadaan');
            if (inputTglPengadaan && !inputTglPengadaan.value) { 
                inputTglPengadaan.value = todayFormat; 
            }

            let inputTanggalKode = document.getElementById('input_tanggal_kode');
            if(inputTanggalKode && !inputTanggalKode.value) {
                inputTanggalKode.value = todayFormat;
            }

            document.querySelectorAll('#form-tambah-fisik input, #form-tambah-fisik select').forEach(element => {
                element.addEventListener('input', updateAutoGenerate);
                element.addEventListener('change', updateAutoGenerate);
            });
            
            kalkulasiUrutanOtomatis();
            updateAutoGenerate();
        });

        function updateAutoGenerate() {
            let selectKat = document.getElementById('pilih_kategori');
            let rawKodeKat = selectKat.options[selectKat.selectedIndex]?.getAttribute('data-kode') || 'X';
            let kodeKat = rawKodeKat.replace(/\./g, "").trim();

            let selectAset = document.getElementById('pilih_aset');
            let selectedAsetOpt = selectAset.options[selectAset.selectedIndex];
            let awalSerial = selectedAsetOpt?.getAttribute('data-serial') || 'XXX';
            let assetUrutan = selectedAsetOpt?.getAttribute('data-urutan') || '1';
            let formatUrutAset = String(assetUrutan).padStart(2, '0');

            let tanggalKodeVal = document.getElementById('input_tanggal_kode')?.value;
            let formatHariBulanTahun = "DDMMYYYY";

            if(tanggalKodeVal) {
                let parts = tanggalKodeVal.split('-');
                if(parts.length === 3) {
                    formatHariBulanTahun = parts[2] + parts[1] + parts[0]; 
                }
            }

            let urutKode = document.getElementById('input_urut_kode')?.value || '1';
            let urutSeri = document.getElementById('input_urut_seri')?.value || '001';

            document.getElementById('hasil_kode_final').value = `${kodeKat}.${formatUrutAset}/${formatHariBulanTahun}/${urutKode}`;
            document.getElementById('hasil_seri_final').value = `${awalSerial}-${urutSeri}`;
        }

        function filterAsetByKategori() {
            let selectedKatId = document.getElementById('pilih_kategori').value;
            let selectAset = document.getElementById('pilih_aset');
            selectAset.querySelectorAll('option').forEach(option => {
                if (option.value === "") return;
                option.style.display = (selectedKatId === "" || option.getAttribute('data-kategori') === selectedKatId) ? "" : "none";
            });
            selectAset.value = "";
        }

        function filterTabelBerjalan() {
            if (typeof window.terapkanFilterTabel === 'function') window.terapkanFilterTabel();
        }

        function openModalImport() {
            document.getElementById('modalImportExcel').classList.add('show');
        }

        function closeModalImport() {
            document.getElementById('modalImportExcel').classList.remove('show');
        }
        
        let originalWindowClick = window.onclick;
        window.onclick = function(event) {
            if(originalWindowClick) originalWindowClick(event);
            
            let modalImport = document.getElementById('modalImportExcel');
            if (event.target === modalImport) closeModalImport();
        }
    </script>

</body>
</html>