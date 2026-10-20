<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Aset - Master Admin</title>
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

        /* GRID CARD GALLERY */
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

        /* MODAL */
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
    </style>
</head>

<body>

    @include('layouts.mastersidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Barcode & QR Aset</h1>
                <p>Pusat pembuatan dan cetak label QR Code aset fisik.</p>
            </div>
            
            <!-- TOMBOL CETAK SEMUA QR -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                
                <button type="button" class="btn-qr" style="background: var(--secondary-color); padding: 10px 18px;" onclick="prosesGenerateSemua()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 8v6h6"></path></svg>
                    Generate Semua
                </button>
                
                <!-- TOMBOL UPDATE SEMUA (BARU) -->
                <button type="button" class="btn-qr" style="background: #f59e0b; padding: 10px 18px;" onclick="prosesUpdateSemua()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Update Semua
                </button>

                <button type="button" class="btn-qr" style="background: var(--primary-color); padding: 10px 18px;" onclick="downloadSemuaQRAsJPG()" id="btn-download-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Semua QR
                </button>
                
            </div>

            
        </header>

        <div class="card-box">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <form action="{{ url()->current() }}" method="GET" style="margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
                
                <!-- Kiri: Kotak Pencarian -->
                <div style="display: flex; gap: 10px; flex: 1; min-width: 300px;">
                    <div style="position: relative; flex: 1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8;">
                            <circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aset, kode qr, seri, merk, status..." autocomplete="off" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 14px; outline: none; background: #f8fafc; font-weight: 500;">
                    </div>
                    <button type="submit" class="btn-qr" style="padding: 12px 20px; font-size: 14px; background: var(--primary-color);">Cari QR</button>
                    
                    @if(request('search') || (request('filter_tipe') && request('filter_tipe') != 'Semua'))
                        <a href="{{ url()->current() }}" class="btn-qr" style="background: #fee2e2; color: #ef4444; border: none; text-decoration: none; padding: 12px 20px; font-size: 14px; box-shadow: none;">Reset</a>
                    @endif
                </div>

                <!-- Kanan: Dropdown Filter -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="filterStatusBerjalan" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Filter Tipe:</label>
                    <select name="filter_tipe" id="filterStatusBerjalan" onchange="this.form.submit()" style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; font-weight: 600; outline: none; cursor: pointer; background: #f8fafc;">
                        <option value="Semua" {{ request('filter_tipe') == 'Semua' ? 'selected' : '' }}>Tampilkan Semua</option>
                        <option value="Aset Berjalan" {{ request('filter_tipe') == 'Aset Berjalan' ? 'selected' : '' }}>Aset Berjalan Saja</option>
                        <option value="Aset Tidak Berjalan" {{ request('filter_tipe') == 'Aset Tidak Berjalan' ? 'selected' : '' }}>Aset Tidak Berjalan Saja</option>
                    </select>
                </div>
            </form>

            <h3 style="font-size: 16px; color: var(--primary-color); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">Galeri Label QR Aset Fisik</h3>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="filterStatusBerjalan" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Filter Tipe:</label>
                    <select id="filterStatusBerjalan" onchange="filterGaleriQR()" style="padding: 8px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; font-weight: 600; outline: none; cursor: pointer; background: #f8fafc;">
                        <option value="Semua">Tampilkan Semua</option>
                        <option value="Aset Berjalan">Aset Berjalan Saja</option>
                        <option value="Aset Tidak Berjalan">Aset Tidak Berjalan Saja</option>
                    </select>
                </div>
            </div>

            <div class="asset-grid">
                @forelse($detailAsets ?? [] as $detail)
               @php
                    $warnaBg = '#94a3b8'; 
                    if(isset($statuses)) {
                        $match = $statuses->where('nama_status', $detail->status_aset)->first();
                        if($match && !empty($match->warna_status)) {
                            $warnaBg = $match->warna_status;
                        }
                    }
                    
                    $hex = ltrim($warnaBg, '#');
                    $cardBg = 'rgba(148, 163, 184, 0.08)'; 
                    if (strlen($hex) == 6) {
                        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                        $cardBg = "rgba($r, $g, $b, 0.08)";
                    } elseif (strlen($hex) == 3) {
                        $r = hexdec(str_repeat(substr($hex, 0, 1), 2)); $g = hexdec(str_repeat(substr($hex, 1, 1), 2)); $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
                        $cardBg = "rgba($r, $g, $b, 0.08)";
                    }
                @endphp
                
                <div class="asset-card baris-qr-aset" style="background-color: {{ $cardBg }}; border-top: 5px solid {{$warnaBg }};">
                    <div class="card-header">
                        <div>
                            <div class="card-title">{{ $detail->item->nama_aset ?? 'Tanpa Nama' }}</div>
                            <div class="card-subtitle">{{ $detail->kategori->nama_kategori ?? $detail->kategori ?? '-' }}</div>
                        </div>
                        <span class="badge" style="background-color: {{ $warnaBg }}; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            {{ $detail->status_aset ?? 'N/A' }}
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Kode Aset</span>
                            <span class="info-value" style="color: var(--primary-color);">{{ $detail->kode_aset ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nomor Seri</span>
                            <span class="info-value">{{ $detail->nomor_seri ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Merk</span>
                            <span class="info-value">{{ $detail->merk ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cabang</span>
                            <span class="info-value">{{ $detail->cabang->nama_cabang ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tipe Berjalan</span>
                            <span class="label-tipe-berjalan badge" style="background-color: rgba(0,0,0,0.05); color: #1e293b; border: 1px solid rgba(0,0,0,0.1);">
                                {{ $detail->status_berjalan ?? 'Tidak Ada' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        @php
                        // Isi QR = KODE ASET saja (harus sama dengan generateAndSaveQr di ItemController)
                        $isiQr = $detail->kode_aset;
                        $hash = md5($isiQr);

                        $expectedFilename = 'qr_aset_' . $detail->aset_id . '_' .$hash . '.svg';
                        $isUpToDate = file_exists(storage_path('app/public/qrcodes/' .$expectedFilename));

                        $oldFiles = glob(storage_path('app/public/qrcodes/qr_aset_' .$detail->aset_id . '_*.svg'));
                        $hasAnyQr = count($oldFiles) > 0;

                        $fileToShow = $isUpToDate ? $expectedFilename : ($hasAnyQr ? basename($oldFiles[0]) : null);
                        @endphp

                        @if($hasAnyQr)
                            <!-- Tombol ini ditambahkan class 'btn-lihat-qr' dan data attribute untuk diproses massal oleh JS -->
                            <button type="button" class="btn-qr btn-lihat-qr" style="background: var(--secondary-color);"
                                data-url="{{ asset('storage/qrcodes/' . $fileToShow) }}"
                                data-kode="{{ $detail->kode_aset }}"
                                data-seri="{{ $detail->nomor_seri }}"
                                data-nama="{{ $detail->item->nama_aset ?? 'Aset' }}"
                                onclick="bukaModalQR('{{ asset('storage/qrcodes/' . $fileToShow) }}', '{{ $detail->kode_aset }}', '{{$detail->nomor_seri }}', '{{ $detail->item->nama_aset ?? 'Aset' }}', '{{ $fileToShow }}')">
                                Lihat QR
                            </button>

                            @if($isUpToDate)
                                <button type="button" class="btn-qr" style="background: #cbd5e1; color: #64748b; cursor: not-allowed; box-shadow: none;" disabled title="QR sudah versi terbaru">
                                    Update QR
                                </button>
                            @else
                                <button type="button" class="btn-qr" style="background: #f59e0b;"
                                    onclick="prosesBuatQR('{{ $detail->aset_id }}', this)" title="Data berubah, klik untuk memperbarui QR">
                                    Update QR
                                </button>
                            @endif
                        @else
                            <button type="button" class="btn-qr" style="width: 100%; background: var(--secondary-color);"
                                onclick="prosesBuatQR('{{ $detail->aset_id }}', this)">
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

    <!-- MODAL QR CODE -->
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
                <!-- DIGANTI MENJADI BUTTON UNTUK CONVERT KE JPG -->
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

    <!-- SCRIPT FUNGSI -->
    <script>
        // Variabel global untuk menyimpan data modal aktif
        let currentQrUrl = '';
        let currentQrFilename = '';

        

        function prosesBuatQR(idAset, btnElement) {
            let originalText = btnElement.innerText;
            btnElement.innerText = 'Memproses...';
            btnElement.disabled = true;

            fetch(`/masteradmin/generate-qr/${idAset}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
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

        // =========================================================================
        // FUNGSI KONVERSI SVG KE JPG (SATUAN)
        // =========================================================================
        function downloadQRSingleAsJPG() {
            let btn = document.getElementById('btn-download-qr-single');
            let originalText = btn.innerText;
            btn.innerText = "Memproses JPG...";
            btn.disabled = true;

            let img = new Image();
            img.crossOrigin = "Anonymous";
            img.onload = function() {
                let canvas = document.createElement("canvas");
                let pad = 30;
                canvas.width = img.width + (pad * 2);
                canvas.height = img.height + (pad * 2) + 60; // Ruang ekstra untuk teks
                let ctx = canvas.getContext("2d");

                // Latar Belakang Putih
                ctx.fillStyle = "#ffffff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Gambar QR
                ctx.drawImage(img, pad, pad);

                // Tambahkan Teks Kode Aset
                ctx.fillStyle = "#1a4f91";
                ctx.font = "bold 20px Arial";
                ctx.textAlign = "center";
                ctx.fillText(document.getElementById('qr-teks-kode').innerText, canvas.width/2, img.height + pad + 30);

                // Tambahkan Teks Seri
                ctx.fillStyle = "#475569";
                ctx.font = "16px Arial";
                ctx.fillText(document.getElementById('qr-teks-seri').innerText, canvas.width/2, img.height + pad + 55);

                // Eksekusi Download
                let link = document.createElement("a");
                link.download = currentQrFilename.replace('.svg', '.jpg');
                link.href = canvas.toDataURL("image/jpeg", 1.0);
                link.click();

                btn.innerText = originalText;
                btn.disabled = false;
            };
            img.src = currentQrUrl;
        }

        // =========================================================================
        // FUNGSI KONVERSI SVG KE JPG (MASAL / GABUNG JADI 1 HALAMAN)
        // =========================================================================
        function downloadSemuaQRAsJPG() {
            let qrElements = document.querySelectorAll('.btn-lihat-qr');
            
            // Filter hanya yang tampil di layar (jika sedang pakai dropdown filter)
            let visibleQrs = Array.from(qrElements).filter(el => el.closest('.asset-card').style.display !== 'none');

            if (visibleQrs.length === 0) {
                alert("Belum ada QR Code yang bisa dicetak pada filter saat ini.");
                return;
            }

            let btn = document.getElementById('btn-download-all');
            let originalText = btn.innerHTML;
            btn.innerText = "Menyusun Gambar JPG...";
            btn.disabled = true;

            // Atur Layout Grid untuk Kertas Besar
            let cols = 4; // 4 Kolom QR per baris
            let itemWidth = 260;
            let itemHeight = 320;
            let padding = 40;
            let rows = Math.ceil(visibleQrs.length / cols);

            let canvas = document.createElement("canvas");
            // Hitung ukuran total kanvas
            canvas.width = (cols * itemWidth) + (padding * (cols + 1));
            // Tambah margin atas 100px untuk Judul
            canvas.height = (rows * itemHeight) + (padding * (rows + 1)) + 100; 
            
            let ctx = canvas.getContext("2d");
            ctx.fillStyle = "#ffffff"; // Background Putih Full
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Tulis Judul Dokumen di atas
            ctx.fillStyle = "#1a4f91";
            ctx.font = "bold 35px Arial";
            ctx.textAlign = "center";
            ctx.fillText("DAFTAR BARCODE ASET INVENTARIS", canvas.width / 2, 70);

            let loadedCount = 0;

            visibleQrs.forEach((el, index) => {
                let img = new Image();
                img.crossOrigin = "Anonymous";
                
                img.onload = function() {
                    let col = index % cols;
                    let row = Math.floor(index / cols);

                    // Hitung koordinat X dan Y untuk tiap kotak QR
                    let x = padding + (col * (itemWidth + padding));
                    let y = 100 + padding + (row * (itemHeight + padding));

                    // Gambar Border/Kotak Tipis Pembatas
                    ctx.strokeStyle = "#cbd5e1";
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, itemWidth, itemHeight);

                    // Gambar QR Code di tengah kotak
                    ctx.drawImage(img, x + 30, y + 20, 200, 200);

                    // Gambar Teks Kode Aset
                    ctx.fillStyle = "#1a4f91";
                    ctx.textAlign = "center";
                    ctx.font = "bold 16px Arial";
                    ctx.fillText(el.getAttribute('data-kode'), x + (itemWidth / 2), y + 255);

                    // Gambar Teks Nama Aset & Seri
                    ctx.fillStyle = "#475569";
                    ctx.font = "14px Arial";
                    ctx.fillText(el.getAttribute('data-nama'), x + (itemWidth / 2), y + 280);
                    ctx.font = "13px Arial";
                    ctx.fillText(el.getAttribute('data-seri'), x + (itemWidth / 2), y + 300);

                    loadedCount++;
                    
                    // Jika semua gambar sudah masuk kanvas, langsung download!
                    if (loadedCount === visibleQrs.length) {
                        let link = document.createElement("a");
                        link.download = "Semua_Katalog_QR_Aset.jpg";
                        link.href = canvas.toDataURL("image/jpeg", 1.0); // Kualitas Maksimal 1.0
                        link.click();

                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                };
                
                // Trigger load gambar
                img.src = el.getAttribute('data-url');
            });
        }

        function prosesGenerateSemua() {
            if(!confirm('Apakah Anda yakin ingin memproses/update seluruh QR Code yang belum terbuat? Ini mungkin memakan waktu beberapa detik.')) return;

            // Tampilkan Layar Loading
            document.getElementById('modalLoadingGenerate').classList.add('show');

            fetch(`/generate-qr-all`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
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

            // Tampilkan Layar Loading dan ubah teksnya agar sesuai dengan konteks "Update"
            let loadingModal = document.getElementById('modalLoadingGenerate');
            loadingModal.querySelector('h3').innerText = 'Memperbarui QR Code...';
            loadingModal.querySelector('p').innerText = 'Sistem sedang memindai aset yang berubah dan memperbarui QR Code.';
            loadingModal.classList.add('show');

            fetch(`/generate-qr-all`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
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
</body>

</html>