@php
    // PROTEKSI MIDDLEWARE & ROLE GUARD: Halaman Pengajuan khusus untuk Admin!
    // Jika Master Admin mencoba mengakses /pengajuan lewat URL, arahkan kembali ke Dashboard Master Admin.
    $__cekRolePengajuan = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';

    if ($__cekRolePengajuan === 'master_admin' || $__cekRolePengajuan === 'masteradmin') {
        if (class_exists(\App\Models\RiwayatAktivitas::class) && \Illuminate\Support\Facades\Auth::check()) {
            try {
                $logTolakPengajuan = new \App\Models\RiwayatAktivitas();
                $tblTolakPengajuan = $logTolakPengajuan->getTable();
                $dataTolakPengajuan = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolakPengajuan, 'user_id')) {
                    $dataTolakPengajuan['user_id'] = \Illuminate\Support\Facades\Auth::id();
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolakPengajuan, 'aktivitas')) {
                    $dataTolakPengajuan['aktivitas'] = 'Mencoba mengakses halaman Pengajuan Admin tanpa hak akses';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolakPengajuan, 'modul')) {
                    $dataTolakPengajuan['modul'] = 'Pengajuan';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolakPengajuan, 'status')) {
                    $dataTolakPengajuan['status'] = 'Peringatan';
                }
                $logTolakPengajuan->forceFill($dataTolakPengajuan)->save();
            } catch (\Throwable $e) {
            }
        }
        $targetMasterUrl = \Illuminate\Support\Facades\Route::has('masteradmin.dashboard')
            ? route('masteradmin.dashboard')
            : url('/masteradmin');
        header('Location: ' . $targetMasterUrl);
        exit;
    }

    session(['active_sidebar_panel' => 'admin']);

    // Baca konfigurasi hak akses per-pengajuan dari storage/app/roles_permissions.json
    $rolesStoragePath = storage_path('app/roles_permissions.json');
    $izinPengajuan = [
        'pengajuan'              => true,
        'pengajuan_peminjaman'   => true,
        'pengajuan_pengembalian' => true,
        'pengajuan_peleburan'    => true,
        'pengajuan_servis'       => true,
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

    // Tentukan tab jenis pengajuan yang diminta dari query URL (?jenis=peminjaman|pengembalian|peleburan|servis)
    $daftarJenisValid = ['peminjaman', 'pengembalian', 'peleburan', 'servis'];
    $adaQueryJenisEksplisit = request()->has('jenis') || request()->has('pengajuan');
    $jenisAktif = strtolower((string) request()->query('jenis', request()->query('pengajuan', 'peminjaman')));
    if (!in_array($jenisAktif, $daftarJenisValid, true)) {
        $jenisAktif = 'peminjaman';
    }

    // Jika user membuka /pengajuan tanpa parameter ?jenis= dan peminjaman sedang ditutup,
    // arahkan ke tab pertama yang masih terbuka (namun jika tidak ada atau diminta spesifik, tampilkan Akses Dicabut)
    if (!$adaQueryJenisEksplisit && empty($izinPengajuan['pengajuan_' . $jenisAktif])) {
        foreach ($daftarJenisValid as $jKandidat) {
            if (!empty($izinPengajuan['pengajuan_' . $jKandidat])) {
                $jenisAktif = $jKandidat;
                break;
            }
        }
    }

    $semuaSubTertutup = empty($izinPengajuan['pengajuan_peminjaman'])
        && empty($izinPengajuan['pengajuan_pengembalian'])
        && empty($izinPengajuan['pengajuan_peleburan'])
        && empty($izinPengajuan['pengajuan_servis']);
    $menuUtamaPengajuanBoleh = !empty($izinPengajuan['pengajuan']) && !$semuaSubTertutup;
    $tabAktifSaatIniBoleh = $menuUtamaPengajuanBoleh && !empty($izinPengajuan['pengajuan_' . $jenisAktif]);

    $labelNamaPengajuan = [
        'peminjaman'   => 'Pengajuan Peminjaman',
        'pengembalian' => 'Pengajuan Pengembalian',
        'peleburan'    => 'Pengajuan Peleburan',
        'servis'       => 'Pengajuan Servis',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Aset - Sistem Manajemen Aset</title>
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
            padding: 25px 30px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 {
            font-size: 25px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 5px;
        }

        /* 4 Tombol Utama Pilihan Pengajuan */
        .pengajuan-top-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
        }

        .btn-tab-pengajuan {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 18px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #1e293b;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            text-decoration: none;
        }

        .btn-tab-pengajuan:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-tab-pengajuan.active {
            background: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 6px 14px rgba(26, 79, 145, 0.22);
        }

        /* Panel Konten Masing-Masing Pengajuan */
        .pengajuan-section {
            display: none;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 16px;
            padding: 25px;
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

        .pengajuan-section-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pengajuan-section-desc {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 18px;
        }

        .pengajuan-placeholder-box {
            padding: 32px 20px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        /* Kartu Peringatan Akses Sub-Pengajuan Dicabut */
        .sub-pengajuan-revoked-box {
            display: none;
            background: #ffffff;
            border: 1px solid #fecaca;
            border-top: 5px solid #ef4444;
            border-radius: 16px;
            padding: 40px 28px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.08);
            animation: fadeInSection 0.25s ease;
        }

        .sub-pengajuan-revoked-box.active {
            display: block;
        }

        .btn-back-dashboard {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary-color);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-back-dashboard:hover {
            opacity: 0.92;
        }

        @media (max-width: 768px) {
            main {
                margin-left: 0;
                width: 100%;
                padding: 18px;
            }
        }
    </style>
</head>

<body>

    @include('layouts.adminsidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>Menu Pengajuan Aset</h1>
                <p>Pilih jenis pengajuan aset di bawah ini (Peminjaman, Pengembalian, Peleburan, atau Servis).</p>
            </div>
        </header>

        <!-- 4 BUTTON UTAMA PENGAJUAN -->
        <div class="pengajuan-top-buttons"
             id="bar-tombol-pengajuan"
             style="{{ !$menuUtamaPengajuanBoleh ? 'display:none;' : '' }}">
            <button type="button"
                    class="btn-tab-pengajuan {{ $jenisAktif === 'peminjaman' ? 'active' : '' }}"
                    data-tab-jenis="peminjaman"
                    data-subperm-key="pengajuan_peminjaman"
                    style="{{ empty($izinPengajuan['pengajuan_peminjaman']) ? 'display:none;' : '' }}"
                    onclick="bukaTabPengajuan('peminjaman')">
                <span>📦</span>
                <span>Pengajuan Peminjaman</span>
            </button>

            <button type="button"
                    class="btn-tab-pengajuan {{ $jenisAktif === 'pengembalian' ? 'active' : '' }}"
                    data-tab-jenis="pengembalian"
                    data-subperm-key="pengajuan_pengembalian"
                    style="{{ empty($izinPengajuan['pengajuan_pengembalian']) ? 'display:none;' : '' }}"
                    onclick="bukaTabPengajuan('pengembalian')">
                <span>🔄</span>
                <span>Pengajuan Pengembalian</span>
            </button>

            <button type="button"
                    class="btn-tab-pengajuan {{ $jenisAktif === 'peleburan' ? 'active' : '' }}"
                    data-tab-jenis="peleburan"
                    data-subperm-key="pengajuan_peleburan"
                    style="{{ empty($izinPengajuan['pengajuan_peleburan']) ? 'display:none;' : '' }}"
                    onclick="bukaTabPengajuan('peleburan')">
                <span>🔥</span>
                <span>Pengajuan Peleburan</span>
            </button>

            <button type="button"
                    class="btn-tab-pengajuan {{ $jenisAktif === 'servis' ? 'active' : '' }}"
                    data-tab-jenis="servis"
                    data-subperm-key="pengajuan_servis"
                    style="{{ empty($izinPengajuan['pengajuan_servis']) ? 'display:none;' : '' }}"
                    onclick="bukaTabPengajuan('servis')">
                <span>🛠️</span>
                <span>Pengajuan Servis</span>
            </button>
        </div>

        <!-- KOTAK PERINGATAN JIKA SUB-PENGAJUAN YANG SEDANG DIBUKA DICABUT OLEH MASTER ADMIN -->
        <div id="box-akses-pengajuan-dicabut" class="sub-pengajuan-revoked-box {{ !$tabAktifSaatIniBoleh ? 'active' : '' }}">
            <div style="font-size: 44px; margin-bottom: 12px;">🚫</div>
            <h2 id="judul-akses-pengajuan-dicabut" style="color: #0f172a; font-size: 20px; font-weight: 800; margin-bottom: 8px;">
                Hak Akses {{ $labelNamaPengajuan[$jenisAktif] ?? 'Pengajuan' }} Dicabut
            </h2>
            <p id="pesan-akses-pengajuan-dicabut" style="color: #64748b; font-size: 13.5px; line-height: 1.6; max-width: 520px; margin: 0 auto 22px auto;">
                Master Admin telah menutup / menonaktifkan akses pada menu <strong>{{ $labelNamaPengajuan[$jenisAktif] ?? 'Pengajuan' }}</strong> untuk role Anda secara real-time. Silakan pilih kategori pengajuan lain yang masih tersedia atau kembali ke Dashboard.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('admin.dashboard') }}" class="btn-back-dashboard">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- ================= 1. PENGAJUAN PEMINJAMAN ================= -->
        <section id="section-pengajuan-peminjaman"
                 class="pengajuan-section {{ ($jenisAktif === 'peminjaman' && $tabAktifSaatIniBoleh) ? 'active' : '' }}"
                 data-section-jenis="peminjaman">
            <h2 class="pengajuan-section-title">📦 Pengajuan Peminjaman</h2>
            <p class="pengajuan-section-desc">Kelola dan ajukan permohonan peminjaman aset inventaris antar cabang atau divisi.</p>
            <div class="pengajuan-placeholder-box">
                Silakan pilih atau kelola data pada kategori <strong style="color: var(--primary-color);">Pengajuan Peminjaman</strong>.
            </div>
        </section>

        <!-- ================= 2. PENGAJUAN PENGEMBALIAN ================= -->
        <section id="section-pengajuan-pengembalian"
                 class="pengajuan-section {{ ($jenisAktif === 'pengembalian' && $tabAktifSaatIniBoleh) ? 'active' : '' }}"
                 data-section-jenis="pengembalian">
            <h2 class="pengajuan-section-title">🔄 Pengajuan Pengembalian</h2>
            <p class="pengajuan-section-desc">Kelola proses pengembalian aset yang telah selesai dipinjam beserta pengecekan kondisinya.</p>
            <div class="pengajuan-placeholder-box">
                Silakan pilih atau kelola data pada kategori <strong style="color: var(--primary-color);">Pengajuan Pengembalian</strong>.
            </div>
        </section>

        <!-- ================= 3. PENGAJUAN PELEBURAN ================= -->
        <section id="section-pengajuan-peleburan"
                 class="pengajuan-section {{ ($jenisAktif === 'peleburan' && $tabAktifSaatIniBoleh) ? 'active' : '' }}"
                 data-section-jenis="peleburan">
            <h2 class="pengajuan-section-title">🔥 Pengajuan Peleburan</h2>
            <p class="pengajuan-section-desc">Ajukan penghapusan atau peleburan aset fisik yang sudah rusak berat / tidak layak pakai.</p>
            <div class="pengajuan-placeholder-box">
                Silakan pilih atau kelola data pada kategori <strong style="color: var(--primary-color);">Pengajuan Peleburan</strong>.
            </div>
        </section>

        <!-- ================= 4. PENGAJUAN SERVIS ================= -->
        <section id="section-pengajuan-servis"
                 class="pengajuan-section {{ ($jenisAktif === 'servis' && $tabAktifSaatIniBoleh) ? 'active' : '' }}"
                 data-section-jenis="servis">
            <h2 class="pengajuan-section-title">🛠️ Pengajuan Servis</h2>
            <p class="pengajuan-section-desc">Ajukan perbaikan dan perawatan berkala (maintenance) untuk aset yang bermasalah.</p>
            <div class="pengajuan-placeholder-box">
                Silakan pilih atau kelola data pada kategori <strong style="color: var(--primary-color);">Pengajuan Servis</strong>.
            </div>
        </section>
    </main>

    <script>
        const NAMA_KATEGORI_PENGAJUAN = {
            peminjaman: 'Pengajuan Peminjaman',
            pengembalian: 'Pengajuan Pengembalian',
            peleburan: 'Pengajuan Peleburan',
            servis: 'Pengajuan Servis'
        };

        window.jenisPengajuanAktif = @json($jenisAktif);

        window.sinkronkanStatusAksesPengajuan = function (perms) {
            const jenis = window.jenisPengajuanAktif || 'peminjaman';
            const namaLabel = NAMA_KATEGORI_PENGAJUAN[jenis] || 'Pengajuan';

            const subKeys = ['pengajuan_peminjaman', 'pengajuan_pengembalian', 'pengajuan_peleburan', 'pengajuan_servis'];
            const adaSubBoleh = subKeys.some(sk => perms && perms[sk] ? !!perms[sk].view : true);
            const menuUtamaBoleh = (perms && perms.pengajuan ? !!perms.pengajuan.view : true) && adaSubBoleh;
            const subIniBoleh = menuUtamaBoleh && (perms && perms['pengajuan_' + jenis] ? !!perms['pengajuan_' + jenis].view : true);

            const barTombol = document.getElementById('bar-tombol-pengajuan');
            if (barTombol) {
                barTombol.style.display = menuUtamaBoleh ? 'grid' : 'none';
            }

            const boxDicabut = document.getElementById('box-akses-pengajuan-dicabut');
            const judulDicabut = document.getElementById('judul-akses-pengajuan-dicabut');
            const pesanDicabut = document.getElementById('pesan-akses-pengajuan-dicabut');

            if (!subIniBoleh) {
                // Sembunyikan semua section konten pengajuan dan tampilkan kotak "Hak Akses Dicabut"
                document.querySelectorAll('.pengajuan-section').forEach(sec => sec.classList.remove('active'));
                if (boxDicabut) {
                    boxDicabut.classList.add('active');
                }
                if (judulDicabut) {
                    judulDicabut.textContent = menuUtamaBoleh
                        ? `Hak Akses ${namaLabel} Dicabut`
                        : 'Hak Akses Menu Pengajuan Dicabut';
                }
                if (pesanDicabut) {
                    pesanDicabut.innerHTML = menuUtamaBoleh
                        ? `Master Admin telah menutup / menonaktifkan akses pada menu <strong>${namaLabel}</strong> untuk role Anda secara real-time. Silakan pilih kategori pengajuan lain yang masih tersedia atau kembali ke Dashboard.`
                        : `Master Admin telah menutup seluruh akses pada menu <strong>Pengajuan Aset</strong> untuk role Anda secara real-time.`;
                }
            } else {
                if (boxDicabut) {
                    boxDicabut.classList.remove('active');
                }
                document.querySelectorAll('.pengajuan-section').forEach(sec => {
                    sec.classList.toggle('active', sec.getAttribute('data-section-jenis') === jenis);
                });
            }
        };

        window.bukaTabPengajuan = function (jenis, updateUrl = true) {
            const validJenis = ['peminjaman', 'pengembalian', 'peleburan', 'servis'];
            if (!validJenis.includes(jenis)) {
                jenis = 'peminjaman';
            }

            window.jenisPengajuanAktif = jenis;

            // Aktifkan tombol tab yang sesuai
            document.querySelectorAll('.btn-tab-pengajuan').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab-jenis') === jenis);
            });

            // Sinkronkan highlight sub-button di sidebar
            document.querySelectorAll('.submenu-pengajuan-btn').forEach(subBtn => {
                subBtn.classList.toggle('active', subBtn.getAttribute('data-jenis') === jenis);
            });

            const wrapSidebar = document.getElementById('wrap-menu-pengajuan');
            if (wrapSidebar) {
                wrapSidebar.classList.add('open');
            }

            // Cek apakah kategori pengajuan ini diizinkan menurut state permission real-time saat ini
            const permsSaatIni = window.__adminCurrentPerms || null;
            if (permsSaatIni) {
                window.sinkronkanStatusAksesPengajuan(permsSaatIni);
            } else {
                document.querySelectorAll('.pengajuan-section').forEach(sec => {
                    sec.classList.toggle('active', sec.getAttribute('data-section-jenis') === jenis);
                });
            }

            // Perbarui URL di address bar menjadi /pengajuan?jenis=... tanpa reload penuh
            if (updateUrl && window.history && window.history.replaceState) {
                const urlBaru = new URL(window.location.href);
                urlBaru.searchParams.delete('pengajuan');
                urlBaru.searchParams.set('jenis', jenis);
                window.history.replaceState({ jenis: jenis }, '', urlBaru.toString());
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            const params = new URLSearchParams(window.location.search);
            const jenisAwal = params.get('jenis') || params.get('pengajuan') || @json($jenisAktif);
            window.bukaTabPengajuan(jenisAwal, false);
        });
    </script>
</body>

</html>