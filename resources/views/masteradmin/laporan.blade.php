@php
    /* LAPORAN_RESMI_MASTER_V2 */
    $__userLoginLaporan = \Illuminate\Support\Facades\Auth::user();
    $__roleUserLaporan = $__userLoginLaporan
        ? strtolower(str_replace(' ', '_', trim((string) ($__userLoginLaporan->role ?? ''))))
        : '';
    $__isMasterAdminLaporan = ($__roleUserLaporan === 'master_admin' || $__roleUserLaporan === 'masteradmin');

    if ($__isMasterAdminLaporan) {
        session(['active_sidebar_panel' => 'masteradmin']);
    } else {
        session(['active_sidebar_panel' => 'admin']);
    }

    // Endpoint AJAX untuk mencatat aktivitas cetak laporan (PDF / Excel) ke RiwayatAktivitas
    if (request()->has('_log_cetak_laporan')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        try {
            if (class_exists(\App\Models\RiwayatAktivitas::class) && \Illuminate\Support\Facades\Auth::check()) {
                $formatCetak = strtoupper(trim((string) request('format', 'PDF')));
                $jenisLap = trim((string) request('jenis_label', 'Laporan Peminjaman Aset'));
                $periodeLap = trim((string) request('periode_label', '1 Bulan Terakhir'));

                $logLap = new \App\Models\RiwayatAktivitas();
                $tblLap = $logLap->getTable();
                $dataLogLap = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLap, 'user_id')) {
                    $dataLogLap['user_id'] = \Illuminate\Support\Facades\Auth::id();
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLap, 'aktivitas')) {
                    $dataLogLap['aktivitas'] = "Mencetak {$formatCetak} {$jenisLap} (Periode: {$periodeLap})";
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLap, 'modul')) {
                    $dataLogLap['modul'] = 'Laporan';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblLap, 'status')) {
                    $dataLogLap['status'] = 'Berhasil';
                }
                $logLap->forceFill($dataLogLap)->save();
            }
        } catch (\Throwable $e) {
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }

    // Ambil daftar Cabang dan Kategori dari database untuk dropdown filter
    $daftarCabangLaporan = [];
    $daftarKategoriLaporan = [];

    try {
        if (class_exists(\App\Models\Cabang::class)) {
            $daftarCabangLaporan = \App\Models\Cabang::orderBy('nama_cabang')->get()->map(fn($c) => [
                'id'   => (string) $c->id,
                'nama' => (string) $c->nama_cabang,
            ])->values()->toArray();
        }
    } catch (\Throwable $e) {
    }

    try {
        if (class_exists(\App\Models\Kategori::class)) {
            $daftarKategoriLaporan = \App\Models\Kategori::orderBy('nama_kategori')->get()->map(fn($k) => [
                'id'   => (string) $k->id,
                'kode' => (string) ($k->kode_kategori ?? ''),
                'nama' => (string) $k->nama_kategori,
            ])->values()->toArray();
        }
    } catch (\Throwable $e) {
    }

    // Ambil data transaksi nyata dari database untuk:
    // 1. Peminjaman
    // 2. Pengajuan
    // 3. Pengembalian
    // 4. Peleburan
    // Jika tabel/transaksi dari sisi User belum ada di database, maka hasilnya murni 0 (kosong).
    $dataLaporanDatabase = [
        'peminjaman'   => [],
        'pengajuan'    => [],
        'pengembalian' => [],
        'peleburan'    => [],
    ];

    try {
        // Peta lookup DetailAset agar jika ada tabel transaksi (peminjamans, pengajuans, dll) bisa dilengkapi detail asetnya
        $asetMapById = [];
        $semuaDetailAset = [];
        if (class_exists(\App\Models\DetailAset::class)) {
            $semuaDetailAset = \App\Models\DetailAset::with(['item', 'kategori', 'cabang'])
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get();
            foreach ($semuaDetailAset as $da) {
                $keyId = (string) ($da->aset_id ?? $da->id ?? '');
                if ($keyId !== '') {
                    $asetMapById[$keyId] = $da;
                }
            }
        }

        // Helper konversi baris tabel transaksi khusus (jika tabel sudah dibuat di DB)
        $ambilDariTabelTransaksi = function (array $namaTabelKandidat, string $jenisFilter = '') use ($asetMapById) {
            $hasilBaris = [];
            foreach ($namaTabelKandidat as $namaTbl) {
                if (\Illuminate\Support\Facades\Schema::hasTable($namaTbl)) {
                    $cols = \Illuminate\Support\Facades\Schema::getColumnListing($namaTbl);
                    $q = \Illuminate\Support\Facades\DB::table($namaTbl);
                    if ($jenisFilter !== '' && in_array('jenis', $cols, true)) {
                        $q->where('jenis', 'like', '%' . $jenisFilter . '%');
                    } elseif ($jenisFilter !== '' && in_array('jenis_pengajuan', $cols, true)) {
                        $q->where('jenis_pengajuan', 'like', '%' . $jenisFilter . '%');
                    }
                    $rows = $q->orderByDesc(in_array('id', $cols, true) ? 'id' : $cols[0])->get();
                    foreach ($rows as $idx => $r) {
                        $asetIdRef = (string) ($r->detail_aset_id ?? $r->aset_id ?? '');
                        $asetObj = $asetMapById[$asetIdRef] ?? null;

                        $tglRaw = (string) ($r->tanggal_pinjam ?? $r->tanggal_pengajuan ?? $r->tanggal_kembali ?? $r->tanggal_peleburan ?? $r->tanggal ?? $r->created_at ?? '');
                        $tglYmd = $tglRaw !== '' ? substr($tglRaw, 0, 10) : '';

                        $hasilBaris[] = [
                            'id'               => (string) ($r->id ?? ($idx + 1)),
                            'kode_aset'        => (string) ($r->kode_aset ?? ($asetObj->kode_aset ?? '-')),
                            'nomor_seri'       => (string) ($r->nomor_seri ?? ($asetObj->nomor_seri ?? '-')),
                            'nama_aset'        => (string) ($r->nama_aset ?? ($asetObj->item->nama_aset ?? '-')),
                            'kategori_id'      => (string) ($r->kategori_id ?? ($asetObj->kategori_id ?? '')),
                            'nama_kategori'    => (string) ($r->nama_kategori ?? ($asetObj->kategori->nama_kategori ?? '-')),
                            'cabang_id'        => (string) ($r->cabang_id ?? ($asetObj->cabang_id ?? '')),
                            'nama_cabang'      => (string) ($r->nama_cabang ?? ($asetObj->cabang->nama_cabang ?? '-')),
                            'ruangan'          => (string) ($r->ruangan ?? ($asetObj->Ruangan ?? $asetObj->lokasi_saat_ini ?? '-')),
                            'merk'             => (string) ($r->merk ?? ($asetObj->merk ?? '-')),
                            'unit'             => max(1, (int) ($r->unit ?? $r->jumlah ?? ($asetObj->unit ?? 1))),
                            'penanggung_jawab' => (string) ($r->nama_peminjam ?? $r->nama_pemohon ?? $r->penanggung_jawab ?? ($asetObj->penanggung_jawab ?? '-')),
                            'status_aset'      => (string) ($r->status ?? $r->status_pengajuan ?? ($asetObj->status_aset ?? '-')),
                            'harga_beli'       => (float) ($r->nilai ?? $r->harga_beli ?? ($asetObj->harga_beli ?? 0)),
                            'biaya_terkait'    => (float) ($r->biaya_servis ?? $r->nilai_jual ?? ($asetObj->biaya_servis ?? 0)),
                            'tanggal'          => $tglYmd,
                        ];
                    }
                    break;
                }
            }
            return $hasilBaris;
        };

        // 1. Data Peminjaman dari tabel transaksi peminjaman (atau detail_asets yang berstatus eksplisit 'Dipinjam')
        $dataLaporanDatabase['peminjaman'] = $ambilDariTabelTransaksi(['peminjamans', 'peminjaman_asets', 'peminjaman', 'transaksi_peminjamans']);
        if (empty($dataLaporanDatabase['peminjaman'])) {
            foreach ($semuaDetailAset as $idx => $da) {
                $st = strtolower(trim((string) ($da->status_aset ?? '')));
                if ($st === 'dipinjam' || $st === 'sedang dipinjam') {
                    $tglPinjam = (!empty($da->tanggal_mutasi) && $da->tanggal_mutasi !== '-')
                        ? (string) $da->tanggal_mutasi
                        : ($da->updated_at ? $da->updated_at->format('Y-m-d') : '');
                    $dataLaporanDatabase['peminjaman'][] = [
                        'id'               => (string) ($da->aset_id ?? $da->id ?? ($idx + 1)),
                        'kode_aset'        => (string) ($da->kode_aset ?? '-'),
                        'nomor_seri'       => (string) ($da->nomor_seri ?? '-'),
                        'nama_aset'        => (string) ($da->item->nama_aset ?? '-'),
                        'kategori_id'      => (string) ($da->kategori_id ?? ''),
                        'nama_kategori'    => (string) ($da->kategori->nama_kategori ?? '-'),
                        'cabang_id'        => (string) ($da->cabang_id ?? ''),
                        'nama_cabang'      => (string) ($da->cabang->nama_cabang ?? '-'),
                        'ruangan'          => (string) ($da->Ruangan ?? $da->lokasi_saat_ini ?? '-'),
                        'merk'             => (string) ($da->merk ?? '-'),
                        'unit'             => max(1, (int) ($da->unit ?? 1)),
                        'penanggung_jawab' => (string) ($da->penanggung_jawab ?: '-'),
                        'status_aset'      => (string) $da->status_aset,
                        'harga_beli'       => (float) ($da->harga_beli ?? 0),
                        'biaya_terkait'    => (float) ($da->biaya_servis ?? 0),
                        'tanggal'          => $tglPinjam,
                    ];
                }
            }
        }

        // 2. Data Pengajuan dari tabel transaksi pengajuan (atau detail_asets yang berstatus eksplisit 'Diajukan')
        $dataLaporanDatabase['pengajuan'] = $ambilDariTabelTransaksi(['pengajuans', 'pengajuan_asets', 'pengajuan', 'permohonan_asets']);
        if (empty($dataLaporanDatabase['pengajuan'])) {
            foreach ($semuaDetailAset as $idx => $da) {
                $st = strtolower(trim((string) ($da->status_aset ?? '')));
                if ($st === 'diajukan' || $st === 'menunggu persetujuan') {
                    $tglAju = $da->updated_at ? $da->updated_at->format('Y-m-d') : ($da->created_at ? $da->created_at->format('Y-m-d') : '');
                    $dataLaporanDatabase['pengajuan'][] = [
                        'id'               => (string) ($da->aset_id ?? $da->id ?? ($idx + 1)),
                        'kode_aset'        => (string) ($da->kode_aset ?? '-'),
                        'nomor_seri'       => (string) ($da->nomor_seri ?? '-'),
                        'nama_aset'        => (string) ($da->item->nama_aset ?? '-'),
                        'kategori_id'      => (string) ($da->kategori_id ?? ''),
                        'nama_kategori'    => (string) ($da->kategori->nama_kategori ?? '-'),
                        'cabang_id'        => (string) ($da->cabang_id ?? ''),
                        'nama_cabang'      => (string) ($da->cabang->nama_cabang ?? '-'),
                        'ruangan'          => (string) ($da->Ruangan ?? $da->lokasi_saat_ini ?? '-'),
                        'merk'             => (string) ($da->merk ?? '-'),
                        'unit'             => max(1, (int) ($da->unit ?? 1)),
                        'penanggung_jawab' => (string) ($da->penanggung_jawab ?: '-'),
                        'status_aset'      => (string) $da->status_aset,
                        'harga_beli'       => (float) ($da->harga_beli ?? 0),
                        'biaya_terkait'    => (float) ($da->biaya_servis ?? 0),
                        'tanggal'          => $tglAju,
                    ];
                }
            }
        }

        // 3. Data Pengembalian dari tabel transaksi pengembalian (atau detail_asets yang berstatus eksplisit 'Dikembalikan')
        $dataLaporanDatabase['pengembalian'] = $ambilDariTabelTransaksi(['pengembalians', 'pengembalian_asets', 'pengembalian', 'transaksi_pengembalians']);
        if (empty($dataLaporanDatabase['pengembalian'])) {
            foreach ($semuaDetailAset as $idx => $da) {
                $st = strtolower(trim((string) ($da->status_aset ?? '')));
                if ($st === 'dikembalikan' || $st === 'selesai dipinjam') {
                    $tglKembali = (!empty($da->tanggal_mutasi) && $da->tanggal_mutasi !== '-')
                        ? (string) $da->tanggal_mutasi
                        : ($da->updated_at ? $da->updated_at->format('Y-m-d') : '');
                    $dataLaporanDatabase['pengembalian'][] = [
                        'id'               => (string) ($da->aset_id ?? $da->id ?? ($idx + 1)),
                        'kode_aset'        => (string) ($da->kode_aset ?? '-'),
                        'nomor_seri'       => (string) ($da->nomor_seri ?? '-'),
                        'nama_aset'        => (string) ($da->item->nama_aset ?? '-'),
                        'kategori_id'      => (string) ($da->kategori_id ?? ''),
                        'nama_kategori'    => (string) ($da->kategori->nama_kategori ?? '-'),
                        'cabang_id'        => (string) ($da->cabang_id ?? ''),
                        'nama_cabang'      => (string) ($da->cabang->nama_cabang ?? '-'),
                        'ruangan'          => (string) ($da->Ruangan ?? $da->lokasi_saat_ini ?? '-'),
                        'merk'             => (string) ($da->merk ?? '-'),
                        'unit'             => max(1, (int) ($da->unit ?? 1)),
                        'penanggung_jawab' => (string) ($da->penanggung_jawab ?: '-'),
                        'status_aset'      => (string) $da->status_aset,
                        'harga_beli'       => (float) ($da->harga_beli ?? 0),
                        'biaya_terkait'    => (float) ($da->biaya_servis ?? 0),
                        'tanggal'          => $tglKembali,
                    ];
                }
            }
        }

        // 4. Data Peleburan dari tabel transaksi peleburan (atau detail_asets yang memiliki tanggal/status peleburan nyata)
        $dataLaporanDatabase['peleburan'] = $ambilDariTabelTransaksi(['peleburans', 'peleburan_asets', 'peleburan', 'transaksi_peleburans']);
        if (empty($dataLaporanDatabase['peleburan'])) {
            foreach ($semuaDetailAset as $idx => $da) {
                $st = strtolower(trim((string) ($da->status_aset ?? '')));
                $stLebur = trim((string) ($da->status_peleburan ?? ''));
                $tglLebur = (!empty($da->tanggal_peleburan) && $da->tanggal_peleburan !== '-') ? (string) $da->tanggal_peleburan : '';

                if ($st === 'dilebur' || ($stLebur !== '' && $stLebur !== '-' && strtolower($stLebur) !== 'tidak ada') || $tglLebur !== '') {
                    $dataLaporanDatabase['peleburan'][] = [
                        'id'               => (string) ($da->aset_id ?? $da->id ?? ($idx + 1)),
                        'kode_aset'        => (string) ($da->kode_aset ?? '-'),
                        'nomor_seri'       => (string) ($da->nomor_seri ?? '-'),
                        'nama_aset'        => (string) ($da->item->nama_aset ?? '-'),
                        'kategori_id'      => (string) ($da->kategori_id ?? ''),
                        'nama_kategori'    => (string) ($da->kategori->nama_kategori ?? '-'),
                        'cabang_id'        => (string) ($da->cabang_id ?? ''),
                        'nama_cabang'      => (string) ($da->cabang->nama_cabang ?? '-'),
                        'ruangan'          => (string) ($da->Ruangan ?? $da->lokasi_saat_ini ?? '-'),
                        'merk'             => (string) ($da->merk ?? '-'),
                        'unit'             => max(1, (int) ($da->unit ?? 1)),
                        'penanggung_jawab' => (string) ($da->penanggung_jawab ?: '-'),
                        'status_aset'      => $stLebur !== '' && $stLebur !== '-' ? $stLebur : (string) $da->status_aset,
                        'harga_beli'       => (float) ($da->harga_beli ?? 0),
                        'biaya_terkait'    => (float) ($da->nilai_jual ?? 0),
                        'tanggal'          => $tglLebur !== '' ? $tglLebur : ($da->updated_at ? $da->updated_at->format('Y-m-d') : ''),
                    ];
                }
            }
        }
    } catch (\Throwable $e) {
    }

    $namaUserCetak = $__userLoginLaporan->nama_lengkap ?? 'Master Admin';
    $jabatanUserCetak = $__isMasterAdminLaporan ? 'Master Admin Sistem Inventaris' : 'Admin Inventaris Aset';

    // Sinkronkan otomatis ke file laporan.blade.php lain jika project memisahkan folder views/admin & views/masteradmin
    try {
        $__currentLaporanFile = __FILE__;
        $__kandidatLaporanFiles = [
            resource_path('views/laporan.blade.php'),
            resource_path('views/admin/laporan.blade.php'),
            resource_path('views/masteradmin/laporan.blade.php'),
        ];
        if (is_file($__currentLaporanFile)) {
            $__isiLaporanIni = (string) file_get_contents($__currentLaporanFile);
            if (str_contains($__isiLaporanIni, '/* LAPORAN_RESMI_MASTER_V2 */')) {
                foreach ($__kandidatLaporanFiles as $__targetLFile) {
                    if (is_file($__targetLFile) && is_writable($__targetLFile) && realpath($__targetLFile) !== realpath($__currentLaporanFile)) {
                        $__isiTargetL = (string) file_get_contents($__targetLFile);
                        if (!str_contains($__isiTargetL, '/* LAPORAN_RESMI_MASTER_V2 */')) {
                            file_put_contents($__targetLFile, $__isiLaporanIni);
                        }
                    }
                }
            }
        }
    } catch (\Throwable $e) {
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Resmi Aset - {{ $__isMasterAdminLaporan ? 'Master Admin' : 'Admin' }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Coda+Caption:wght@800&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <style>
        :root {
            --primary-color: #1a4f91;
            --primary-dark: #103463;
            --secondary-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-light: #f8fafc;
            --text-color: #0f172a;
            --text-muted: rgba(0, 0, 0, 0.48);
            --border-color: rgba(0, 0, 0, 0.14);
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
            padding: 25px 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: calc(100% - var(--sidebar-width));
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 16px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1a4f91, #2563eb);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 14px rgba(26, 79, 145, 0.22);
            flex-shrink: 0;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 3px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .btn:hover:not(.is-disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn.is-disabled {
            opacity: 0.52;
            cursor: not-allowed;
            box-shadow: none !important;
        }

        .btn-pdf {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }

        .btn-excel {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        /* Panel Filter Dropdown */
        .card-box {
            background: #ffffff;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
            gap: 14px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .form-group select,
        .form-group input {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text-color);
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1);
        }

        /* Kartu Ringkasan Statistik Laporan */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 5px solid var(--primary-color);
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .summary-card.green { border-left-color: var(--secondary-color); }
        .summary-card.amber { border-left-color: var(--warning-color); }
        .summary-card.red { border-left-color: var(--danger-color); }

        .summary-card-left {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .summary-icon-badge {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .summary-card.green .summary-icon-badge { background: #ecfdf5; color: var(--secondary-color); }
        .summary-card.amber .summary-icon-badge { background: #fffbeb; color: var(--warning-color); }
        .summary-card.red .summary-icon-badge { background: #fef2f2; color: var(--danger-color); }

        .summary-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-value {
            font-size: 25px;
            font-weight: 800;
            color: #0f172a;
        }

        .summary-note {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }

        /* Tabel Data di Halaman Utama */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .data-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11.5px;
            font-weight: 800;
            text-align: left;
            padding: 12px 14px;
            border-bottom: 2px solid #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .data-table td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: #f8fafc;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-pinjam { background: #dbeafe; color: #1e40af; }
        .badge-kembali { background: #d1fae5; color: #065f46; }
        .badge-lebur { background: #fee2e2; color: #991b1b; }
        .badge-pengajuan { background: #fef3c7; color: #92400e; }

        .empty-table-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 44px 20px;
            color: #64748b;
            gap: 10px;
        }

        .empty-table-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Toast Peringatan */
        .laporan-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 12px;
            border-left: 4px solid #ef4444;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(130%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 440px;
        }

        .laporan-toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* ================= MODAL POP-UP PREVIEW LAPORAN RESMI (PDF & EXCEL) ================= */
        .preview-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .preview-modal-backdrop.show {
            display: flex;
        }

        .preview-modal-dialog {
            background: #e2e8f0;
            width: 100%;
            max-width: 1120px;
            max-height: 94vh;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .preview-modal-topbar {
            background: #0f172a;
            color: #ffffff;
            padding: 14px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .preview-modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 800;
        }

        .preview-modal-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Panel Pengaturan Kop Surat & Logo di dalam Modal */
        .kop-editor-drawer {
            display: none;
            background: #ffffff;
            border-bottom: 2px solid #cbd5e1;
            padding: 16px 22px;
        }

        .kop-editor-drawer.open {
            display: block;
        }

        .kop-editor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .preview-modal-body {
            padding: 28px;
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            justify-content: center;
        }

        /* KERTAS LAPORAN RESMI (A4 PREVIEW) */
        .official-paper {
            background: #ffffff;
            width: 100%;
            max-width: 960px;
            min-height: 1120px;
            padding: 42px 48px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
            border-radius: 4px;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* KOP SURAT RESMI (LOGO KIRI, INFO PT/MITRA TENGAH, LOGO KANAN) */
        .kop-surat-header {
            display: grid;
            grid-template-columns: 95px 1fr 95px;
            align-items: center;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 4px double #0f172a;
            margin-bottom: 22px;
        }

        .kop-logo-box {
            width: 88px;
            height: 88px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .kop-logo-box img {
            max-width: 86px;
            max-height: 86px;
            object-fit: contain;
        }

        .kop-center-info {
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .kop-instansi-atas {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #1e3a8a;
            text-transform: uppercase;
        }

        .kop-nama-pt {
            font-size: 21px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .kop-unit-mitra {
            font-size: 12.5px;
            font-weight: 700;
            color: #334155;
        }

        .kop-alamat {
            font-size: 11px;
            color: #475569;
            line-height: 1.45;
            margin-top: 2px;
        }

        .kop-kontak {
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
        }

        /* Judul & Nomor Dokumen Laporan Resmi */
        .doc-title-block {
            text-align: center;
            margin-bottom: 20px;
        }

        .doc-title-block h2 {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-decoration: underline;
            text-underline-offset: 4px;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .doc-title-block .doc-number {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        /* Tabel Metadata Dokumen */
        .doc-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .doc-meta-row {
            display: flex;
            gap: 8px;
        }

        .doc-meta-label {
            width: 135px;
            font-weight: 700;
            color: #334155;
            flex-shrink: 0;
        }

        .doc-meta-val {
            font-weight: 600;
            color: #0f172a;
        }

        /* Kotak Ringkasan di dalam Dokumen PDF */
        .doc-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .doc-summary-item {
            border: 1px solid #94a3b8;
            border-radius: 6px;
            padding: 10px 12px;
            background: #ffffff;
            text-align: center;
        }

        .doc-summary-item .ds-label {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .doc-summary-item .ds-val {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Tabel Resmi di dalam Dokumen PDF */
        .official-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 11px;
        }

        .official-table th {
            background: #1a4f91 !important;
            color: #ffffff !important;
            font-weight: 800;
            text-transform: uppercase;
            padding: 8px 9px;
            border: 1px solid #0f172a;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .official-table td {
            padding: 7px 9px;
            border: 1px solid #475569;
            color: #0f172a;
            vertical-align: top;
        }

        .official-table tbody tr:nth-child(even) {
            background: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .official-table tfoot td {
            background: #e2e8f0 !important;
            font-weight: 800;
            border: 1px solid #0f172a;
            padding: 8px 9px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Blok Tanda Tangan & Pengesahan Resmi */
        .doc-signature-section {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sig-space {
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-style: italic;
        }

        .sig-name {
            font-weight: 800;
            text-decoration: underline;
            color: #0f172a;
            font-size: 12.5px;
        }

        .sig-role {
            font-size: 11px;
            color: #475569;
            margin-top: 2px;
        }

        /* ATURAN CETAK (PRINT PDF LANGSUNG BERSIH HANYA KERTAS RESMI) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }

            body {
                background: #ffffff !important;
                display: block !important;
                overflow: visible !important;
            }

            sidebar,
            main,
            .preview-modal-topbar,
            .kop-editor-drawer,
            .laporan-toast {
                display: none !important;
            }

            .preview-modal-backdrop {
                position: static !important;
                display: block !important;
                background: transparent !important;
                padding: 0 !important;
                backdrop-filter: none !important;
            }

            .preview-modal-dialog {
                background: transparent !important;
                max-width: 100% !important;
                max-height: none !important;
                box-shadow: none !important;
                border: none !important;
                overflow: visible !important;
            }

            .preview-modal-body {
                padding: 0 !important;
                overflow: visible !important;
                display: block !important;
            }

            .official-paper {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                min-height: 0 !important;
            }
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            .kop-surat-header {
                grid-template-columns: 65px 1fr 65px;
            }
        }
    </style>
</head>
<body>

    @if($__isMasterAdminLaporan)
        @include('layouts.mastersidebar')
    @else
        @include('layouts.adminsidebar')
    @endif

    <main>
        <!-- HEADER UTAMA & TOMBOL CETAK PDF / EXCEL -->
        <header class="main-header">
            <div class="header-title">
                <div class="header-icon-box">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                </div>
                <div>
                    <h1>Laporan Resmi Aset</h1>
                    <p>Rekapitulasi data peminjaman, pengajuan, pengembalian, dan peleburan aset dari database beserta cetak PDF dan Excel resmi.</p>
                </div>
            </div>
            <div class="header-actions">
                <button type="button" id="btn-utama-pdf" class="btn btn-pdf" onclick="bukaModalPreviewLaporan('pdf')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Cetak PDF</span>
                </button>
                <button type="button" id="btn-utama-excel" class="btn btn-excel" onclick="bukaModalPreviewLaporan('excel')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="9" x2="9" y2="21"></line>
                        <line x1="15" y1="9" x2="15" y2="21"></line>
                    </svg>
                    <span>Cetak Excel</span>
                </button>
            </div>
        </header>

        <!-- PANEL FILTER DROPDOWN (RINGKAS, TANPA TOMBOL GANDA) -->
        <div class="card-box">
            <div class="filter-grid">
                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        <span>Jenis Laporan</span>
                    </label>
                    <select id="select-jenis-laporan" onchange="pilihJenisLaporan(this.value)">
                        <option value="peminjaman" selected>Laporan Peminjaman Aset</option>
                        <option value="pengajuan">Laporan Pengajuan Aset</option>
                        <option value="pengembalian">Laporan Pengembalian Aset</option>
                        <option value="peleburan">Laporan Peleburan Aset</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Template Rentang Waktu</span>
                    </label>
                    <select id="select-template-waktu" onchange="terapkanTemplateWaktu(this.value)">
                        <option value="1_bulan" selected>1 Bulan Terakhir</option>
                        <option value="2_bulan">2 Bulan Terakhir</option>
                        <option value="3_bulan">3 Bulan Terakhir</option>
                        <option value="6_bulan">6 Bulan Terakhir</option>
                        <option value="12_bulan">1 Tahun Terakhir</option>
                        <option value="semua">Semua Waktu</option>
                        <option value="custom">Pilih Rentang Tanggal Tertentu</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Dari Tanggal</span>
                    </label>
                    <input type="date" id="filter-tgl-mulai" onchange="saatTanggalManualBerubah()">
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>Sampai Tanggal</span>
                    </label>
                    <input type="date" id="filter-tgl-selesai" onchange="saatTanggalManualBerubah()">
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>Filter Cabang</span>
                    </label>
                    <select id="filter-cabang-laporan" onchange="renderLaporanUtama()">
                        <option value="">-- Semua Cabang --</option>
                        @foreach($daftarCabangLaporan as $cb)
                            <option value="{{ $cb['id'] }}">{{ $cb['nama'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <span>Filter Kategori</span>
                    </label>
                    <select id="filter-kategori-laporan" onchange="renderLaporanUtama()">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($daftarKategoriLaporan as $kt)
                            <option value="{{ $kt['id'] }}">{{ $kt['nama'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>Pencarian Data</span>
                    </label>
                    <input type="text" id="filter-keyword-laporan" placeholder="Cari kode, nama aset, atau PJ..." oninput="renderLaporanUtama()">
                </div>
            </div>
        </div>

        <!-- 4 KARTU RINGKASAN EKSEKUTIF -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-card-left">
                    <span class="summary-label" id="stat-label-utama">Total Aset Dipinjam</span>
                    <span class="summary-value" id="stat-total-aset">0 Aset</span>
                    <span class="summary-note" id="stat-periode-text">Periode: 1 Bulan Terakhir</span>
                </div>
                <div class="summary-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
            </div>

            <div class="summary-card green">
                <div class="summary-card-left">
                    <span class="summary-label">Total Kuantitas Unit</span>
                    <span class="summary-value" id="stat-total-unit">0 Unit</span>
                    <span class="summary-note">Akumulasi jumlah fisik unit aset</span>
                </div>
                <div class="summary-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
            </div>

            <div class="summary-card amber">
                <div class="summary-card-left">
                    <span class="summary-label">Total Nilai Aset Tercatat</span>
                    <span class="summary-value" id="stat-total-nilai">Rp 0</span>
                    <span class="summary-note">Akumulasi nilai perolehan aset</span>
                </div>
                <div class="summary-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
            </div>

            <div class="summary-card red">
                <div class="summary-card-left">
                    <span class="summary-label" id="stat-label-sekunder">Total Biaya Terkait</span>
                    <span class="summary-value" id="stat-total-sekunder">Rp 0</span>
                    <span class="summary-note">Akumulasi biaya servis / nilai peleburan</span>
                </div>
                <div class="summary-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
            </div>
        </div>

        <!-- TABEL DATA LAPORAN DI HALAMAN UTAMA -->
        <div class="card-box">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <div>
                    <h3 id="judul-tabel-halaman" style="font-size: 16px; font-weight: 800; color: var(--primary-color);">
                        Daftar Laporan Peminjaman Aset
                    </h3>
                    <p id="subjudul-tabel-halaman" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Data diambil langsung dari database sesuai jenis laporan dan rentang waktu yang dipilih.
                    </p>
                </div>
                <div id="indikator-status-cetak" style="font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 8px; background: #fee2e2; color: #b91c1c;">
                    0 Data Tersedia (Tidak Dapat Dicetak)
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>KODE ASET</th>
                            <th>NAMA ASET & MERK</th>
                            <th>KATEGORI</th>
                            <th>CABANG & LOKASI</th>
                            <th>PENANGGUNG JAWAB / PEMINJAM</th>
                            <th>TANGGAL TRANSAKSI</th>
                            <th>UNIT</th>
                            <th>STATUS</th>
                            <th>NILAI ASET</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-laporan-utama">
                        <!-- Diisi secara dinamis oleh JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- TOAST NOTIFIKASI TANPA EMOJI -->
    <div id="toast-laporan" class="laporan-toast">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span id="toast-laporan-text">Tidak dapat mencetak laporan karena belum ada data (0 data).</span>
    </div>

    <!-- ================= POP-UP MODAL PREVIEW LAPORAN RESMI (PDF & EXCEL) ================= -->
    <div class="preview-modal-backdrop" id="modalPreviewLaporan">
        <div class="preview-modal-dialog">
            <!-- Topbar Kontrol Pop-Up Preview -->
            <div class="preview-modal-topbar">
                <div class="preview-modal-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span id="modal-preview-header-text">Preview Dokumen Laporan Resmi (Ukuran A4)</span>
                </div>
                <div class="preview-modal-actions">
                    <button type="button" class="btn" style="background: #1e293b; color: #fff; border: 1px solid #475569; padding: 8px 14px; font-size: 12px;" onclick="toggleEditorKopSurat()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span>Sesuaikan Kop Surat & Logo</span>
                    </button>
                    <button type="button" class="btn btn-pdf" style="padding: 8px 16px; font-size: 12.5px;" onclick="eksekusiCetakPdfResmi()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak / Simpan PDF</span>
                    </button>
                    <button type="button" class="btn btn-excel" style="padding: 8px 16px; font-size: 12.5px;" onclick="eksekusiUnduhExcelResmi()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Unduh Excel (.xls)</span>
                    </button>
                    <button type="button" class="btn" style="background: #334155; color: #fff; border: none; padding: 8px 14px; font-size: 12px;" onclick="tutupModalPreviewLaporan()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Tutup</span>
                    </button>
                </div>
            </div>

            <!-- Drawer Pengaturan Kop Surat, Informasi PT/Mitra & Upload Logo Kiri/Kanan -->
            <div class="kop-editor-drawer" id="drawerEditorKop">
                <div style="font-size: 12.5px; font-weight: 800; color: var(--primary-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Pengaturan Kop Surat Resmi (Otomatis Tersimpan & Tampil di PDF maupun Excel)</span>
                </div>
                <div class="kop-editor-grid">
                    <div class="form-group">
                        <label>Instansi Induk / Header Atas</label>
                        <input type="text" id="inp-kop-induk" value="DIVISI MANAJEMEN INVENTARIS & ASET TERPADU" oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Nama Perusahaan / PT / Mitra</label>
                        <input type="text" id="inp-kop-pt" value="PT MITRA ASET NUSANTARA" oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Sub-Unit / Keterangan Mitra</label>
                        <input type="text" id="inp-kop-unit" value="MITRA RESMI PENGELOLAAN FASILITAS & INVENTARIS CABANG" oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap PT / Mitra</label>
                        <input type="text" id="inp-kop-alamat" value="Jl. Raya Industri No. 88, Kawasan Bisnis Terpadu, Sidoarjo, Jawa Timur 61219" oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Kontak Telepon & Email Resmi</label>
                        <input type="text" id="inp-kop-kontak" value="Telp: (031) 8945-2210 | Email: official@mitraasetnusantara.co.id | Web: www.mitraasetnusantara.co.id" oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Nama Pimpinan Mitra (Tanda Tangan Kiri)</label>
                        <input type="text" id="inp-kop-pimpinan" value="Bpk. Hendra Wijaya, S.E., M.M." oninput="perbaruiKopSuratLive()">
                    </div>
                    <div class="form-group">
                        <label>Upload Logo Kiri (Opsional)</label>
                        <input type="file" accept="image/*" onchange="ubahLogoKop(this, 'kiri')" style="font-size: 11px; padding: 6px;">
                    </div>
                    <div class="form-group">
                        <label>Upload Logo Kanan (Opsional)</label>
                        <input type="file" accept="image/*" onchange="ubahLogoKop(this, 'kanan')" style="font-size: 11px; padding: 6px;">
                    </div>
                </div>
            </div>

            <!-- Area Kertas A4 Preview Laporan Resmi -->
            <div class="preview-modal-body">
                <div class="official-paper" id="kertas-laporan-resmi">
                    <div>
                        <!-- 1. KOP SURAT RESMI (LOGO KIRI - INFO PT/MITRA TENGAH - LOGO KANAN) -->
                        <div class="kop-surat-header">
                            <!-- Pojok Kiri: Logo Utama PT / Sistem -->
                            <div class="kop-logo-box" id="box-logo-kiri">
                                <svg width="82" height="82" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="gradLogoKiri" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#1e3a8a"/>
                                            <stop offset="100%" stop-color="#1a4f91"/>
                                        </linearGradient>
                                    </defs>
                                    <circle cx="50" cy="50" r="46" fill="url(#gradLogoKiri)" stroke="#f59e0b" stroke-width="4"/>
                                    <circle cx="50" cy="50" r="39" fill="none" stroke="#ffffff" stroke-width="1.2" stroke-dasharray="3,2"/>
                                    <path d="M28 62 L50 26 L72 62 Z" fill="none" stroke="#f59e0b" stroke-width="3.5" stroke-linejoin="round"/>
                                    <text x="50" y="58" text-anchor="middle" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="20" font-weight="900">SM</text>
                                    <text x="50" y="77" text-anchor="middle" fill="#fde68a" font-family="Inter, Arial, sans-serif" font-size="7.5" font-weight="800" letter-spacing="1">ASET CORP</text>
                                </svg>
                            </div>

                            <!-- Tengah: Informasi Resmi PT / Mitra, Alamat, Email, Kontak -->
                            <div class="kop-center-info">
                                <div class="kop-instansi-atas" id="teks-kop-induk">DIVISI MANAJEMEN INVENTARIS & ASET TERPADU</div>
                                <div class="kop-nama-pt" id="teks-kop-pt">PT MITRA ASET NUSANTARA</div>
                                <div class="kop-unit-mitra" id="teks-kop-unit">MITRA RESMI PENGELOLAAN FASILITAS & INVENTARIS CABANG</div>
                                <div class="kop-alamat" id="teks-kop-alamat">Jl. Raya Industri No. 88, Kawasan Bisnis Terpadu, Sidoarjo, Jawa Timur 61219</div>
                                <div class="kop-kontak" id="teks-kop-kontak">Telp: (031) 8945-2210 | Email: official@mitraasetnusantara.co.id | Web: www.mitraasetnusantara.co.id</div>
                            </div>

                            <!-- Pojok Kanan: Logo Mitra / ISO Badge Resmi -->
                            <div class="kop-logo-box" id="box-logo-kanan">
                                <svg width="82" height="82" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="gradLogoKanan" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#065f46"/>
                                            <stop offset="100%" stop-color="#10b981"/>
                                        </linearGradient>
                                    </defs>
                                    <polygon points="50,5 89,27 89,73 50,95 11,73 11,27" fill="url(#gradLogoKanan)" stroke="#0f172a" stroke-width="3"/>
                                    <polygon points="50,12 82,31 82,69 50,88 18,69 18,31" fill="none" stroke="#ffffff" stroke-width="1.2"/>
                                    <path d="M36 51 L46 61 L66 39" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <text x="50" y="76" text-anchor="middle" fill="#ecfdf5" font-family="Inter, Arial, sans-serif" font-size="8" font-weight="800" letter-spacing="0.8">MITRA RESMI</text>
                                </svg>
                            </div>
                        </div>

                        <!-- 2. JUDUL DOKUMEN & NOMOR SURAT LAPORAN -->
                        <div class="doc-title-block">
                            <h2 id="pdf-judul-dokumen">LAPORAN REKAPITULASI PEMINJAMAN ASET INVENTARIS</h2>
                            <div class="doc-number" id="pdf-nomor-dokumen">Nomor Dokumen: 001/LAP-AST/PMJ/X/2026</div>
                        </div>

                        <!-- 3. METADATA INFORMASI LAPORAN -->
                        <div class="doc-meta-grid">
                            <div>
                                <div class="doc-meta-row">
                                    <span class="doc-meta-label">Kategori Laporan</span>
                                    <span class="doc-meta-val">: <span id="pdf-meta-jenis">Laporan Peminjaman Aset</span></span>
                                </div>
                                <div class="doc-meta-row" style="margin-top: 4px;">
                                    <span class="doc-meta-label">Periode Laporan</span>
                                    <span class="doc-meta-val">: <span id="pdf-meta-periode">1 Bulan Terakhir</span></span>
                                </div>
                                <div class="doc-meta-row" style="margin-top: 4px;">
                                    <span class="doc-meta-label">Cakupan Cabang</span>
                                    <span class="doc-meta-val">: <span id="pdf-meta-cabang">Semua Cabang</span></span>
                                </div>
                            </div>
                            <div>
                                <div class="doc-meta-row">
                                    <span class="doc-meta-label">Filter Kategori Aset</span>
                                    <span class="doc-meta-val">: <span id="pdf-meta-kategori">Semua Kategori</span></span>
                                </div>
                                <div class="doc-meta-row" style="margin-top: 4px;">
                                    <span class="doc-meta-label">Tanggal Cetak</span>
                                    <span class="doc-meta-val">: <span id="pdf-meta-tglcetak">-</span></span>
                                </div>
                                <div class="doc-meta-row" style="margin-top: 4px;">
                                    <span class="doc-meta-label">Dicetak Oleh</span>
                                    <span class="doc-meta-val">: {{ $namaUserCetak }} ({{ $__isMasterAdminLaporan ? 'Master Admin' : 'Admin' }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. KOTAK RINGKASAN EKSEKUTIF DI DALAM PDF -->
                        <div class="doc-summary-strip">
                            <div class="doc-summary-item">
                                <div class="ds-label" id="pdf-sum-label-1">Total Aset Dipinjam</div>
                                <div class="ds-val" id="pdf-sum-val-1">0 Aset</div>
                            </div>
                            <div class="doc-summary-item">
                                <div class="ds-label">Total Kuantitas Fisik</div>
                                <div class="ds-val" id="pdf-sum-val-2">0 Unit</div>
                            </div>
                            <div class="doc-summary-item">
                                <div class="ds-label">Total Nilai Inventaris</div>
                                <div class="ds-val" id="pdf-sum-val-3">Rp 0</div>
                            </div>
                            <div class="doc-summary-item">
                                <div class="ds-label" id="pdf-sum-label-4">Biaya Terkait</div>
                                <div class="ds-val" id="pdf-sum-val-4">Rp 0</div>
                            </div>
                        </div>

                        <!-- 5. TABEL DATA LAPORAN RESMI -->
                        <table class="official-table" id="tabel-resmi-cetak">
                            <thead>
                                <tr>
                                    <th style="width: 34px; text-align: center;">NO</th>
                                    <th style="width: 115px;">KODE ASET</th>
                                    <th>NAMA ASET & MERK</th>
                                    <th>KATEGORI</th>
                                    <th>CABANG / RUANGAN</th>
                                    <th>PENANGGUNG JAWAB</th>
                                    <th style="width: 82px;">TANGGAL</th>
                                    <th style="width: 45px; text-align: center;">UNIT</th>
                                    <th>STATUS</th>
                                    <th style="width: 105px; text-align: right;">NILAI (RP)</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pdf-resmi">
                                <!-- Diisi otomatis oleh JS -->
                            </tbody>
                            <tfoot id="tfoot-pdf-resmi">
                                <!-- Baris Total diisi otomatis oleh JS -->
                            </tfoot>
                        </table>
                    </div>

                    <!-- 6. BLOK PENGESAHAN & TANDA TANGAN RESMI -->
                    <div class="doc-signature-section">
                        <div class="sig-box">
                            <div>Mengetahui & Menyetujui,</div>
                            <div style="font-weight: 700; margin-top: 2px;">Pimpinan Operasional / Mitra</div>
                            <div class="sig-space">( Stempel & Tanda Tangan Resmi )</div>
                            <div class="sig-name" id="teks-sig-pimpinan">Bpk. Hendra Wijaya, S.E., M.M.</div>
                            <div class="sig-role">Perwakilan Resmi PT Mitra Aset Nusantara</div>
                        </div>

                        <div class="sig-box">
                            <div id="teks-sig-tanggal">Sidoarjo, {{ date('d F Y') }}</div>
                            <div style="font-weight: 700; margin-top: 2px;">Disusun & Disahkan Oleh,</div>
                            <div class="sig-space">( Tanda Tangan Master Admin )</div>
                            <div class="sig-name">{{ $namaUserCetak }}</div>
                            <div class="sig-role">{{ $jabatanUserCetak }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const DATA_LAPORAN_DB = @json($dataLaporanDatabase);
        const NAMA_USER_CETAK = @json($namaUserCetak);
        const JABATAN_USER_CETAK = @json($jabatanUserCetak);

        let stateLaporan = {
            jenis: 'peminjaman',
            template: '1_bulan',
            tglMulai: '',
            tglSelesai: '',
            filteredRows: []
        };

        let toastTimeout = null;

        const KONFIG_JENIS_LAPORAN = {
            peminjaman: {
                nama: 'Laporan Peminjaman Aset',
                judulTabel: 'Daftar Laporan Peminjaman Aset',
                judulPdf: 'LAPORAN REKAPITULASI PEMINJAMAN ASET INVENTARIS',
                kodeDok: 'PMJ',
                labelUtama: 'Total Aset Dipinjam',
                labelSekunder: 'Total Biaya Pemeliharaan'
            },
            pengajuan: {
                nama: 'Laporan Pengajuan Aset',
                judulTabel: 'Daftar Laporan Pengajuan Aset',
                judulPdf: 'LAPORAN REKAPITULASI PENGAJUAN ASET INVENTARIS',
                kodeDok: 'PGJ',
                labelUtama: 'Total Aset Diajukan',
                labelSekunder: 'Total Estimasi Biaya'
            },
            pengembalian: {
                nama: 'Laporan Pengembalian Aset',
                judulTabel: 'Daftar Laporan Pengembalian Aset',
                judulPdf: 'LAPORAN REKAPITULASI PENGEMBALIAN ASET INVENTARIS',
                kodeDok: 'PGB',
                labelUtama: 'Total Aset Dikembalikan',
                labelSekunder: 'Total Biaya Terkait'
            },
            peleburan: {
                nama: 'Laporan Peleburan Aset',
                judulTabel: 'Daftar Laporan Peleburan Aset',
                judulPdf: 'LAPORAN REKAPITULASI PELEBURAN & PENGHAPUSAN ASET',
                kodeDok: 'PLB',
                labelUtama: 'Total Aset Dilebur',
                labelSekunder: 'Total Nilai Jual Peleburan'
            }
        };

        function tampilkanToastPeringatan(pesan) {
            const toastEl = document.getElementById('toast-laporan');
            const textEl = document.getElementById('toast-laporan-text');
            if (!toastEl || !textEl) return;
            textEl.textContent = pesan;
            toastEl.classList.add('show');
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toastEl.classList.remove('show');
            }, 3500);
        }

        function formatRupiah(angka) {
            const n = Math.round(Number(angka) || 0);
            return 'Rp ' + n.toLocaleString('id-ID');
        }

        function formatTanggalIndo(ymdStr) {
            if (!ymdStr || ymdStr === '-') return '-';
            const d = new Date(ymdStr + 'T00:00:00');
            if (isNaN(d.getTime())) return ymdStr;
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function formatYmd(dateObj) {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Terapkan Template Waktu dari Dropdown (1 Bulan, 2 Bulan, 3 Bulan, 6 Bulan, 1 Tahun, Semua, atau Custom)
        function terapkanTemplateWaktu(kodeTemplate) {
            stateLaporan.template = kodeTemplate;

            const selTemplate = document.getElementById('select-template-waktu');
            if (selTemplate && selTemplate.value !== kodeTemplate) {
                selTemplate.value = kodeTemplate;
            }

            const inpMulai = document.getElementById('filter-tgl-mulai');
            const inpSelesai = document.getElementById('filter-tgl-selesai');
            const hariIni = new Date();
            const mulai = new Date(hariIni.getTime());

            if (kodeTemplate === '1_bulan') {
                mulai.setMonth(mulai.getMonth() - 1);
                stateLaporan.tglMulai = formatYmd(mulai);
                stateLaporan.tglSelesai = formatYmd(hariIni);
            } else if (kodeTemplate === '2_bulan') {
                mulai.setMonth(mulai.getMonth() - 2);
                stateLaporan.tglMulai = formatYmd(mulai);
                stateLaporan.tglSelesai = formatYmd(hariIni);
            } else if (kodeTemplate === '3_bulan') {
                mulai.setMonth(mulai.getMonth() - 3);
                stateLaporan.tglMulai = formatYmd(mulai);
                stateLaporan.tglSelesai = formatYmd(hariIni);
            } else if (kodeTemplate === '6_bulan') {
                mulai.setMonth(mulai.getMonth() - 6);
                stateLaporan.tglMulai = formatYmd(mulai);
                stateLaporan.tglSelesai = formatYmd(hariIni);
            } else if (kodeTemplate === '12_bulan') {
                mulai.setFullYear(mulai.getFullYear() - 1);
                stateLaporan.tglMulai = formatYmd(mulai);
                stateLaporan.tglSelesai = formatYmd(hariIni);
            } else if (kodeTemplate === 'semua') {
                stateLaporan.tglMulai = '';
                stateLaporan.tglSelesai = '';
            } else if (kodeTemplate === 'custom') {
                if (inpMulai) inpMulai.focus();
            }

            if (kodeTemplate !== 'custom') {
                if (inpMulai) inpMulai.value = stateLaporan.tglMulai;
                if (inpSelesai) inpSelesai.value = stateLaporan.tglSelesai;
            }

            renderLaporanUtama();
        }

        function saatTanggalManualBerubah() {
            const inpMulai = document.getElementById('filter-tgl-mulai');
            const inpSelesai = document.getElementById('filter-tgl-selesai');
            stateLaporan.tglMulai = inpMulai ? inpMulai.value : '';
            stateLaporan.tglSelesai = inpSelesai ? inpSelesai.value : '';
            stateLaporan.template = 'custom';

            const selTemplate = document.getElementById('select-template-waktu');
            if (selTemplate) selTemplate.value = 'custom';

            renderLaporanUtama();
        }

        function pilihJenisLaporan(jenis) {
            stateLaporan.jenis = jenis;
            const selJenis = document.getElementById('select-jenis-laporan');
            if (selJenis && selJenis.value !== jenis) {
                selJenis.value = jenis;
            }
            renderLaporanUtama();
        }

        function ambilLabelPeriodeAktif() {
            const mapTemplate = {
                '1_bulan': '1 Bulan Terakhir',
                '2_bulan': '2 Bulan Terakhir',
                '3_bulan': '3 Bulan Terakhir',
                '6_bulan': '6 Bulan Terakhir',
                '12_bulan': '1 Tahun Terakhir',
                'semua': 'Semua Waktu',
                'custom': 'Rentang Tanggal Tertentu'
            };
            const namaTpl = mapTemplate[stateLaporan.template] || 'Periode Terpilih';
            if (stateLaporan.tglMulai && stateLaporan.tglSelesai) {
                return `${namaTpl} (${formatTanggalIndo(stateLaporan.tglMulai)} s/d ${formatTanggalIndo(stateLaporan.tglSelesai)})`;
            }
            if (stateLaporan.tglMulai) {
                return `Mulai ${formatTanggalIndo(stateLaporan.tglMulai)}`;
            }
            if (stateLaporan.tglSelesai) {
                return `Sampai ${formatTanggalIndo(stateLaporan.tglSelesai)}`;
            }
            return 'Semua Waktu';
        }

        function renderLaporanUtama() {
            const jenis = stateLaporan.jenis;
            const konfig = KONFIG_JENIS_LAPORAN[jenis] || KONFIG_JENIS_LAPORAN.peminjaman;
            const cabangId = document.getElementById('filter-cabang-laporan')?.value || '';
            const kategoriId = document.getElementById('filter-kategori-laporan')?.value || '';
            const keyword = (document.getElementById('filter-keyword-laporan')?.value || '').trim().toLowerCase();

            // Ambil murni dari database sesuai jenis laporan (tanpa data dummy/fallback palsu)
            const sumberDataJenis = Array.isArray(DATA_LAPORAN_DB[jenis]) ? DATA_LAPORAN_DB[jenis] : [];

            const hasil = sumberDataJenis.filter(item => {
                if (cabangId && String(item.cabang_id) !== String(cabangId)) return false;
                if (kategoriId && String(item.kategori_id) !== String(kategoriId)) return false;

                if (stateLaporan.tglMulai && item.tanggal && item.tanggal < stateLaporan.tglMulai) {
                    return false;
                }
                if (stateLaporan.tglSelesai && item.tanggal && item.tanggal > stateLaporan.tglSelesai) {
                    return false;
                }

                if (keyword) {
                    const gabungan = [
                        item.kode_aset,
                        item.nomor_seri,
                        item.nama_aset,
                        item.nama_kategori,
                        item.nama_cabang,
                        item.ruangan,
                        item.merk,
                        item.penanggung_jawab,
                        item.status_aset
                    ].join(' ').toLowerCase();
                    if (!gabungan.includes(keyword)) return false;
                }
                return true;
            });

            stateLaporan.filteredRows = hasil;

            // Hitung ringkasan statistik
            const totalAset = hasil.length;
            const totalUnit = hasil.reduce((sum, r) => sum + (Number(r.unit) || 1), 0);
            const totalHarga = hasil.reduce((sum, r) => sum + (Number(r.harga_beli) || 0), 0);
            const totalSekunder = hasil.reduce((sum, r) => sum + (Number(r.biaya_terkait) || 0), 0);

            const periodeText = ambilLabelPeriodeAktif();

            document.getElementById('stat-label-utama').textContent = konfig.labelUtama;
            document.getElementById('stat-total-aset').textContent = `${totalAset} Aset`;
            document.getElementById('stat-periode-text').textContent = periodeText;
            document.getElementById('stat-total-unit').textContent = `${totalUnit} Unit`;
            document.getElementById('stat-total-nilai').textContent = formatRupiah(totalHarga);
            document.getElementById('stat-label-sekunder').textContent = konfig.labelSekunder;
            document.getElementById('stat-total-sekunder').textContent = formatRupiah(totalSekunder);
            document.getElementById('judul-tabel-halaman').textContent = konfig.judulTabel;

            // Atur status aktif/nonaktif tombol Cetak PDF & Excel berdasarkan ketersediaan data
            const btnPdf = document.getElementById('btn-utama-pdf');
            const btnExcel = document.getElementById('btn-utama-excel');
            const indikatorCetak = document.getElementById('indikator-status-cetak');

            if (totalAset === 0) {
                if (btnPdf) btnPdf.classList.add('is-disabled');
                if (btnExcel) btnExcel.classList.add('is-disabled');
                if (indikatorCetak) {
                    indikatorCetak.style.background = '#fee2e2';
                    indikatorCetak.style.color = '#b91c1c';
                    indikatorCetak.textContent = '0 Data Tersedia (Tidak Dapat Dicetak)';
                }
            } else {
                if (btnPdf) btnPdf.classList.remove('is-disabled');
                if (btnExcel) btnExcel.classList.remove('is-disabled');
                if (indikatorCetak) {
                    indikatorCetak.style.background = '#d1fae5';
                    indikatorCetak.style.color = '#065f46';
                    indikatorCetak.textContent = `${totalAset} Data Siap Dicetak (PDF / Excel)`;
                }
            }

            // Render baris ke tabel utama
            const tbody = document.getElementById('tbody-laporan-utama');
            if (!tbody) return;

            if (hasil.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10">
                            <div class="empty-table-box">
                                <div class="empty-table-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="9" y1="15" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                                <div style="font-size: 14px; font-weight: 700; color: #334155;">
                                    Belum Ada Data ${escapeHtml(konfig.nama)} (0 Data)
                                </div>
                                <div style="font-size: 12.5px; color: #64748b; text-align: center; max-width: 520px;">
                                    Saat ini belum ada transaksi pada database untuk kategori <strong>${escapeHtml(konfig.nama)}</strong> di periode <strong>${escapeHtml(periodeText)}</strong>. Laporan baru dapat dicetak setelah data tersedia.
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = hasil.map((r, i) => {
                    const tglTampil = formatTanggalIndo(r.tanggal);
                    let badgeClass = 'badge-pinjam';
                    if (jenis === 'pengembalian') badgeClass = 'badge-kembali';
                    else if (jenis === 'peleburan') badgeClass = 'badge-lebur';
                    else if (jenis === 'pengajuan') badgeClass = 'badge-pengajuan';

                    const nilaiTampil = (jenis === 'peleburan' && r.biaya_terkait > 0) ? r.biaya_terkait : r.harga_beli;

                    return `
                        <tr>
                            <td style="font-weight: 700; color: #64748b;">${i + 1}</td>
                            <td style="font-weight: 800; color: var(--primary-color);">${escapeHtml(r.kode_aset)}</td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;">${escapeHtml(r.nama_aset)}</div>
                                <div style="font-size: 11px; color: #64748b;">Merk: ${escapeHtml(r.merk)} | SN: ${escapeHtml(r.nomor_seri)}</div>
                            </td>
                            <td>${escapeHtml(r.nama_kategori)}</td>
                            <td>
                                <div style="font-weight: 600;">${escapeHtml(r.nama_cabang)}</div>
                                <div style="font-size: 11px; color: #64748b;">Ruang: ${escapeHtml(r.ruangan)}</div>
                            </td>
                            <td style="font-weight: 600;">${escapeHtml(r.penanggung_jawab)}</td>
                            <td style="white-space: nowrap;">${escapeHtml(tglTampil)}</td>
                            <td style="font-weight: 700; text-align: center;">${r.unit}</td>
                            <td><span class="badge-status ${badgeClass}">${escapeHtml(r.status_aset)}</span></td>
                            <td style="font-weight: 700; white-space: nowrap;">${formatRupiah(nilaiTampil)}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        function renderDokumenPreviewResmi() {
            const jenis = stateLaporan.jenis;
            const konfig = KONFIG_JENIS_LAPORAN[jenis] || KONFIG_JENIS_LAPORAN.peminjaman;
            const rows = stateLaporan.filteredRows || [];
            const periodeText = ambilLabelPeriodeAktif();

            const selCabang = document.getElementById('filter-cabang-laporan');
            const namaCabang = (selCabang && selCabang.value) ? selCabang.options[selCabang.selectedIndex].text : 'Semua Cabang';

            const selKategori = document.getElementById('filter-kategori-laporan');
            const namaKategori = (selKategori && selKategori.value) ? selKategori.options[selKategori.selectedIndex].text : 'Semua Kategori';

            const skrg = new Date();
            const romawiBulan = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][skrg.getMonth()];
            const nomorDok = `Nomor: ${String(rows.length || 1).padStart(3, '0')}/LAP-AST/${konfig.kodeDok}/${romawiBulan}/${skrg.getFullYear()}`;
            const tglCetakLengkap = skrg.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

            document.getElementById('pdf-judul-dokumen').textContent = konfig.judulPdf;
            document.getElementById('pdf-nomor-dokumen').textContent = nomorDok;
            document.getElementById('pdf-meta-jenis').textContent = konfig.nama;
            document.getElementById('pdf-meta-periode').textContent = periodeText;
            document.getElementById('pdf-meta-cabang').textContent = namaCabang;
            document.getElementById('pdf-meta-kategori').textContent = namaKategori;
            document.getElementById('pdf-meta-tglcetak').textContent = tglCetakLengkap;
            document.getElementById('teks-sig-tanggal').textContent = `Sidoarjo, ${tglCetakLengkap}`;

            const totalAset = rows.length;
            const totalUnit = rows.reduce((sum, r) => sum + (Number(r.unit) || 1), 0);
            const totalHarga = rows.reduce((sum, r) => sum + (Number(r.harga_beli) || 0), 0);
            const totalSekunder = rows.reduce((sum, r) => sum + (Number(r.biaya_terkait) || 0), 0);

            document.getElementById('pdf-sum-label-1').textContent = konfig.labelUtama;
            document.getElementById('pdf-sum-val-1').textContent = `${totalAset} Data Aset`;
            document.getElementById('pdf-sum-val-2').textContent = `${totalUnit} Unit`;
            document.getElementById('pdf-sum-val-3').textContent = formatRupiah(totalHarga);
            document.getElementById('pdf-sum-label-4').textContent = konfig.labelSekunder;
            document.getElementById('pdf-sum-val-4').textContent = formatRupiah(totalSekunder);

            const tbodyPdf = document.getElementById('tbody-pdf-resmi');
            const tfootPdf = document.getElementById('tfoot-pdf-resmi');

            let akumulasiNilaiTabel = 0;
            tbodyPdf.innerHTML = rows.map((r, i) => {
                const tglTampil = formatTanggalIndo(r.tanggal);
                const nilaiBaris = (jenis === 'peleburan' && r.biaya_terkait > 0) ? Number(r.biaya_terkait) : Number(r.harga_beli || 0);
                akumulasiNilaiTabel += nilaiBaris;

                return `
                    <tr>
                        <td style="text-align: center; font-weight: 700;">${i + 1}</td>
                        <td style="font-weight: 700;">${escapeHtml(r.kode_aset)}</td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(r.nama_aset)}</div>
                            <div style="font-size: 9.5px; color: #475569;">Merk: ${escapeHtml(r.merk)} | SN: ${escapeHtml(r.nomor_seri)}</div>
                        </td>
                        <td>${escapeHtml(r.nama_kategori)}</td>
                        <td>${escapeHtml(r.nama_cabang)} (${escapeHtml(r.ruangan)})</td>
                        <td>${escapeHtml(r.penanggung_jawab)}</td>
                        <td>${escapeHtml(tglTampil)}</td>
                        <td style="text-align: center; font-weight: 700;">${r.unit}</td>
                        <td>${escapeHtml(r.status_aset)}</td>
                        <td style="text-align: right; font-weight: 700;">${formatRupiah(nilaiBaris)}</td>
                    </tr>
                `;
            }).join('');

            tfootPdf.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: right;">TOTAL KESELURUHAN (${totalAset} ASET)</td>
                    <td style="text-align: center;">${totalUnit}</td>
                    <td>-</td>
                    <td style="text-align: right;">${formatRupiah(akumulasiNilaiTabel)}</td>
                </tr>
            `;
        }

        // Buka Pop-Up Preview Laporan Resmi HANYA jika data > 0
        function bukaModalPreviewLaporan(modeFokus = 'pdf') {
            const rows = stateLaporan.filteredRows || [];
            const konfig = KONFIG_JENIS_LAPORAN[stateLaporan.jenis] || KONFIG_JENIS_LAPORAN.peminjaman;

            if (rows.length === 0) {
                tampilkanToastPeringatan(`Tidak dapat mencetak ${konfig.nama} karena datanya masih 0 di database.`);
                return;
            }

            renderDokumenPreviewResmi();
            const modal = document.getElementById('modalPreviewLaporan');
            const headerTxt = document.getElementById('modal-preview-header-text');
            if (headerTxt) {
                headerTxt.textContent = modeFokus === 'excel'
                    ? 'Preview Dokumen Laporan Resmi — Siap Diunduh ke Excel (.xls) atau Dicetak ke PDF'
                    : 'Preview Dokumen Laporan Resmi (Ukuran A4) — Siap Dicetak ke PDF';
            }
            if (modal) modal.classList.add('show');
        }

        function tutupModalPreviewLaporan() {
            const modal = document.getElementById('modalPreviewLaporan');
            if (modal) modal.classList.remove('show');
        }

        function toggleEditorKopSurat() {
            const drawer = document.getElementById('drawerEditorKop');
            if (drawer) drawer.classList.toggle('open');
        }

        function perbaruiKopSuratLive() {
            const induk = document.getElementById('inp-kop-induk')?.value || '';
            const pt = document.getElementById('inp-kop-pt')?.value || '';
            const unit = document.getElementById('inp-kop-unit')?.value || '';
            const alamat = document.getElementById('inp-kop-alamat')?.value || '';
            const kontak = document.getElementById('inp-kop-kontak')?.value || '';
            const pimpinan = document.getElementById('inp-kop-pimpinan')?.value || '';

            document.getElementById('teks-kop-induk').textContent = induk;
            document.getElementById('teks-kop-pt').textContent = pt;
            document.getElementById('teks-kop-unit').textContent = unit;
            document.getElementById('teks-kop-alamat').textContent = alamat;
            document.getElementById('teks-kop-kontak').textContent = kontak;
            document.getElementById('teks-sig-pimpinan').textContent = pimpinan;

            try {
                localStorage.setItem('kop_surat_laporan_resmi', JSON.stringify({ induk, pt, unit, alamat, kontak, pimpinan }));
            } catch (e) {}
        }

        function ubahLogoKop(inputEl, posisi) {
            if (!inputEl || !inputEl.files || !inputEl.files[0]) return;
            const reader = new FileReader();
            reader.onload = function (e) {
                const dataUrl = e.target.result;
                const targetBox = document.getElementById(posisi === 'kiri' ? 'box-logo-kiri' : 'box-logo-kanan');
                if (targetBox) {
                    targetBox.innerHTML = `<img src="${dataUrl}" alt="Logo ${posisi}">`;
                }
                try {
                    localStorage.setItem('logo_kop_' + posisi, dataUrl);
                } catch (err) {}
            };
            reader.readAsDataURL(inputEl.files[0]);
        }

        function muatPengaturanKopTersimpan() {
            try {
                const raw = localStorage.getItem('kop_surat_laporan_resmi');
                if (raw) {
                    const d = JSON.parse(raw);
                    if (d.induk) document.getElementById('inp-kop-induk').value = d.induk;
                    if (d.pt) document.getElementById('inp-kop-pt').value = d.pt;
                    if (d.unit) document.getElementById('inp-kop-unit').value = d.unit;
                    if (d.alamat) document.getElementById('inp-kop-alamat').value = d.alamat;
                    if (d.kontak) document.getElementById('inp-kop-kontak').value = d.kontak;
                    if (d.pimpinan) document.getElementById('inp-kop-pimpinan').value = d.pimpinan;
                    perbaruiKopSuratLive();
                }
                const logoKiri = localStorage.getItem('logo_kop_kiri');
                if (logoKiri) {
                    document.getElementById('box-logo-kiri').innerHTML = `<img src="${logoKiri}" alt="Logo Kiri">`;
                }
                const logoKanan = localStorage.getItem('logo_kop_kanan');
                if (logoKanan) {
                    document.getElementById('box-logo-kanan').innerHTML = `<img src="${logoKanan}" alt="Logo Kanan">`;
                }
            } catch (e) {}
        }

        function catatLogCetakKeServer(format) {
            try {
                const konfig = KONFIG_JENIS_LAPORAN[stateLaporan.jenis] || KONFIG_JENIS_LAPORAN.peminjaman;
                const urlLog = new URL(window.location.href);
                urlLog.searchParams.set('_log_cetak_laporan', '1');
                urlLog.searchParams.set('format', format);
                urlLog.searchParams.set('jenis_label', konfig.nama);
                urlLog.searchParams.set('periode_label', ambilLabelPeriodeAktif());
                fetch(urlLog.toString(), { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
            } catch (e) {}
        }

        function eksekusiCetakPdfResmi() {
            const rows = stateLaporan.filteredRows || [];
            if (rows.length === 0) {
                tampilkanToastPeringatan('Tidak dapat mencetak PDF karena tidak ada data (0 data).');
                return;
            }
            catatLogCetakKeServer('PDF');
            window.print();
        }

        function eksekusiUnduhExcelResmi() {
            const rows = stateLaporan.filteredRows || [];
            if (rows.length === 0) {
                tampilkanToastPeringatan('Tidak dapat mengekspor Excel karena tidak ada data (0 data).');
                return;
            }

            catatLogCetakKeServer('EXCEL');
            const jenis = stateLaporan.jenis;
            const konfig = KONFIG_JENIS_LAPORAN[jenis] || KONFIG_JENIS_LAPORAN.peminjaman;

            const pt = document.getElementById('teks-kop-pt')?.textContent || 'PT MITRA ASET NUSANTARA';
            const unit = document.getElementById('teks-kop-unit')?.textContent || '';
            const alamat = document.getElementById('teks-kop-alamat')?.textContent || '';
            const kontak = document.getElementById('teks-kop-kontak')?.textContent || '';
            const nomorDok = document.getElementById('pdf-nomor-dokumen')?.textContent || '';
            const periode = document.getElementById('pdf-meta-periode')?.textContent || '';
            const cabang = document.getElementById('pdf-meta-cabang')?.textContent || 'Semua Cabang';
            const tglCetak = document.getElementById('pdf-meta-tglcetak')?.textContent || '';
            const pimpinan = document.getElementById('teks-sig-pimpinan')?.textContent || '';

            const totalUnit = rows.reduce((s, r) => s + (Number(r.unit) || 1), 0);
            let totalNilai = 0;

            const barisHtml = rows.map((r, idx) => {
                const tglTampil = formatTanggalIndo(r.tanggal);
                const nilaiBaris = (jenis === 'peleburan' && r.biaya_terkait > 0) ? Number(r.biaya_terkait) : Number(r.harga_beli || 0);
                totalNilai += nilaiBaris;
                return `
                    <tr>
                        <td style="border:1px solid #000; text-align:center;">${idx + 1}</td>
                        <td style="border:1px solid #000; font-weight:bold;">${escapeHtml(r.kode_aset)}</td>
                        <td style="border:1px solid #000;">${escapeHtml(r.nama_aset)} (Merk: ${escapeHtml(r.merk)})</td>
                        <td style="border:1px solid #000;">${escapeHtml(r.nama_kategori)}</td>
                        <td style="border:1px solid #000;">${escapeHtml(r.nama_cabang)} - ${escapeHtml(r.ruangan)}</td>
                        <td style="border:1px solid #000;">${escapeHtml(r.penanggung_jawab)}</td>
                        <td style="border:1px solid #000;">${escapeHtml(tglTampil)}</td>
                        <td style="border:1px solid #000; text-align:center;">${r.unit}</td>
                        <td style="border:1px solid #000;">${escapeHtml(r.status_aset)}</td>
                        <td style="border:1px solid #000; text-align:right;">${formatRupiah(nilaiBaris)}</td>
                    </tr>
                `;
            }).join('');

            const excelHtml = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                </head>
                <body style="font-family: Arial, sans-serif; font-size: 11pt;">
                    <table border="0" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse;">
                        <tr><td colspan="10" style="text-align:center; font-size:16pt; font-weight:bold;">${escapeHtml(pt)}</td></tr>
                        <tr><td colspan="10" style="text-align:center; font-size:11pt; font-weight:bold;">${escapeHtml(unit)}</td></tr>
                        <tr><td colspan="10" style="text-align:center; font-size:10pt;">${escapeHtml(alamat)}</td></tr>
                        <tr><td colspan="10" style="text-align:center; font-size:10pt; border-bottom:3px double #000;">${escapeHtml(kontak)}</td></tr>
                        <tr><td colspan="10"></td></tr>
                        <tr><td colspan="10" style="text-align:center; font-size:13pt; font-weight:bold; text-decoration:underline;">${escapeHtml(konfig.judulPdf)}</td></tr>
                        <tr><td colspan="10" style="text-align:center; font-size:10pt; font-weight:bold;">${escapeHtml(nomorDok)}</td></tr>
                        <tr><td colspan="10"></td></tr>
                        <tr>
                            <td colspan="5"><strong>Periode Laporan:</strong> ${escapeHtml(periode)}</td>
                            <td colspan="5"><strong>Tanggal Cetak:</strong> ${escapeHtml(tglCetak)}</td>
                        </tr>
                        <tr>
                            <td colspan="5"><strong>Cakupan Cabang:</strong> ${escapeHtml(cabang)}</td>
                            <td colspan="5"><strong>Total Aset Tercatat:</strong> ${rows.length} Aset (${totalUnit} Unit)</td>
                        </tr>
                        <tr><td colspan="10"></td></tr>
                        <thead>
                            <tr style="background-color:#1a4f91; color:#ffffff; font-weight:bold;">
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">NO</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">KODE ASET</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">NAMA ASET & MERK</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">KATEGORI</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">CABANG / RUANGAN</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">PENANGGUNG JAWAB</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">TANGGAL</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">UNIT</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">STATUS</th>
                                <th style="border:1px solid #000; background:#1a4f91; color:#fff;">NILAI (RP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${barisHtml}
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:bold; background-color:#e2e8f0;">
                                <td colspan="7" style="border:1px solid #000; text-align:right; font-weight:bold;">TOTAL KESELURUHAN (${rows.length} ASET)</td>
                                <td style="border:1px solid #000; text-align:center; font-weight:bold;">${totalUnit}</td>
                                <td style="border:1px solid #000;">-</td>
                                <td style="border:1px solid #000; text-align:right; font-weight:bold;">${formatRupiah(totalNilai)}</td>
                            </tr>
                        </tfoot>
                        <tr><td colspan="10"></td></tr>
                        <tr><td colspan="10"></td></tr>
                        <tr>
                            <td colspan="5" style="text-align:center;">Mengetahui & Menyetujui,<br><strong>Pimpinan Operasional / Mitra</strong></td>
                            <td colspan="5" style="text-align:center;">Sidoarjo, ${escapeHtml(tglCetak)}<br><strong>Disusun & Disahkan Oleh,</strong></td>
                        </tr>
                        <tr><td colspan="10" style="height:45px;"></td></tr>
                        <tr>
                            <td colspan="5" style="text-align:center; font-weight:bold; text-decoration:underline;">${escapeHtml(pimpinan)}</td>
                            <td colspan="5" style="text-align:center; font-weight:bold; text-decoration:underline;">${escapeHtml(NAMA_USER_CETAK)}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align:center; font-size:9pt;">Perwakilan Resmi ${escapeHtml(pt)}</td>
                            <td colspan="5" style="text-align:center; font-size:9pt;">${escapeHtml(JABATAN_USER_CETAK)}</td>
                        </tr>
                    </table>
                </body>
                </html>
            `;

            const blob = new Blob(['\ufeff', excelHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const link = document.createElement('a');
            const namaFile = `${konfig.nama.replace(/\s+/g, '_')}_${stateLaporan.template}.xls`;
            link.href = URL.createObjectURL(blob);
            link.download = namaFile;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        window.addEventListener('click', function (e) {
            const modal = document.getElementById('modalPreviewLaporan');
            if (e.target === modal) {
                tutupModalPreviewLaporan();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            muatPengaturanKopTersimpan();
            // Default awal: Laporan Peminjaman Aset dalam 1 Bulan Terakhir
            terapkanTemplateWaktu('1_bulan');
            pilihJenisLaporan('peminjaman');
        });
    </script>
</body>
</html>