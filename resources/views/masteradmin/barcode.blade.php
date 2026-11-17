<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Barcode Aset - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        :root {
            --primary-color: #1a4f91;
            --secondary-color: #10b981;
            --bg-light: #f8fafc;
            --text-color: #000;
            --text-muted: rgba(0, 0, 0, 0.45);
            --border-color: rgba(0, 0, 0, 0.15);
            --sidebar-width: 270px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

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
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: calc(100% - var(--sidebar-width));
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 { font-size: 26px; font-weight: 700; color: var(--primary-color); }
        .header-title p { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }

        .card-box {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .btn-qr {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .btn-qr:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .asset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .asset-card {
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .asset-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }

        .card-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px dashed rgba(0,0,0,0.1);
        }

        .card-title { font-size: 16px; font-weight: 800; color: var(--primary-color); margin-bottom: 4px; }
        .card-subtitle { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        .card-body { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .info-label { color: var(--text-muted); font-weight: 600; }
        .info-value { font-weight: 700; color: #1e293b; }

        .card-footer { display: flex; gap: 10px; margin-top: auto; }
        .card-footer .btn-qr { flex: 1; text-align: center; }

        .modal-backdrop {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6);
            display: flex; justify-content: center; align-items: center; z-index: 9999;
            opacity: 0; visibility: hidden; transition: all 0.3s; backdrop-filter: blur(2px);
        }
        .modal-backdrop.show { opacity: 1; visibility: visible; }

        .modal-container {
            background: #fff; width: 400px; border-radius: 16px; padding: 25px; text-align: center;
            transform: scale(0.95); transition: all 0.3s; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }
        .modal-backdrop.show .modal-container { transform: scale(1); }

        .qr-box { margin: 20px auto; padding: 15px; border: 2px dashed #cbd5e1; border-radius: 12px; display: inline-block; background: #f8fafc; }
        .qr-box img { width: 200px; height: 200px; display: block; }

        .qr-info { margin-top: 15px; font-size: 13px; color: #475569; font-weight: 600; }
        .btn-close { background: #e2e8f0; color: #1e293b; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px; width: 100%; }
        .btn-close:hover { background: #cbd5e1; }
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

        .navbar-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin: 0;
        }

        .navbar-search-box {
            position: relative;
            flex: 1 1 280px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 9px 14px;
            transition: all 0.2s ease;
        }

        .navbar-search-box:focus-within {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.12);
        }

        .navbar-search-box input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-color);
            outline: none;
        }

        .navbar-filter-select {
            padding: 9px 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            background: #f8fafc;
            color: var(--text-color);
            transition: all 0.2s ease;
        }

        .navbar-filter-select:focus {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.12);
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

        <header class="main-header" id="navbar-barcode-fixed">
            <div class="navbar-top-row">
                <div class="header-title">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Barcode & QR Aset</span>
                    </h1>
                    <p>Pusat pembuatan dan cetak label QR Code aset fisik.</p>
                </div>

                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                    <button type="button" class="btn-qr btn-generate-semua-qr" style="background: var(--secondary-color); padding: 10px 18px;" onclick="prosesGenerateSemua()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 8v6h6"></path></svg>
                        Generate Semua
                    </button>

                    <button type="button" class="btn-qr btn-update-semua-qr" style="background: #f59e0b; padding: 10px 18px;" onclick="prosesUpdateSemua()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        Update Semua
                    </button>

                    <button type="button" class="btn-qr" style="background: var(--primary-color); padding: 10px 18px;" onclick="downloadSemuaQRAsJPG()" id="btn-download-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Download Semua QR
                    </button>
                </div>
            </div>

            <div class="navbar-search-row">
                <form action="{{ url()->current() }}" method="GET" class="navbar-search-form" id="form-filter-barcode">
                    @if(request('panel'))
                        <input type="hidden" name="panel" value="{{ request('panel') }}">
                    @endif

                    <div style="display: flex; gap: 10px; flex: 1; min-width: 260px; align-items: center;">
                        <div class="navbar-search-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px; color: #64748b; flex-shrink: 0;">
                                <circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="input-search-barcode" name="search" value="{{ request('search') }}" placeholder="Cari apapun: kode aset, nama, cabang, ruangan, kategori, merk, seri, PIC, status..." autocomplete="off">
                        </div>

                        <button type="submit" class="btn-qr" style="padding: 10px 18px; font-size: 13px; background: var(--primary-color); border-radius: 10px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <span>Cari QR</span>
                        </button>

                        @if(request('search') || (request('filter_tipe') && request('filter_tipe') != 'Semua'))
                            <a href="{{ url()->current() }}{{ request('panel') ? '?panel=' . request('panel') : '' }}" class="btn-qr" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; text-decoration: none; padding: 9px 15px; font-size: 13px; border-radius: 10px; box-shadow: none;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Reset</span>
                            </a>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="filterStatusBerjalan" style="font-size: 13px; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Filter Tipe:</label>
                        <select name="filter_tipe" id="filterStatusBerjalan" onchange="this.form.submit()" class="navbar-filter-select">
                            <option value="Semua" {{ request('filter_tipe') == 'Semua' ? 'selected' : '' }}>Tampilkan Semua</option>
                            <option value="Aset Berjalan" {{ request('filter_tipe') == 'Aset Berjalan' ? 'selected' : '' }}>Aset Berjalan Saja</option>
                            <option value="Aset Tidak Berjalan" {{ request('filter_tipe') == 'Aset Tidak Berjalan' ? 'selected' : '' }}>Aset Tidak Berjalan Saja</option>
                            <option value="Aset Tidak Dipakai" {{ strtolower((string) request('filter_tipe')) === 'aset tidak dipakai' ? 'selected' : '' }}>Aset Tidak Dipakai Saja</option>
                            <option value="Tidak Ada" {{ request('filter_tipe') == 'Tidak Ada' ? 'selected' : '' }}>Tidak Ada</option>
                        </select>
                    </div>
                </form>
            </div>
        </header>

        <div class="card-box">
            <input type="hidden" id="json-katalog-semua-qr" value="{{ json_encode($katalogSemuaQr ?? []) }}">

            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">
                <h3 style="font-size: 16px; color: var(--primary-color); margin: 0;">Galeri Label QR Aset Fisik</h3>
                <span id="info-hasil-search-global" style="display: none; font-size: 12.5px; font-weight: 700; color: var(--primary-color); background: #eff6ff; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: 999px;"></span>
            </div>

            <div class="asset-grid">
                @forelse($detailAsets ?? [] as $detail)
                <div class="asset-card baris-qr-aset" data-bg="{{ $detail->qr_attr['card_bg'] ?? 'rgba(148, 163, 184, 0.08)' }}" data-border-top="{{ $detail->qr_attr['warna_bg'] ?? '#94a3b8' }}" data-search="{{ $detail->qr_attr['search'] ?? '' }}">
                    <div class="card-header">
                        <div>
                            <div class="card-title">{{ $detail->qr_attr['nama'] ?? 'Tanpa Nama' }}</div>
                            <div class="card-subtitle">{{ $detail->qr_attr['kategori'] ?? '-' }}</div>
                        </div>
                        <span class="badge" data-bg="{{ $detail->qr_attr['warna_bg'] ?? '#94a3b8' }}" style="color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            {{ $detail->qr_attr['status_aset'] ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Kode Aset</span>
                            <span class="info-value" style="color: var(--primary-color);">{{ $detail->qr_attr['kode_aset'] ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nomor Seri</span>
                            <span class="info-value">{{ $detail->qr_attr['nomor_seri'] ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Merk</span>
                            <span class="info-value">{{ $detail->qr_attr['merk'] ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cabang</span>
                            <span class="info-value">{{ $detail->qr_attr['cabang'] ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ruangan</span>
                            <span class="info-value">{{ $detail->qr_attr['ruangan'] ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tipe Berjalan</span>
                            <span class="label-tipe-berjalan badge" style="background-color: rgba(0,0,0,0.05); color: #1e293b; border: 1px solid rgba(0,0,0,0.1);">
                                {{ $detail->qr_attr['status_berjalan'] ?? 'Tidak Ada' }}
                            </span>
                        </div>
                    </div>

                    <div class="card-footer">
                        @if(!empty($detail->qr_attr['has_qr']))
                            <button type="button" class="btn-qr btn-lihat-qr" style="background: var(--secondary-color);"
                                data-url="{{ $detail->qr_attr['url_qr'] ?? '' }}"
                                data-kode="{{ $detail->qr_attr['kode_aset'] ?? '' }}"
                                data-seri="{{ $detail->qr_attr['nomor_seri'] ?? '' }}"
                                data-nama="{{ $detail->qr_attr['nama'] ?? 'Aset' }}"
                                data-file="{{ $detail->qr_attr['file_qr'] ?? '' }}"
                                onclick="bukaModalQRDariTombol(this)">
                                Lihat QR
                            </button>

                            @if(!empty($detail->qr_attr['up_to_date']))
                                <button type="button" class="btn-qr" style="background: #cbd5e1; color: #64748b; cursor: not-allowed; box-shadow: none;" disabled title="QR sudah versi terbaru">
                                    Update QR
                                </button>
                            @else
                                <button type="button" class="btn-qr" style="background: #f59e0b;"
                                    data-id="{{ $detail->aset_id }}"
                                    onclick="prosesBuatQR(this.dataset.id, this)" title="Data berubah, klik untuk memperbarui QR">
                                    Update QR
                                </button>
                            @endif
                        @else
                            <button type="button" class="btn-qr" style="width: 100%; background: var(--secondary-color);"
                                data-id="{{ $detail->aset_id }}"
                                onclick="prosesBuatQR(this.dataset.id, this)">
                                Buat Label QR Baru
                            </button>
                        @endif
                    </div>
                </div>
                @empty
                <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 50px; background: #fff; border-radius: 16px; border: 2px dashed #cbd5e1;">
                    <p style="font-size: 15px; font-weight: 600;">Belum ada data fisik aset untuk dibuatkan QR Code.</p>
                </div>
                @endforelse
            </div>
            @if($detailAsets->hasPages())
            <div class="custom-pagination-wrap" style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                {{ $detailAsets->links() }}
            </div>
            @endif
        </div>
    </main>

    <div class="modal-backdrop" id="modalQR">
        <div class="modal-container">
            <h3 style="color: var(--primary-color); font-size: 18px; margin-bottom: 5px;">Generate QR Code</h3>
            <p id="qr-nama-aset" style="font-size: 13px; color: var(--text-muted);"></p>

            <div class="qr-box">
                <img id="qr-image" src="" alt="Loading QR...">
            </div>

            <div class="qr-info">
                <div id="qr-teks-kode" style="color: var(--primary-color); font-size: 16px;"></div>
                <div id="qr-teks-seri" style="font-weight: 400; margin-top: 4px;"></div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn-close" onclick="tutupModalQR()" style="margin-top: 0; flex: 1;">Tutup</button>

                <button type="button" id="btn-download-qr-single" onclick="downloadQRSingleAsJPG()" class="btn-qr" style="margin-top: 0; flex: 1; padding: 10px; font-size: 14px;">
                    Download JPG
                </button>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="modalLoadingGenerate" style="z-index: 99999;">
        <div class="modal-container" style="width: 350px; padding: 40px 20px; text-align: center;">
            <div class="spinner-btn" style="width: 50px; height: 50px; border-width: 4px; border-color: rgba(26, 79, 145, 0.2); border-top-color: var(--primary-color); margin-bottom: 20px;"></div>
            <h3 style="color: var(--primary-color); font-size: 18px; margin-bottom: 10px;">Sedang Memproses...</h3>
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">Sistem sedang membuat dan mencocokkan ratusan data QR Code di *background*.<br>Mohon jangan tutup halaman ini.</p>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-bg]').forEach(function(el) { el.style.backgroundColor = el.getAttribute('data-bg'); });
        document.querySelectorAll('[data-border-top]').forEach(function(el) { el.style.borderTop = '5px solid ' + el.getAttribute('data-border-top'); });
        let currentQrUrl = '';
        let currentQrFilename = '';
        const csrfTokenBarcode = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';

        function prosesBuatQR(idAset, btnElement) {
            let originalText = btnElement.innerText;
            btnElement.innerText = 'Memproses...';
            btnElement.disabled = true;

            fetch(`/masteradmin/generate-qr/${idAset}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfTokenBarcode, 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { location.reload(); }
                    else { alert(data.message); btnElement.innerText = originalText; btnElement.disabled = false; }
                })
                .catch(err => {
                    alert('Terjadi kesalahan fatal pada jaringan/server.');
                    btnElement.innerText = originalText;
                    btnElement.disabled = false;
                });
        }

        function bukaModalQRDariTombol(btnEl) {
            if (!btnEl) return;
            bukaModalQR(
                btnEl.getAttribute('data-url') || '',
                btnEl.getAttribute('data-kode') || '',
                btnEl.getAttribute('data-seri') || '',
                btnEl.getAttribute('data-nama') || 'Aset',
                btnEl.getAttribute('data-file') || ''
            );
        }

        function bukaModalQR(qrUrl, kodeAset, nomorSeri, namaAset, namaFile) {
            let modal = document.getElementById('modalQR');
            let finalUrl = qrUrl + '?v=' + new Date().getTime();

            currentQrUrl = finalUrl;
            currentQrFilename = namaFile;

            document.getElementById('qr-image').src = finalUrl;
            document.getElementById('qr-nama-aset').innerText = namaAset;
            document.getElementById('qr-teks-kode').innerText = kodeAset;
            document.getElementById('qr-teks-seri').innerText = nomorSeri;

            modal.classList.add('show');
        }

        function tutupModalQR() {
            document.getElementById('modalQR').classList.remove('show');
            document.getElementById('qr-image').src = '';
        }

        function downloadQRSingleAsJPG() {
            let btn = document.getElementById('btn-download-qr-single');
            let originalText = btn.innerText;
            btn.innerText = "Memproses JPG...";
            btn.disabled = true;

            let img = new Image();
            img.crossOrigin = "Anonymous";
            img.onerror = function() {
                btn.innerText = originalText;
                btn.disabled = false;
                alert("Gagal memuat gambar QR Code untuk diunduh.");
            };
            img.onload = function() {
                let canvas = document.createElement("canvas");
                let pad = 30;
                canvas.width = img.width + (pad * 2);
                canvas.height = img.height + (pad * 2) + 60;
                let ctx = canvas.getContext("2d");

                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.drawImage(img, pad, pad);

                ctx.fillStyle = "#1a4f91";
                ctx.font = "bold 20px Arial";
                ctx.textAlign = "center";
                ctx.fillText(document.getElementById('qr-teks-kode').innerText, canvas.width/2, img.height + pad + 30);

                ctx.fillStyle = "#475569";
                ctx.font = "16px Arial";
                ctx.fillText(document.getElementById('qr-teks-seri').innerText, canvas.width/2, img.height + pad + 55);

                let link = document.createElement("a");
                link.download = currentQrFilename.replace('.svg', '.jpg');
                link.href = canvas.toDataURL("image/jpeg", 1.0);
                link.click();

                btn.innerText = originalText;
                btn.disabled = false;
            };
            img.src = currentQrUrl;
        }

        function downloadSemuaQRAsJPG() {
            let qrElements = document.querySelectorAll('.btn-lihat-qr');

            let visibleQrs = Array.from(qrElements).filter(el => el.closest('.asset-card').style.display !== 'none');

            if (visibleQrs.length === 0) {
                alert("Belum ada QR Code yang bisa dicetak pada filter saat ini.");
                return;
            }

            let btn = document.getElementById('btn-download-all');
            let originalText = btn.innerHTML;
            btn.innerText = "Menyusun Gambar JPG...";
            btn.disabled = true;

            let cols = 4;
            let itemWidth = 260;
            let itemHeight = 320;
            let padding = 40;
            let rows = Math.ceil(visibleQrs.length / cols);

            let canvas = document.createElement("canvas");

            canvas.width = (cols * itemWidth) + (padding * (cols + 1));

            canvas.height = (rows * itemHeight) + (padding * (rows + 1)) + 100;

            let ctx = canvas.getContext("2d");
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = "#1a4f91";
            ctx.font = "bold 35px Arial";
            ctx.textAlign = "center";
            ctx.fillText("DAFTAR BARCODE ASET INVENTARIS", canvas.width / 2, 70);

            let processedCount = 0;
            let loadedCount = 0;

            function finalizeBulkDownload() {
                if (processedCount !== visibleQrs.length) return;

                if (loadedCount === 0) {
                    alert("Gagal memuat gambar QR Code untuk diunduh.");
                } else {
                    let link = document.createElement("a");
                    link.download = "Semua_Katalog_QR_Aset.jpg";
                    link.href = canvas.toDataURL("image/jpeg", 1.0);
                    link.click();
                }

                btn.innerHTML = originalText;
                btn.disabled = false;
            }

            visibleQrs.forEach((el, index) => {
                let img = new Image();
                img.crossOrigin = "Anonymous";

                img.onload = function() {
                    let col = index % cols;
                    let row = Math.floor(index / cols);

                    let x = padding + (col * (itemWidth + padding));
                    let y = 100 + padding + (row * (itemHeight + padding));

                    ctx.strokeStyle = "#cbd5e1";
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, itemWidth, itemHeight);

                    ctx.drawImage(img, x + 30, y + 20, 200, 200);

                    ctx.fillStyle = "#1a4f91";
                    ctx.textAlign = "center";
                    ctx.font = "bold 16px Arial";
                    ctx.fillText(el.getAttribute('data-kode'), x + (itemWidth / 2), y + 255);

                    ctx.fillStyle = "#475569";
                    ctx.font = "14px Arial";
                    ctx.fillText(el.getAttribute('data-nama'), x + (itemWidth / 2), y + 280);
                    ctx.font = "13px Arial";
                    ctx.fillText(el.getAttribute('data-seri'), x + (itemWidth / 2), y + 300);

                    loadedCount++;
                    processedCount++;
                    finalizeBulkDownload();
                };

                img.onerror = function() {
                    let col = index % cols;
                    let row = Math.floor(index / cols);
                    let x = padding + (col * (itemWidth + padding));
                    let y = 100 + padding + (row * (itemHeight + padding));

                    ctx.strokeStyle = "#fca5a5";
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, itemWidth, itemHeight);

                    ctx.fillStyle = "#dc2626";
                    ctx.textAlign = "center";
                    ctx.font = "bold 14px Arial";
                    ctx.fillText("QR Gagal Dimuat", x + (itemWidth / 2), y + 125);

                    ctx.fillStyle = "#1a4f91";
                    ctx.font = "bold 16px Arial";
                    ctx.fillText(el.getAttribute('data-kode'), x + (itemWidth / 2), y + 255);

                    processedCount++;
                    finalizeBulkDownload();
                };

                img.src = el.getAttribute('data-url');
            });
        }

        function prosesGenerateSemua() {
            if(!confirm('Apakah Anda yakin ingin memproses/update seluruh QR Code yang belum terbuat? Ini mungkin memakan waktu beberapa detik.')) return;

            document.getElementById('modalLoadingGenerate').classList.add('show');

            fetch(`/masteradmin/generate-qr-all`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfTokenBarcode, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalLoadingGenerate').classList.remove('show');
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(err => {
                document.getElementById('modalLoadingGenerate').classList.remove('show');
                alert('Terjadi kesalahan fatal pada jaringan/server.');
            });
        }

        function prosesUpdateSemua() {
            if(!confirm('Apakah Anda yakin ingin mengecek dan memperbarui seluruh QR Code yang usang/berubah? Ini mungkin memakan waktu.')) return;

            let loadingModal = document.getElementById('modalLoadingGenerate');
            loadingModal.querySelector('h3').innerText = 'Memperbarui QR Code...';
            loadingModal.querySelector('p').innerText = 'Sistem sedang memindai aset yang berubah dan memperbarui QR Code.';
            loadingModal.classList.add('show');

            fetch(`/masteradmin/generate-qr-all`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfTokenBarcode, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                loadingModal.classList.remove('show');
                if (data.success) {
                    alert('Proses Update Selesai! ' + data.message);
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(err => {
                loadingModal.classList.remove('show');
                alert('Terjadi kesalahan fatal pada jaringan/server.');
            });
        }
    </script>

    <script>
        (function () {
            function sesuaikanPaddingMainDenganHeaderFixed() {
                const mainEl = document.querySelector('main');
                const headerEl = document.getElementById('navbar-barcode-fixed') || (mainEl ? mainEl.querySelector('.main-header') : document.querySelector('.main-header'));
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
                    const headerEl = document.getElementById('navbar-barcode-fixed') || document.querySelector('.main-header');
                    if (headerEl) {
                        new ResizeObserver(sesuaikanPaddingMainDenganHeaderFixed).observe(headerEl);
                    }
                });
            }

            function terapkanGayaKartuDinamis(rootEl) {
                const target = rootEl || document;
                target.querySelectorAll('[data-bg]').forEach(function(el) {
                    el.style.backgroundColor = el.getAttribute('data-bg');
                });
                target.querySelectorAll('[data-border-top]').forEach(function(el) {
                    el.style.borderTop = '5px solid ' + el.getAttribute('data-border-top');
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const inputSearch = document.getElementById('input-search-barcode');
                const selectFilterTipe = document.getElementById('filterStatusBerjalan');
                const formEl = document.getElementById('form-filter-barcode');
                const cardBoxEl = document.querySelector('.card-box');
                const gridEl = cardBoxEl ? cardBoxEl.querySelector('.asset-grid') : null;
                let pagEl = cardBoxEl ? cardBoxEl.querySelector('.custom-pagination-wrap') : null;
                const infoGlobalEl = document.getElementById('info-hasil-search-global');
                const rawJsonEl = document.getElementById('json-katalog-semua-qr');
                if (!inputSearch || !cardBoxEl || !gridEl) return;

                let katalogGlobal = [];
                if (rawJsonEl && rawJsonEl.value) {
                    try {
                        katalogGlobal = JSON.parse(rawJsonEl.value) || [];
                    } catch (e) {
                        katalogGlobal = [];
                    }
                }

                let htmlGridAwal = gridEl.innerHTML;
                let displayPagAwal = pagEl ? pagEl.style.display : '';

                function escHtml(str) {
                    return String(str === null || str === undefined ? '' : str)
                        .replace(/&/g, '&amp;')
                        .replace(/\x3c/g, '&lt;')
                        .replace(/\x3e/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function buatHtmlKartuQr(item) {
                    let footerHtml = '';
                    if (item.has_qr) {
                        const btnUpdate = item.up_to_date
                            ? '\x3cbutton type="button" class="btn-qr" style="background: #cbd5e1; color: #64748b; cursor: not-allowed; box-shadow: none;" disabled title="QR sudah versi terbaru"\x3eUpdate QR\x3c/button\x3e'
                            : '\x3cbutton type="button" class="btn-qr" style="background: #f59e0b;" data-id="' + escHtml(item.id) + '" onclick="prosesBuatQR(this.dataset.id, this)" title="Data berubah, klik untuk memperbarui QR"\x3eUpdate QR\x3c/button\x3e';
                        footerHtml =
                            '\x3cbutton type="button" class="btn-qr btn-lihat-qr" style="background: var(--secondary-color);"' +
                            ' data-url="' + escHtml(item.url_qr) + '"' +
                            ' data-kode="' + escHtml(item.kode_aset) + '"' +
                            ' data-seri="' + escHtml(item.nomor_seri) + '"' +
                            ' data-nama="' + escHtml(item.nama) + '"' +
                            ' data-file="' + escHtml(item.file_qr) + '"' +
                            ' onclick="bukaModalQRDariTombol(this)"\x3eLihat QR\x3c/button\x3e' +
                            btnUpdate;
                    } else {
                        footerHtml =
                            '\x3cbutton type="button" class="btn-qr" style="width: 100%; background: var(--secondary-color);"' +
                            ' data-id="' + escHtml(item.id) + '" onclick="prosesBuatQR(this.dataset.id, this)"\x3eBuat Label QR Baru\x3c/button\x3e';
                    }

                    return (
                        '\x3cdiv class="asset-card baris-qr-aset" style="background-color: ' + escHtml(item.card_bg) + '; border-top: 5px solid ' + escHtml(item.warna_bg) + ';" data-search="' + escHtml(item.search) + '"\x3e' +
                            '\x3cdiv class="card-header"\x3e' +
                                '\x3cdiv\x3e' +
                                    '\x3cdiv class="card-title"\x3e' + escHtml(item.nama) + '\x3c/div\x3e' +
                                    '\x3cdiv class="card-subtitle"\x3e' + escHtml(item.kategori) + '\x3c/div\x3e' +
                                '\x3c/div\x3e' +
                                '\x3cspan class="badge" style="background-color: ' + escHtml(item.warna_bg) + '; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"\x3e' + escHtml(item.status_aset) + '\x3c/span\x3e' +
                            '\x3c/div\x3e' +
                            '\x3cdiv class="card-body"\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eKode Aset\x3c/span\x3e\x3cspan class="info-value" style="color: var(--primary-color);"\x3e' + escHtml(item.kode_aset) + '\x3c/span\x3e\x3c/div\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eNomor Seri\x3c/span\x3e\x3cspan class="info-value"\x3e' + escHtml(item.nomor_seri) + '\x3c/span\x3e\x3c/div\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eMerk\x3c/span\x3e\x3cspan class="info-value"\x3e' + escHtml(item.merk) + '\x3c/span\x3e\x3c/div\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eCabang\x3c/span\x3e\x3cspan class="info-value"\x3e' + escHtml(item.cabang) + '\x3c/span\x3e\x3c/div\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eRuangan\x3c/span\x3e\x3cspan class="info-value"\x3e' + escHtml(item.ruangan) + '\x3c/span\x3e\x3c/div\x3e' +
                                '\x3cdiv class="info-row"\x3e\x3cspan class="info-label"\x3eTipe Berjalan\x3c/span\x3e\x3cspan class="label-tipe-berjalan badge" style="background-color: rgba(0,0,0,0.05); color: #1e293b; border: 1px solid rgba(0,0,0,0.1);"\x3e' + escHtml(item.status_berjalan) + '\x3c/span\x3e\x3c/div\x3e' +
                            '\x3c/div\x3e' +
                            '\x3cdiv class="card-footer"\x3e' + footerHtml + '\x3c/div\x3e' +
                        '\x3c/div\x3e'
                    );
                }

                function jalankanPencarianSemuaHalaman() {
                    const keyword = (inputSearch.value || '').trim().toLowerCase();
                    const filterTipe = selectFilterTipe ? (selectFilterTipe.value || 'Semua') : 'Semua';
                    const words = keyword.split(/\s+/).filter(Boolean);

                    if (words.length === 0) {
                        gridEl.innerHTML = htmlGridAwal;
                        terapkanGayaKartuDinamis(gridEl);
                        if (pagEl) pagEl.style.display = displayPagAwal;
                        if (infoGlobalEl) infoGlobalEl.style.display = 'none';
                        return;
                    }

                    const hasilCocok = katalogGlobal.filter(function (item) {
                        if (filterTipe && filterTipe !== 'Semua') {
                            if (String(item.status_berjalan || '').toLowerCase() !== filterTipe.toLowerCase()) {
                                return false;
                            }
                        }
                        const haystack = String(item.search || '');
                        return words.every(function (w) {
                            return haystack.indexOf(w) !== -1;
                        });
                    });

                    if (pagEl) pagEl.style.display = 'none';

                    if (infoGlobalEl) {
                        infoGlobalEl.textContent = 'Menampilkan ' + hasilCocok.length + ' dari ' + katalogGlobal.length + ' aset (Semua Halaman)';
                        infoGlobalEl.style.display = 'inline-block';
                    }

                    if (hasilCocok.length === 0) {
                        gridEl.innerHTML =
                            '\x3cdiv style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 45px 20px; background: #f8fafc; border-radius: 16px; border: 2px dashed #cbd5e1;"\x3e' +
                                '\x3cp style="font-size: 15px; font-weight: 700; color: #334155; margin-bottom: 4px;"\x3eTidak ditemukan aset untuk pencarian "' + escHtml(inputSearch.value.trim()) + '"\x3c/p\x3e' +
                                '\x3cp style="font-size: 13px;"\x3ePencarian telah dilakukan ke seluruh ' + katalogGlobal.length + ' aset di semua halaman paginasi.\x3c/p\x3e' +
                            '\x3c/div\x3e';
                    } else {
                        gridEl.innerHTML = hasilCocok.map(buatHtmlKartuQr).join('');
                        terapkanGayaKartuDinamis(gridEl);
                    }
                }

                window.__refreshBarcodeLive = function (freshDoc) {
                    if (!freshDoc) return false;
                    const modalQr = document.getElementById('modalQR');
                    const modalLoad = document.getElementById('modalLoadingGenerate');
                    if ((modalQr && modalQr.classList.contains('show')) || (modalLoad && modalLoad.classList.contains('show'))) {
                        return false;
                    }

                    const newRawJson = freshDoc.getElementById('json-katalog-semua-qr');
                    if (newRawJson) {
                        if (rawJsonEl) rawJsonEl.value = newRawJson.value;
                        try {
                            katalogGlobal = JSON.parse(newRawJson.value || '[]') || [];
                        } catch (e) {}
                    }

                    const newGrid = freshDoc.querySelector('.card-box .asset-grid');
                    if (newGrid) {
                        htmlGridAwal = newGrid.innerHTML;
                    }

                    const newPag = freshDoc.querySelector('.card-box .custom-pagination-wrap');
                    if (pagEl && newPag) {
                        pagEl.innerHTML = newPag.innerHTML;
                    } else if (pagEl && !newPag) {
                        pagEl.remove();
                        pagEl = null;
                    } else if (!pagEl && newPag) {
                        pagEl = newPag.cloneNode(true);
                        cardBoxEl.appendChild(pagEl);
                    }
                    displayPagAwal = pagEl ? pagEl.style.display : '';

                    if ((inputSearch.value || '').trim() !== '') {
                        jalankanPencarianSemuaHalaman();
                    } else {
                        gridEl.innerHTML = htmlGridAwal;
                        terapkanGayaKartuDinamis(gridEl);
                        if (pagEl) pagEl.style.display = displayPagAwal;
                        if (infoGlobalEl) infoGlobalEl.style.display = 'none';
                    }
                    return true;
                };

                inputSearch.addEventListener('input', jalankanPencarianSemuaHalaman);
                if (formEl) {
                    formEl.addEventListener('submit', function (e) {
                        const kw = (inputSearch.value || '').trim();
                        if (kw !== '' && katalogGlobal.length > 0) {
                            e.preventDefault();
                            jalankanPencarianSemuaHalaman();
                        }
                    });
                }

                if ((inputSearch.value || '').trim() !== '') {
                    jalankanPencarianSemuaHalaman();
                }
            });
        })();
    </script>

</body>

</html>
