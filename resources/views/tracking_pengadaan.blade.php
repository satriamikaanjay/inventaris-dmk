<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Pengadaan Aset - Sistem {{ $isMasterAdmin ? 'Master Admin' : 'Admin' }}</title>
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
            --text-muted: rgba(0, 0, 0, 0.5);
            --border-color: rgba(0, 0, 0, 0.14);
            --sidebar-width: 270px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); display: flex; min-height: 100vh; overflow-x: hidden; color: var(--text-color); }
        main { margin-left: var(--sidebar-width); padding: 25px; width: calc(100% - var(--sidebar-width)); display: flex; flex-direction: column; gap: 20px; }

        .main-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 15px; flex-wrap: wrap; gap: 12px; }
        .header-title h1 { font-size: 24px; font-weight: 800; color: var(--primary-color); }
        .header-title p { font-size: 13px; color: var(--text-muted); font-weight: 500; margin-top: 5px; }

        .btn { padding: 9px 15px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); background: #fff; color: var(--text-color); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
        .btn:hover { background: #f1f5f9; }
        .btn-primary { background: var(--primary-color); color: #fff; border: none; }
        .btn-primary:hover { opacity: 0.92; background: var(--primary-color); }
        .btn-secondary { background: var(--secondary-color); color: #fff; border: none; }
        .btn-secondary:hover { opacity: 0.92; background: var(--secondary-color); }
        .btn-sm { padding: 6px 11px; font-size: 12px; border-radius: 6px; }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .kpi-card { background: #fff; padding: 16px 18px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 3px 6px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 6px; border-left: 4px solid var(--primary-color); }
        .kpi-card.green { border-left-color: var(--secondary-color); }
        .kpi-card.amber { border-left-color: var(--warning-color); }
        .kpi-card.indigo { border-left-color: #6366f1; }
        .kpi-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; }
        .kpi-val { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .kpi-sub { font-size: 12px; color: #64748b; font-weight: 500; }

        .card-box { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.04); border: 1px solid var(--border-color); }
        .section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .section-head h2 { font-size: 16px; font-weight: 700; color: #0f172a; }
        .section-head p { font-size: 12px; color: #64748b; margin-top: 2px; }

        .filter-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 11.5px; font-weight: 700; color: #334155; }
        .form-group input, .form-group select { padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; color: #0f172a; outline: none; font-family: inherit; width: 100%; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1); }

        .filter-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; align-items: center; justify-content: space-between; }
        .preset-pills { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .pill-btn { padding: 5px 11px; border-radius: 999px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; font-size: 11.5px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.15s; }
        .pill-btn:hover { background: #e2e8f0; border-color: #94a3b8; }
        .pill-btn.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

        .table-container { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .data-table thead th { background: #f8fafc; padding: 11px 12px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .data-table tbody td { padding: 11px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table tbody tr:hover { background: #f8fafc; }
        .data-table tbody tr.row-selected { background: #eff6ff; box-shadow: inset 3px 0 0 var(--primary-color); }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
        .badge-date-code { background: #dbeafe; color: #1e40af; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.3px; }
        .badge-count { background: #dcfce7; color: #166534; font-size: 12.5px; padding: 4px 10px; }
        .badge-chip { background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; display: inline-block; margin: 2px 3px 2px 0; }
        .badge-kat { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .code-seg-date { background: #dbeafe; color: #1e3a8a; font-weight: 800; padding: 1px 5px; border-radius: 4px; }
        .code-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; font-weight: 600; color: #0f172a; white-space: nowrap; }

        .custom-pagination-wrap nav > div:first-child { display: none !important; }
        .custom-pagination-wrap nav > div:last-child { display: flex !important; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; width: 100%; margin-top: 14px; }
        .custom-pagination-wrap nav p { font-size: 13px !important; color: var(--text-muted) !important; margin: 0 !important; }
        .custom-pagination-wrap nav p span { font-weight: 800 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav > div:last-child > div:last-child > span { display: inline-flex !important; border-radius: 8px !important; border: 1px solid var(--border-color) !important; background: #fff !important; overflow: hidden !important; }
        .custom-pagination-wrap nav span > a, .custom-pagination-wrap nav span > span { display: inline-flex !important; align-items: center !important; justify-content: center !important; min-width: 36px !important; padding: 7px 11px !important; font-size: 13px !important; font-weight: 600 !important; color: #475569 !important; text-decoration: none !important; background: #fff !important; border-right: 1px solid var(--border-color) !important; margin: 0 !important; border-top: none !important; border-bottom: none !important; border-left: none !important; }
        .custom-pagination-wrap nav span > *:last-child { border-right: none !important; }
        .custom-pagination-wrap nav span > a:hover { background: #f1f5f9 !important; color: var(--primary-color) !important; }
        .custom-pagination-wrap nav span[aria-current="page"] > span { background: var(--primary-color) !important; color: #fff !important; }
        .custom-pagination-wrap nav span[aria-disabled="true"] > span { color: #cbd5e1 !important; background: #f8fafc !important; }
        .custom-pagination-wrap nav svg { width: 15px !important; height: 15px !important; }

        @media (max-width: 1100px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            main { margin-left: 0; width: 100%; padding: 62px 14px 20px 14px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    @if($isMasterAdmin)
        @include('layouts.mastersidebar')
    @else
        @include('layouts.adminsidebar')
    @endif

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Tracking Pengadaan Aset</h1>
                <p>Lacak jumlah pengadaan &amp; penambahan aset per tanggal berdasarkan tanggal pada kode aset (<code>DDMMYYYY</code>).</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <a href="{{ route($routeName, array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-secondary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Unduh Rekap CSV</span>
                </a>
                <a href="{{ route($isMasterAdmin ? 'aset.index' : 'admin.aset.index') }}" class="btn">
                    <span>Ke Data Aset</span>
                </a>
            </div>
        </header>

        <section class="kpi-grid">
            <div class="kpi-card">
                <span class="kpi-label">Total Pengadaan (Terfilter)</span>
                <span class="kpi-val">{{ number_format($ringkasan['total_aset'], 0, ',', '.') }} Unit</span>
                <span class="kpi-sub">Total Nilai: Rp {{ number_format($ringkasan['total_nilai'], 0, ',', '.') }}</span>
            </div>
            <div class="kpi-card green">
                <span class="kpi-label">Jumlah Tanggal Pengadaan</span>
                <span class="kpi-val">{{ number_format($ringkasan['jumlah_tanggal'], 0, ',', '.') }} Tanggal</span>
                <span class="kpi-sub">Tercatat pada kode aset</span>
            </div>
            <div class="kpi-card amber">
                <span class="kpi-label">Pengadaan Terbanyak</span>
                @if(!empty($ringkasan['tanggal_terbanyak']))
                    <span class="kpi-val">{{ $ringkasan['tanggal_terbanyak']['jumlah'] }} Unit</span>
                    <span class="kpi-sub">{{ $ringkasan['tanggal_terbanyak']['label'] }} ({{ $ringkasan['tanggal_terbanyak']['dmy'] }})</span>
                @else
                    <span class="kpi-val">-</span>
                    <span class="kpi-sub">Belum ada data</span>
                @endif
            </div>
            <div class="kpi-card indigo">
                <span class="kpi-label">Pengadaan Terbaru</span>
                @if(!empty($ringkasan['tanggal_terbaru']))
                    <span class="kpi-val">{{ $ringkasan['tanggal_terbaru']['jumlah'] }} Unit</span>
                    <span class="kpi-sub">{{ $ringkasan['tanggal_terbaru']['label'] }} ({{ $ringkasan['tanggal_terbaru']['dmy'] }})</span>
                @else
                    <span class="kpi-val">-</span>
                    <span class="kpi-sub">Belum ada data</span>
                @endif
            </div>
        </section>

        <section class="card-box">
            <form method="GET" action="{{ route($routeName) }}" id="form-filter-tracking">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="inp-tanggal">Tanggal Spesifik</label>
                        <input type="date" id="inp-tanggal" name="tanggal" value="{{ $filter['tanggal'] }}">
                    </div>
                    <div class="form-group">
                        <label for="inp-dari">Dari Tanggal</label>
                        <input type="date" id="inp-dari" name="dari" value="{{ $filter['dari'] }}">
                    </div>
                    <div class="form-group">
                        <label for="inp-sampai">Sampai Tanggal</label>
                        <input type="date" id="inp-sampai" name="sampai" value="{{ $filter['sampai'] }}">
                    </div>
                    <div class="form-group">
                        <label for="inp-cabang">Cabang</label>
                        <select id="inp-cabang" name="cabang_id">
                            <option value="">-- Semua Cabang --</option>
                            @foreach($cabangs as $cab)
                                <option value="{{ $cab->id }}" {{ $filter['cabang_id'] === (string) $cab->id ? 'selected' : '' }}>{{ $cab->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inp-kategori">Kategori</label>
                        <select id="inp-kategori" name="kategori_id">
                            <option value="">-- Semua Kategori --</option>
                            @foreach($kategoris as $kat)
                                <option value="{{ $kat->id }}" {{ $filter['kategori_id'] === (string) $kat->id ? 'selected' : '' }}>{{ $kat->nama_kategori }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inp-cari">Cari Kode / Tgl (cth: 11082026) / Nama</label>
                        <input type="text" id="inp-cari" name="q" value="{{ $filter['q'] }}" placeholder="C.01, 11082026, Meja...">
                    </div>
                </div>

                <div class="filter-actions">
                    <div class="preset-pills">
                        @php
                            $hariIniYmd = date('Y-m-d');
                            $tujuhHariYmd = date('Y-m-d', strtotime('-6 days'));
                            $bulanIniAwalYmd = date('Y-m-01');
                        @endphp
                        <span style="font-size: 12px; font-weight: 700; color: #64748b; margin-right: 4px;">Cepat:</span>
                        <a href="{{ route($routeName, ['tanggal' => $hariIniYmd]) }}" class="pill-btn {{ $filter['tanggal'] === $hariIniYmd ? 'active' : '' }}">Hari Ini</a>
                        <a href="{{ route($routeName, ['dari' => $tujuhHariYmd, 'sampai' => $hariIniYmd]) }}" class="pill-btn {{ ($filter['dari'] === $tujuhHariYmd && $filter['sampai'] === $hariIniYmd) ? 'active' : '' }}">7 Hari Terakhir</a>
                        <a href="{{ route($routeName, ['dari' => $bulanIniAwalYmd, 'sampai' => $hariIniYmd]) }}" class="pill-btn {{ ($filter['dari'] === $bulanIniAwalYmd && $filter['sampai'] === $hariIniYmd) ? 'active' : '' }}">Bulan Ini</a>
                        <a href="{{ route($routeName) }}" class="pill-btn {{ empty(array_filter($filter)) ? 'active' : '' }}">Semua Tanggal</a>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route($routeName) }}" class="btn btn-sm">Reset</a>
                        <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="card-box">
            <div class="section-head">
                <div>
                    <h2>Rekap Jumlah Pengadaan per Tanggal</h2>
                    <p>Klik <strong>Lihat Detail</strong> pada salah satu tanggal untuk melihat daftar aset apa saja yang ditambahkan di tanggal tersebut.</p>
                </div>
                <span class="badge badge-chip">{{ count($rekapTanggal) }} Tanggal Ditemukan</span>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th>TANGGAL PENGADAAN</th>
                            <th>JUMLAH PENGADAAN</th>
                            <th>RINCIAN ASET &amp; KODE</th>
                            <th>KATEGORI</th>
                            <th>CABANG</th>
                            <th>TOTAL NILAI BELI</th>
                            <th style="text-align: center; width: 150px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapTanggal as $idx => $rekap)
                            @php
                                $isSelectedDate = $filter['detail_tanggal'] !== '' && $filter['detail_tanggal'] === $rekap['key'];
                                $urlDetailTanggal = route($routeName, array_merge(request()->except('page', 'export'), ['detail_tanggal' => $rekap['key']])) . '#rincian-pengadaan';
                                $topItems = array_slice($rekap['per_item'], 0, 5, true);
                                $sisaItems = count($rekap['per_item']) - count($topItems);
                            @endphp
                            <tr class="{{ $isSelectedDate ? 'row-selected' : '' }}">
                                <td style="text-align: center; font-weight: 600; color: #64748b;">{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">{{ $rekap['label_tanggal'] }}</div>
                                    <span class="badge badge-date-code" style="margin-top: 4px;">Kode: {{ $rekap['dmy'] }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-count">{{ number_format($rekap['jumlah_aset'], 0, ',', '.') }} Unit / Aset</span>
                                </td>
                                <td style="max-width: 310px;">
                                    @foreach($topItems as $itemNama => $itemJml)
                                        <span class="badge-chip">{{ $itemNama }}: <strong>{{ $itemJml }}</strong></span>
                                    @endforeach
                                    @if($sisaItems > 0)
                                        <span class="badge-chip" style="background: #fff; color: #64748b;">+{{ $sisaItems }} jenis lain</span>
                                    @endif
                                </td>
                                <td style="max-width: 240px;">
                                    @foreach($rekap['per_kategori'] as $katNama => $katJml)
                                        <span class="badge-chip badge-kat">{{ $katNama }}: <strong>{{ $katJml }}</strong></span>
                                    @endforeach
                                </td>
                                <td style="max-width: 220px;">
                                    @foreach($rekap['per_cabang'] as $cabNama => $cabJml)
                                        <span class="badge-chip">{{ $cabNama }}: <strong>{{ $cabJml }}</strong></span>
                                    @endforeach
                                </td>
                                <td style="font-weight: 600; white-space: nowrap;">
                                    Rp {{ number_format($rekap['total_nilai'], 0, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    @if($isSelectedDate)
                                        <a href="{{ route($routeName, request()->except('detail_tanggal', 'page', 'export')) }}#rincian-pengadaan" class="btn btn-sm" style="background: #e2e8f0; color: #0f172a;">
                                            Sedang Dipilih
                                        </a>
                                    @else
                                        <a href="{{ $urlDetailTanggal }}" class="btn btn-primary btn-sm">
                                            Lihat Detail ({{ $rekap['jumlah_aset'] }})
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">
                                    Tidak ada data pengadaan aset yang sesuai dengan filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card-box" id="rincian-pengadaan">
            <div class="section-head">
                <div>
                    <h2>Rincian Aset Hasil Pengadaan</h2>
                    <p>Menampilkan: <strong>{{ $labelDetailAktif }}</strong> ({{ $detailPaginated->total() }} aset)</p>
                </div>
                @if($filter['detail_tanggal'] !== '')
                    <a href="{{ route($routeName, request()->except('detail_tanggal', 'page', 'export')) }}#rincian-pengadaan" class="btn btn-sm">
                        Tampilkan Semua Tanggal
                    </a>
                @endif
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>KODE ASET</th>
                            <th>TANGGAL (KODE)</th>
                            <th>NAMA ASET</th>
                            <th>KATEGORI</th>
                            <th>CABANG</th>
                            <th>RUANGAN</th>
                            <th>MERK</th>
                            <th>HARGA BELI</th>
                            <th>UNIT CABANG SAAT INI</th>
                            <th>NO. BERITA ACARA</th>
                            <th>STATUS ASET</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($detailPaginated as $row)
                            @php
                                $statusBg = $statusWarnaMap[$row['status_aset']] ?? '#64748b';
                                $bagianKode = explode('/', $row['kode_aset']);
                            @endphp
                            <tr>
                                <td>
                                    <span class="code-mono">
                                        @if(count($bagianKode) >= 2 && $row['dmy'] !== '-')
                                            {{ $bagianKode[0] }}/<span class="code-seg-date">{{ $bagianKode[1] }}</span>@if(count($bagianKode) > 2)/{{ implode('/', array_slice($bagianKode, 2)) }}@endif
                                        @else
                                            {{ $row['kode_aset'] }}
                                        @endif
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div style="font-weight: 600;">{{ $row['label_tanggal'] }}</div>
                                    <div style="font-size: 11px; color: #64748b;">{{ $row['dmy'] }}</div>
                                </td>
                                <td style="font-weight: 600;">{{ $row['nama_aset'] }}</td>
                                <td>{{ $row['nama_kategori'] }}</td>
                                <td style="font-weight: 600;">{{ $row['nama_cabang'] }}</td>
                                <td>{{ $row['ruangan'] }}</td>
                                <td>{{ $row['merk'] }}</td>
                                <td style="white-space: nowrap;">{{ $row['harga_beli'] > 0 ? 'Rp ' . number_format($row['harga_beli'], 0, ',', '.') : '-' }}</td>
                                <td style="text-align: center;">
                                    <span class="badge-chip" title="Total unit aset ini yang tersimpan di cabang {{ $row['nama_cabang'] }} saat ini">{{ $row['unit_cabang'] }}</span>
                                </td>
                                <td>{{ $row['no_berita_acara'] }}</td>
                                <td>
                                    <span class="badge" data-bg="{{ $statusBg }}" style="color: #fff;">{{ $row['status_aset'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 28px; color: var(--text-muted);">
                                    Tidak ada rincian aset untuk tanggal/filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="custom-pagination-wrap">
                {{ $detailPaginated->fragment('rincian-pengadaan')->links() }}
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-bg]').forEach(function (el) {
            el.style.backgroundColor = el.getAttribute('data-bg');
        });
    </script>
</body>

</html>
