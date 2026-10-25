<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Admin</title>
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

        /* ===== NOTIFIKASI KARTU (Hanya Informasi) ===== */
        .notif-ringkas { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
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

        .notif-filter { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 18px; }
        .notif-filter .cari-wrap { position: relative; flex: 1 1 250px; max-width: 400px; }
        .notif-filter input[type="text"] { width: 100%; padding: 9px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; outline: none; }
        .notif-filter input[type="text"]:focus, .notif-filter select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1); }
        .notif-filter select { padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 13px; outline: none; cursor: pointer; background: #fff; }
        .notif-filter .reset { font-size: 13px; color: #dc2626; font-weight: 600; text-decoration: none; }

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

        /* PAGINASI */
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
    </style>
</head>

<body>

    @include('layouts.adminsidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Pusat Notifikasi Aset</h1>
                <p>Memantau aset yang mendekati jadwal servis atau yang sedang berstatus rusak.</p>
            </div>
        </header>

        @php
            $paramDasar = array_filter(['q' => request('q'), 'cabang' => request('cabang')], fn ($v) => $v !== null && $v !== '');
            $adaFilter  = $tipe !== 'semua' || !empty($paramDasar);
        @endphp

        <!-- Ringkasan Kartu (Sekarang hanya Keterangan / Tidak bisa diklik) -->
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
        </div>

        <!-- Area Kontrol Filter -->
        <form method="GET" action="{{ route('notifikasi.index') }}" class="notif-filter">
            
            <!-- 1. Kolom Pencarian -->
            <div class="cari-wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama aset, merk, ruangan..." autocomplete="off">
            </div>

            <!-- 2. Dropdown Filter Tipe Peringatan -->
            <select name="tipe" onchange="this.form.submit()">
                <option value="semua" {{ $tipe === 'semua' ? 'selected' : '' }}>Semua Jenis Peringatan</option>
                <option value="rusak" {{ $tipe === 'rusak' ? 'selected' : '' }}>Hanya Aset Rusak</option>
                <option value="terlewat" {{ $tipe === 'terlewat' ? 'selected' : '' }}>Hanya Terlewat Servis</option>
                <option value="waktunya" {{ $tipe === 'waktunya' ? 'selected' : '' }}>Hanya Servis Mendesak</option>
                <option value="mendekati" {{ $tipe === 'mendekati' ? 'selected' : '' }}>Hanya Mendekati Servis</option>
            </select>

            <!-- 3. Dropdown Filter Cabang -->
            <select name="cabang" onchange="this.form.submit()">
                <option value="">Semua Cabang</option>
                @foreach($cabangs as $cabang)
                    <option value="{{ $cabang->getKey() }}" {{ (string) request('cabang') === (string) $cabang->getKey() ? 'selected' : '' }}>{{ $cabang->nama_cabang }}</option>
                @endforeach
            </select>

            <!-- 4. Tombol Cari & Reset -->
            <button type="submit" class="btn btn-primary" style="padding: 9px 18px;">Terapkan</button>
            @if($adaFilter)
                <a href="{{ route('notifikasi.index') }}" class="reset">Reset filter</a>
            @endif
        </form>

        <p class="notif-info">
            Hari ini: {{ $hariIni->format('d/m/Y') }} 
            @if($notifs->total() > 0) &middot; Menampilkan {{ $notifs->firstItem() }}-{{ $notifs->lastItem() }} dari {{ $notifs->total() }} data @endif
        </p>

        <!-- Daftar Notifikasi -->
        <div class="notif-list">
            @forelse($notifs as $aset)
            @php
                // 1. Logika Warna Kerusakan (Menyalin dari Data Aset)
                $warnaRusak = '#ef4444'; // Default merah
                if(isset($statuses)) {
                    $match = $statuses->where('nama_status', $aset->status_aset)->first();
                    if($match && !empty($match->warna_status)) $warnaRusak = $match->warna_status;
                }

                // 2. Logika Warna Servis (Menyalin dari Data Aset)
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

                // 3. Penentuan Tema Background 
                $borderCard = $aset->is_rusak ? $warnaRusak : $warnaServis;
                $hex = ltrim($borderCard, '#');
                $bgCard = 'rgba(26, 79, 145, 0.03)';
                if (strlen($hex) == 6) {
                    $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                    $bgCard = "rgba($r, $g, $b, 0.05)";
                }
            @endphp
            
            <div class="notif-item" style="border-left: 5px solid {{ $borderCard }}; background: {{ $bgCard }};">
                
                <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; min-width: 130px;">
                    @if($aset->is_rusak)
                        <div class="notif-badge" style="background: {{ $warnaRusak }}; border: 1px solid rgba(0,0,0,0.1);" title="Status Kerusakan Aset">
                            {{ strtoupper($aset->status_aset) }}
                        </div>
                    @endif
                    
                    @if($aset->jenis_servis)
                        <div class="notif-badge" style="background: {{ $warnaServis }}; border: 1px solid rgba(0,0,0,0.1);" title="{{ $labelServis }}">
                            {{ $teksServis }}
                        </div>
                    @endif
                </div>

                <div class="notif-isi">
                    <div class="notif-nama">{{ $aset->item->nama_aset ?? 'Aset tidak diketahui' }}</div>
                    <div class="notif-kode">
                        {{ $aset->kode_aset }} 
                        @if($aset->is_rusak) &middot; <span style="color: {{ $warnaRusak }}; font-weight: 700;">{{ $aset->status_aset }}</span> @endif
                        @if($aset->jenis_servis) &middot; <span style="color: {{ $warnaServis }}; font-weight: 700;">{{ $labelServis }}</span> @endif
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
</body>

</html>