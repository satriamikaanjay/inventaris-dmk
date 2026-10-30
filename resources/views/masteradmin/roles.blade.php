@php
    // PROTEKSI MIDDLEWARE & ROLE GUARD: Halaman Roles & Permission HANYA boleh diakses oleh Master Admin!
    $__cekRoleUserRoles = \Illuminate\Support\Facades\Auth::check()
        ? strtolower(str_replace(' ', '_', trim((string) (\Illuminate\Support\Facades\Auth::user()->role ?? ''))))
        : '';

    if ($__cekRoleUserRoles !== 'master_admin' && $__cekRoleUserRoles !== 'masteradmin') {
        if (class_exists(\App\Models\RiwayatAktivitas::class) && \Illuminate\Support\Facades\Auth::check()) {
            try {
                $logTolak = new \App\Models\RiwayatAktivitas();
                $tblTolak = $logTolak->getTable();
                $dataTolak = [];
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolak, 'user_id')) {
                    $dataTolak['user_id'] = \Illuminate\Support\Facades\Auth::id();
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolak, 'aktivitas')) {
                    $dataTolak['aktivitas'] = 'Mencoba mengakses menu Roles & Permission tanpa hak akses';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolak, 'modul')) {
                    $dataTolak['modul'] = 'Roles & Permission';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn($tblTolak, 'status')) {
                    $dataTolak['status'] = 'Peringatan';
                }
                $logTolak->forceFill($dataTolak)->save();
            } catch (\Throwable $e) {
            }
        }
        $targetRedirectAdmin = \Illuminate\Support\Facades\Route::has('admin.dashboard')
            ? route('admin.dashboard')
            : url('/admin-dashboard');
        header('Location: ' . $targetRedirectAdmin);
        exit;
    }

    session(['active_sidebar_panel' => 'masteradmin']);

    $rolesStoragePath = storage_path('app/roles_permissions.json');

    // Daftar 10 modul beserta kapabilitas aksi nyata yang ada di masing-masing halaman
    $daftarModulSistem = [
        'dashboard' => [
            'nama'      => 'Dashboard',
            'grup'      => 'MENU UTAMA',
            'deskripsi' => 'Halaman ringkasan statistik dan pemantauan aset per cabang',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Menu'],
        ],
        'pengajuan' => [
            'nama'      => 'Pengajuan (Menu Sidebar)',
            'grup'      => 'MENU PENGAJUAN ASET',
            'deskripsi' => 'Tampilkan atau tutup menu utama Pengajuan di sidebar Admin',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Menu'],
        ],
        'pengajuan_peminjaman' => [
            'nama'      => '↳ Pengajuan Peminjaman',
            'grup'      => 'MENU PENGAJUAN ASET',
            'deskripsi' => 'Buka atau tutup akses tombol & fitur Pengajuan Peminjaman',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Pengajuan'],
        ],
        'pengajuan_pengembalian' => [
            'nama'      => '↳ Pengajuan Pengembalian',
            'grup'      => 'MENU PENGAJUAN ASET',
            'deskripsi' => 'Buka atau tutup akses tombol & fitur Pengajuan Pengembalian',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Pengajuan'],
        ],
        'pengajuan_peleburan' => [
            'nama'      => '↳ Pengajuan Peleburan',
            'grup'      => 'MENU PENGAJUAN ASET',
            'deskripsi' => 'Buka atau tutup akses tombol & fitur Pengajuan Peleburan',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Pengajuan'],
        ],
        'pengajuan_servis' => [
            'nama'      => '↳ Pengajuan Servis',
            'grup'      => 'MENU PENGAJUAN ASET',
            'deskripsi' => 'Buka atau tutup akses tombol & fitur Pengajuan Servis',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Pengajuan'],
        ],
        'notifikasi' => [
            'nama'      => 'Notifikasi',
            'grup'      => 'MENU UTAMA',
            'deskripsi' => 'Daftar peringatan jadwal servis dan aset rusak',
            'caps'      => ['view'],
            'labels'    => ['view' => 'Buka Menu'],
        ],
        'data_aset' => [
            'nama'      => 'Data Aset',
            'grup'      => 'MANAJEMEN ASET',
            'deskripsi' => 'Tabel inventaris fisik aset, tambah/import, edit detail, dan hapus aset',
            'caps'      => ['view', 'create', 'edit', 'delete'],
            'labels'    => ['view' => 'Buka Menu', 'create' => 'Tambah & Import', 'edit' => 'Edit Detail', 'delete' => 'Hapus Aset'],
        ],
        'barcode_aset' => [
            'nama'      => 'Barcode Aset',
            'grup'      => 'MANAJEMEN ASET',
            'deskripsi' => 'Lihat/unduh katalog QR, buat label QR baru, dan update QR aset',
            'caps'      => ['view', 'create', 'edit'],
            'labels'    => ['view' => 'Lihat & Download', 'create' => 'Buat / Generate QR', 'edit' => 'Update QR'],
        ],
        'kategori_aset' => [
            'nama'      => 'Kategori Aset',
            'grup'      => 'MASTER DATA',
            'deskripsi' => 'Kelola kategori aset, master nama aset (kode urut), dan status aset',
            'caps'      => ['view', 'create', 'edit', 'delete'],
            'labels'    => ['view' => 'Buka Menu', 'create' => 'Tambah Baru', 'edit' => 'Edit Inline', 'delete' => 'Hapus Data'],
        ],
        'cabang' => [
            'nama'      => 'Cabang',
            'grup'      => 'MASTER DATA',
            'deskripsi' => 'Kelola daftar lokasi kantor cabang',
            'caps'      => ['view', 'create', 'edit', 'delete'],
            'labels'    => ['view' => 'Buka Menu', 'create' => 'Tambah Cabang', 'edit' => 'Edit Cabang', 'delete' => 'Hapus Cabang'],
        ],
        'divisi' => [
            'nama'      => 'Divisi',
            'grup'      => 'MASTER DATA',
            'deskripsi' => 'Kelola daftar divisi organisasi',
            'caps'      => ['view', 'create', 'edit', 'delete'],
            'labels'    => ['view' => 'Buka Menu', 'create' => 'Tambah Divisi', 'edit' => 'Edit Divisi', 'delete' => 'Hapus Divisi'],
        ],
        'jabatan' => [
            'nama'      => 'Jabatan',
            'grup'      => 'MASTER DATA',
            'deskripsi' => 'Kelola daftar jabatan pengguna',
            'caps'      => ['view', 'create', 'edit', 'delete'],
            'labels'    => ['view' => 'Buka Menu', 'create' => 'Tambah Jabatan', 'edit' => 'Edit Jabatan', 'delete' => 'Hapus Jabatan'],
        ],
        'laporan' => [
            'nama'      => 'Laporan',
            'grup'      => 'LAPORAN & PEMANTAUAN',
            'deskripsi' => 'Lihat rekapitulasi laporan dan ekspor/unduh dokumen laporan',
            'caps'      => ['view', 'create'],
            'labels'    => ['view' => 'Lihat Laporan', 'create' => 'Ekspor / Cetak'],
        ],
        'riwayat_aktivitas' => [
            'nama'      => 'Riwayat Aktivitas',
            'grup'      => 'LAPORAN & PEMANTAUAN',
            'deskripsi' => 'Pantau catatan log aktivitas sistem dan tombol bersihkan log',
            'caps'      => ['view', 'delete'],
            'labels'    => ['view' => 'Lihat Log', 'delete' => 'Bersihkan Log'],
        ],
    ];

    $buatDefaultPermission = function (bool $view = true, bool $create = true, bool $edit = true, bool $delete = true) use ($daftarModulSistem) {
        $perms = [];
        foreach ($daftarModulSistem as $key => $info) {
            $caps = $info['caps'];
            $perms[$key] = [
                'view'   => $view,
                'create' => in_array('create', $caps, true) ? $create : false,
                'edit'   => in_array('edit', $caps, true) ? $edit : false,
                'delete' => in_array('delete', $caps, true) ? $delete : false,
            ];
        }
        return $perms;
    };

    $configRoles = null;
    if (is_file($rolesStoragePath)) {
        $rawJson = file_get_contents($rolesStoragePath);
        $decoded = json_decode((string) $rawJson, true);
        if (is_array($decoded) && isset($decoded['roles']) && is_array($decoded['roles'])) {
            $configRoles = $decoded;
        }
    }

    $perluSimpanBersih = false;
    if (!$configRoles) {
        $configRoles = [
            'updated_at' => round(microtime(true) * 1000),
            'roles' => [
                'admin' => [
                    'key'         => 'admin',
                    'name'        => 'Admin',
                    'badge_color' => '#10b981',
                    'desc'        => 'Mengelola operasional aset, master data, dan pemantauan sesuai izin dari Master Admin.',
                    'is_system'   => true,
                    'locked'      => false,
                    'permissions' => $buatDefaultPermission(true, true, true, true),
                ],
            ],
        ];
        $perluSimpanBersih = true;
    }

    // Hapus role Master Admin dari daftar Roles & Permission jika masih tersimpan di JSON lama,
    // karena Master Admin adalah pemegang kendali penuh sistem dan hanya mengatur role Admin / User.
    if (isset($configRoles['roles']['master_admin'])) {
        unset($configRoles['roles']['master_admin']);
        $perluSimpanBersih = true;
    }
    if (isset($configRoles['roles']['masteradmin'])) {
        unset($configRoles['roles']['masteradmin']);
        $perluSimpanBersih = true;
    }

    // Pastikan role Admin selalu tersedia sebagai role utama yang diatur sementara User belum dibuat
    if (empty($configRoles['roles'])) {
        $configRoles['roles']['admin'] = [
            'key'         => 'admin',
            'name'        => 'Admin',
            'badge_color' => '#10b981',
            'desc'        => 'Mengelola operasional aset, master data, dan pemantauan sesuai izin dari Master Admin.',
            'is_system'   => true,
            'locked'      => false,
            'permissions' => $buatDefaultPermission(true, true, true, true),
        ];
        $perluSimpanBersih = true;
    }

    foreach ($configRoles['roles'] as $rKey => &$rVal) {
        if (!isset($rVal['permissions']) || !is_array($rVal['permissions'])) {
            $rVal['permissions'] = $buatDefaultPermission(true, true, true, true);
        }
        foreach ($daftarModulSistem as $mKey => $mInfo) {
            $caps = $mInfo['caps'];
            if (!isset($rVal['permissions'][$mKey])) {
                $rVal['permissions'][$mKey] = [
                    'view'   => true,
                    'create' => in_array('create', $caps, true),
                    'edit'   => in_array('edit', $caps, true),
                    'delete' => in_array('delete', $caps, true),
                ];
            } else {
                $v = !empty($rVal['permissions'][$mKey]['view']);
                $rVal['permissions'][$mKey]['view']   = $v;
                $rVal['permissions'][$mKey]['create'] = $v && in_array('create', $caps, true) ? !empty($rVal['permissions'][$mKey]['create']) : false;
                $rVal['permissions'][$mKey]['edit']   = $v && in_array('edit', $caps, true) ? !empty($rVal['permissions'][$mKey]['edit']) : false;
                $rVal['permissions'][$mKey]['delete'] = $v && in_array('delete', $caps, true) ? !empty($rVal['permissions'][$mKey]['delete']) : false;
            }
        }
    }
    unset($rVal);

    $catatRiwayatRole = function (string $aktivitas, string $status = 'Berhasil') {
        if (!class_exists(\App\Models\RiwayatAktivitas::class)) {
            return;
        }
        try {
            $riwayat = new \App\Models\RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            $data = [];
            if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'user_id')) {
                $data['user_id'] = \Illuminate\Support\Facades\Auth::id();
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'aktivitas')) {
                $data['aktivitas'] = $aktivitas;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'modul')) {
                $data['modul'] = 'Roles & Permission';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($tbl, 'status')) {
                $data['status'] = $status;
            }
            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    };

    if (!is_file($rolesStoragePath) || $perluSimpanBersih) {
        try {
            file_put_contents($rolesStoragePath, json_encode($configRoles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
        }
    }

    if (request()->has('_roles_action')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $action = (string) request('_roles_action');

        if ($action === 'save_permissions') {
            $roleKey = strtolower(trim((string) request('role_key', '')));
            $rawPerms = (string) request('permissions_json', '');
            $decodedPerms = json_decode($rawPerms, true);

            if ($roleKey === '' || !isset($configRoles['roles'][$roleKey])) {
                echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan.']);
                exit;
            }
            if ($roleKey === 'master_admin') {
                echo json_encode(['success' => false, 'message' => 'Hak akses Master Admin bersifat permanen dan tidak dapat dibatasi.']);
                exit;
            }
            if (!is_array($decodedPerms)) {
                echo json_encode(['success' => false, 'message' => 'Format data hak akses tidak valid.']);
                exit;
            }

            foreach ($daftarModulSistem as $mKey => $mInfo) {
                $caps = $mInfo['caps'];
                if (isset($decodedPerms[$mKey]) && is_array($decodedPerms[$mKey])) {
                    $v = !empty($decodedPerms[$mKey]['view']);
                    $configRoles['roles'][$roleKey]['permissions'][$mKey] = [
                        'view'   => $v,
                        'create' => ($v && in_array('create', $caps, true)) ? !empty($decodedPerms[$mKey]['create']) : false,
                        'edit'   => ($v && in_array('edit', $caps, true)) ? !empty($decodedPerms[$mKey]['edit']) : false,
                        'delete' => ($v && in_array('delete', $caps, true)) ? !empty($decodedPerms[$mKey]['delete']) : false,
                    ];
                }
            }

            $configRoles['updated_at'] = round(microtime(true) * 1000);
            file_put_contents($rolesStoragePath, json_encode($configRoles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $ringkasanCatatan = trim((string) request('change_summary', ''));
            $namaRoleTampil = $configRoles['roles'][$roleKey]['name'] ?? $roleKey;
            $teksLog = 'Memperbarui hak akses (permissions) untuk role ' . $namaRoleTampil . ($ringkasanCatatan !== '' ? ': ' . $ringkasanCatatan : '');
            $catatRiwayatRole($teksLog, 'Informasi');

            echo json_encode([
                'success'    => true,
                'message'    => 'Hak akses role ' . $namaRoleTampil . ' berhasil diperbarui secara real-time!',
                'updated_at' => $configRoles['updated_at'],
                'role'       => $configRoles['roles'][$roleKey],
            ]);
            exit;
        }

        if ($action === 'add_role') {
            $namaRole = trim((string) request('role_name', ''));
            $deskripsi = trim((string) request('role_desc', ''));
            $warna = trim((string) request('badge_color', '#0ea5e9'));
            $template = trim((string) request('template_mode', 'readonly'));

            if ($namaRole === '') {
                echo json_encode(['success' => false, 'message' => 'Nama role wajib diisi.']);
                exit;
            }

            $slugKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $namaRole));
            $slugKey = trim($slugKey, '_');

            if ($slugKey === 'master_admin' || $slugKey === 'masteradmin') {
                echo json_encode(['success' => false, 'message' => 'Master Admin adalah pengelola utama sistem dan tidak perlu ditambahkan ke daftar role.']);
                exit;
            }
            if ($slugKey === '' || isset($configRoles['roles'][$slugKey])) {
                echo json_encode(['success' => false, 'message' => 'Role dengan nama tersebut sudah terdaftar!']);
                exit;
            }

            if ($template === 'full') {
                $permsBaru = $buatDefaultPermission(true, true, true, true);
            } elseif ($template === 'minimal') {
                $permsBaru = $buatDefaultPermission(false, false, false, false);
                $permsBaru['dashboard']['view'] = true;
            } else {
                $permsBaru = $buatDefaultPermission(true, false, false, false);
            }

            $configRoles['roles'][$slugKey] = [
                'key'         => $slugKey,
                'name'        => $namaRole,
                'badge_color' => $warna ?: '#0ea5e9',
                'desc'        => $deskripsi !== '' ? $deskripsi : ('Role ' . $namaRole . ' dengan pengaturan akses khusus.'),
                'is_system'   => false,
                'locked'      => false,
                'permissions' => $permsBaru,
            ];

            $configRoles['updated_at'] = round(microtime(true) * 1000);
            file_put_contents($rolesStoragePath, json_encode($configRoles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $catatRiwayatRole('Menambah role baru: ' . $namaRole . ' (' . $slugKey . ')', 'Berhasil');

            echo json_encode([
                'success'     => true,
                'message'     => 'Role baru "' . $namaRole . '" berhasil ditambahkan!',
                'role'        => $configRoles['roles'][$slugKey],
                'total_roles' => count($configRoles['roles']),
            ]);
            exit;
        }

        if ($action === 'delete_role') {
            $roleKey = strtolower(trim((string) request('role_key', '')));
            if ($roleKey === '' || !isset($configRoles['roles'][$roleKey])) {
                echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan.']);
                exit;
            }
            if ($roleKey === 'master_admin') {
                echo json_encode(['success' => false, 'message' => 'Role Master Admin tidak dapat dihapus!']);
                exit;
            }

            $namaDihapus = $configRoles['roles'][$roleKey]['name'] ?? $roleKey;
            unset($configRoles['roles'][$roleKey]);
            $configRoles['updated_at'] = round(microtime(true) * 1000);
            file_put_contents($rolesStoragePath, json_encode($configRoles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $catatRiwayatRole('Menghapus role: ' . $namaDihapus . ' (' . $roleKey . ')', 'Peringatan');

            echo json_encode([
                'success'     => true,
                'message'     => 'Role "' . $namaDihapus . '" berhasil dihapus!',
                'total_roles' => count($configRoles['roles']),
            ]);
            exit;
        }

        if ($action === 'unlock_admin_login') {
            $lockFile = storage_path('app/admin_login_locks.json');
            $resetData = [
                'failed_count' => 0,
                'is_blocked'   => false,
                'last_email'   => '',
                'blocked_at'   => null,
                'updated_at'   => round(microtime(true) * 1000),
            ];
            try {
                file_put_contents($lockFile, json_encode($resetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
            }
            $catatRiwayatRole('Membuka blokir akses login Admin (Reset 5x percobaan gagal login).', 'Berhasil');
            echo json_encode([
                'success' => true,
                'message' => 'Blokir login Admin berhasil dibuka! Admin kini dapat login kembali.',
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
        exit;
    }

    // Baca status blokir login Admin untuk ditampilkan ke Master Admin
    $statusBlokirLoginAdmin = ['failed_count' => 0, 'is_blocked' => false, 'last_email' => '', 'blocked_at' => null];
    $lockFileAdmin = storage_path('app/admin_login_locks.json');
    if (is_file($lockFileAdmin)) {
        $decLock = json_decode((string) file_get_contents($lockFileAdmin), true);
        if (is_array($decLock)) {
            $statusBlokirLoginAdmin = array_merge($statusBlokirLoginAdmin, $decLock);
        }
    }

    $jumlahUserPerRole = [];
    $totalSeluruhUser = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            $rowsUsers = \Illuminate\Support\Facades\DB::table('users')
                ->selectRaw('LOWER(TRIM(role)) as role_name, COUNT(*) as total')
                ->groupBy('role_name')
                ->get();
            foreach ($rowsUsers as $ru) {
                $rKeyNorm = str_replace(' ', '_', (string) $ru->role_name);
                $jml = (int) $ru->total;
                $jumlahUserPerRole[$rKeyNorm] = ($jumlahUserPerRole[$rKeyNorm] ?? 0) + $jml;
                $totalSeluruhUser += $jml;
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
    <title>Roles & Permission - Sistem Master Admin</title>
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
            --border-color: rgba(0, 0, 0, 0.12);
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
            gap: 22px;
            width: calc(100% - var(--sidebar-width));
        }

        @media (max-width: 768px) {
            main { margin-left: 0; width: 100%; padding: 15px; }
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 15px;
        }

        .header-title h1 {
            font-size: 25px;
            font-weight: 800;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-title p {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-top: 5px;
        }

        .live-sync-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-green 1.5s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 7px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 15px;
        }

        .kpi-card {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .kpi-card .kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-card .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .kpi-card .kpi-sub {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
        }

        .roles-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .roles-layout { grid-template-columns: 1fr; }
        }

        .card-box {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            padding-bottom: 12px;
            margin-bottom: 15px;
            border-bottom: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .role-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .role-card-item {
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .role-card-item:hover {
            border-color: #94a3b8;
            background: #fff;
        }

        .role-card-item.active {
            border-color: var(--primary-color);
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(26, 79, 145, 0.12);
        }

        .role-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .role-badge-name {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        .role-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .role-user-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #334155;
        }

        .role-card-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .role-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }

        .btn-delete-role {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-delete-role:hover {
            background: #dc2626;
            color: #fff;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 12px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .form-group input,
        .form-group select {
            padding: 9px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
            background: #fff;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 79, 145, 0.1);
        }

        .btn {
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.2s;
        }

        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-success { background: var(--secondary-color); color: #fff; }
        .btn-warning { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .btn-danger-soft { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .btn:hover { opacity: 0.92; }

        .perm-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
        }

        .perm-Preset-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .perm-table-wrap {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .perm-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .perm-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 12px;
            text-align: center;
            border-bottom: 1px solid #cbd5e1;
        }

        .perm-table th:first-child { text-align: left; }

        .perm-group-row td {
            background: #f8fafc;
            font-size: 11px;
            font-weight: 800;
            color: var(--primary-color);
            padding: 8px 14px;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .perm-row td {
            padding: 12px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            text-align: center;
            font-size: 13px;
            transition: background 0.15s;
        }

        .perm-row:hover td { background: #f8fafc; }

        .perm-row.row-disabled td:first-child .modul-title {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .perm-row td:first-child { text-align: left; }

        .modul-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modul-desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .cap-cell-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }

        .cap-sub-label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
        }

        .cap-na {
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            user-select: none;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 23px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: 0.25s;
            border-radius: 999px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 17px;
            width: 17px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.25s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        input:checked + .slider { background-color: var(--secondary-color); }
        input:checked + .slider.slider-view { background-color: var(--primary-color); }
        input:disabled + .slider { opacity: 0.45; cursor: not-allowed; }
        input:checked + .slider:before { transform: translateX(19px); }

        .preset-select-row {
            padding: 5px 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            font-weight: 700;
            background: #fff;
            color: #334155;
            cursor: pointer;
            outline: none;
        }

        .status-pill-akses {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .pill-full { background: #d1fae5; color: #065f46; }
        .pill-readonly { background: #fef3c7; color: #92400e; }
        .pill-custom { background: #e0f2fe; color: #0369a1; }
        .pill-hidden { background: #fee2e2; color: #991b1b; }

        /* COOLDOWN 2 DETIK ANTI-SPAM KLIK */
        .perm-cooldown-lock {
            pointer-events: none !important;
            opacity: 0.62;
            transition: opacity 0.2s ease;
        }
        body.perm-in-cooldown {
            cursor: progress !important;
        }

        .toast-popup {
            position: fixed;
            top: 25px;
            right: -100%;
            padding: 14px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            font-size: 13px;
            font-weight: 700;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-popup.show { right: 25px; }
        .toast-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .toast-error { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

    @include('layouts.mastersidebar')

    <main>
        <header class="main-header">
            <div class="header-title">
                <h1>
                    <span style="display: inline-flex; align-items: center; gap: 10px;"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg><span>Roles & Permission</span></span>
                    <span class="live-sync-badge" title="Setiap perubahan langsung diterapkan ke sisi Admin tanpa perlu refresh browser">
                        <span class="live-dot"></span> Real-Time Sync ke Admin Aktif
                    </span>
                </h1>
                <p>Kelola daftar role sistem, tambah/hapus role, serta atur pembatasan menu dan hak akses (Lihat, Tambah, Edit, Hapus) secara langsung.</p>
            </div>
        </header>

        <!-- BANNER BUKA BLOKIR LOGIN ADMIN (MUNCUL JIKA ADMIN TERBLOKIR KARENA 5X SALAH SANDI) -->
        <div id="banner-admin-terblokir" style="display: {{ !empty($statusBlokirLoginAdmin['is_blocked']) ? 'flex' : 'none' }}; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #fef2f2; border: 2px solid #ef4444; padding: 14px 20px; border-radius: 14px; color: #991b1b;">
            <div>
                <div style="font-size: 14px; font-weight: 800; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>Akses Login Admin Sedang Terblokir (5 Kali Salah Kata Sandi / Data)</span>
                </div>
                <div style="font-size: 12px; color: #7f1d1d; margin-top: 3px;">
                    Terdeteksi kegagalan login Admin 5 kali berturut-turut{{ !empty($statusBlokirLoginAdmin['last_email']) ? ' pada email: ' . $statusBlokirLoginAdmin['last_email'] : '' }}. Klik tombol di samping untuk membuka blokir agar Admin dapat login kembali.
                </div>
            </div>
            <button type="button" class="btn btn-success" onclick="bukaBlokirLoginAdmin()" style="padding: 10px 18px; font-size: 13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                </svg>
                <span>Buka Blokir Login Admin Sekarang</span>
            </button>
        </div>

        <!-- BARIS 1: KPI RINGKASAN -->
        <div class="kpi-grid">
            <div class="kpi-card" style="border-left: 4px solid var(--primary-color);">
                <div class="kpi-label">Jumlah Role Sistem</div>
                <div class="kpi-value" id="kpi-total-roles">{{ count($configRoles['roles']) }}</div>
                <div class="kpi-sub">Role operasional yang diatur Master Admin</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid var(--secondary-color);">
                <div class="kpi-label">Pengguna Role Admin</div>
                <div class="kpi-value">{{ $jumlahUserPerRole['admin'] ?? 0 }}</div>
                <div class="kpi-sub">Dari total {{ $totalSeluruhUser }} akun terdaftar</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid #8b5cf6;">
                <div class="kpi-label">Modul Menu Terkelola</div>
                <div class="kpi-value">{{ count($daftarModulSistem) }} Modul</div>
                <div class="kpi-sub">Kontrol tampil menu, tambah, edit & hapus</div>
            </div>
            <div class="kpi-card" style="border-left: 4px solid var(--warning-color);">
                <div class="kpi-label">Mode Sinkronisasi</div>
                <div class="kpi-value" style="font-size: 17px; color: #047857; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    <span>Langsung (Tanpa Refresh)</span>
                </div>
                <div class="kpi-sub">Otomatis diterapkan di layar Admin</div>
            </div>
        </div>

        <!-- BARIS 2: LAYOUT KIRI (DAFTAR & TAMBAH ROLE) + KANAN (MATRIKS PERMISSION) -->
        <div class="roles-layout">

            <!-- KOLOM KIRI: DAFTAR ROLE & FORM TAMBAH ROLE BARU -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <div class="card-box" style="border-top: 4px solid var(--primary-color);">
                    <div class="section-title">
                        <span>Daftar Role Tersimpan</span>
                        <span style="font-size: 11px; color: #64748b;" id="badge-count-role">{{ count($configRoles['roles']) }} Role</span>
                    </div>

                    <div class="role-list" id="role-list-container">
                        @foreach($configRoles['roles'] as $rKey => $rData)
                            @php
                                $jmlUserRoleIni = $jumlahUserPerRole[$rKey] ?? 0;
                                $jmlMenuAktif = collect($rData['permissions'] ?? [])->filter(fn($p) => !empty($p['view']))->count();
                            @endphp
                            <div class="role-card-item {{ $rKey === 'admin' ? 'active' : '' }}"
                                 id="role-card-{{ $rKey }}"
                                 data-role-key="{{ $rKey }}"
                                 onclick="pilihRoleUntukDiatur('{{ $rKey }}')">
                                <div class="role-card-top">
                                    <span class="role-badge-name">
                                        <span class="role-dot" style="background: {{ $rData['badge_color'] ?? '#1a4f91' }};"></span>
                                        {{ $rData['name'] }}
                                    </span>
                                    <span class="role-user-pill">{{ $jmlUserRoleIni }} Akun</span>
                                </div>
                                <div class="role-card-desc">{{ $rData['desc'] ?? '-' }}</div>
                                <div class="role-card-footer">
                                    <span id="role-summary-menu-{{ $rKey }}">
                                        @if(!empty($rData['locked']))
                                            Akses Penuh Permanen
                                        @else
                                            {{ $jmlMenuAktif }} / {{ count($daftarModulSistem) }} Menu Aktif
                                        @endif
                                    </span>
                                    @if(empty($rData['locked']))
                                        <button type="button" class="btn-delete-role" onclick="hapusRole(event, '{{ $rKey }}', '{{ addslashes($rData['name']) }}')">
                                            Hapus Role
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Form Tambah Role Baru -->
                <div class="card-box" style="border-top: 4px solid var(--secondary-color);">
                    <div class="section-title">
                        <span>+ Tambah Role Baru</span>
                    </div>
                    <form id="form-tambah-role" onsubmit="tambahRoleBaru(event)">
                        <div class="form-group">
                            <label>Nama Role Baru <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" id="input_role_name" required placeholder="Contoh: Staff Gudang / Auditor">
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Singkat</label>
                            <input type="text" id="input_role_desc" placeholder="Contoh: Hanya melihat data & laporan aset">
                        </div>
                        <div class="form-group">
                            <label>Template Hak Akses Awal</label>
                            <select id="input_role_template">
                                <option value="readonly">Hanya Lihat Semua Menu (Read-Only)</option>
                                <option value="full">Akses Penuh (Lihat, Tambah, Edit, Hapus)</option>
                                <option value="minimal">Minimal (Hanya Dashboard)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Warna Identitas Role</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" id="input_role_color" value="#0ea5e9" style="width: 48px; height: 36px; padding: 2px; cursor: pointer;">
                                <span style="font-size: 11px; color: #64748b;">Warna label pada daftar role</span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" id="btn-submit-role" style="width: 100%; margin-top: 6px; padding: 11px;">
                            + Simpan Role Baru
                        </button>
                    </form>
                </div>

            </div>

            <!-- KOLOM KANAN: PENGATURAN HAK AKSES (PERMISSIONS) PER MODUL -->
            <div class="card-box" style="border-top: 4px solid var(--primary-color);">
                <div class="section-title">
                    <div>
                        <span style="font-size: 16px;">Pengaturan Hak Akses Role: </span>
                        <strong id="judul-role-aktif" style="color: var(--primary-color); font-size: 16px;">Admin</strong>
                    </div>
                    <span id="status-simpan-indikator" style="font-size: 12px; color: #059669; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                        <span id="status-simpan-teks">Otomatis tersimpan & aktif di sisi Admin</span>
                    </span>
                </div>

                <div id="banner-locked-master" style="display: none;"></div>

                <!-- Toolbar Preset Cepat Global -->
                <div class="perm-toolbar" id="toolbar-preset-global">
                    <div>
                        <div style="font-size: 12px; font-weight: 800; color: #0f172a;">Atur Cepat Seluruh Menu untuk Role Ini:</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Klik salah satu mode di samping atau ubah saklar per baris menu di bawah.</div>
                    </div>
                    <div class="perm-Preset-buttons">
                        <button type="button" class="btn btn-success" onclick="terapkanPresetGlobal('full')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Akses Penuh Semua Menu</span>
                        </button>
                        <button type="button" class="btn btn-warning" onclick="terapkanPresetGlobal('readonly')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <span>Set Hanya Lihat (Read-Only)</span>
                        </button>
                        <button type="button" class="btn btn-danger-soft" onclick="terapkanPresetGlobal('minimal')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                            <span>Sembunyikan Menu (Sisakan Dashboard)</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Matriks Hak Akses -->
                <div class="perm-table-wrap">
                    <table class="perm-table">
                        <thead>
                            <tr>
                                <th style="width: 32%;">Modul / Menu Sidebar</th>
                                <th style="width: 18%;">Mode Cepat</th>
                                <th style="width: 12%;">Tampilkan Menu<br><span style="font-size: 9px; font-weight: 600; color: #64748b;">(Lihat / Buka)</span></th>
                                <th style="width: 12%;">Tambah Data<br><span style="font-size: 9px; font-weight: 600; color: #64748b;">(Create / Generate)</span></th>
                                <th style="width: 12%;">Ubah Data<br><span style="font-size: 9px; font-weight: 600; color: #64748b;">(Edit / Update)</span></th>
                                <th style="width: 14%;">Hapus Data<br><span style="font-size: 9px; font-weight: 600; color: #64748b;">(Delete / Clear)</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grupTerakhir = null; @endphp
                            @foreach($daftarModulSistem as $mKey => $mInfo)
                                @php
                                    $caps = $mInfo['caps'];
                                    $hasCreate = in_array('create', $caps, true);
                                    $hasEdit   = in_array('edit', $caps, true);
                                    $hasDelete = in_array('delete', $caps, true);
                                    $onlyView  = (!$hasCreate && !$hasEdit && !$hasDelete);
                                @endphp
                                @if($grupTerakhir !== $mInfo['grup'])
                                    @php $grupTerakhir = $mInfo['grup']; @endphp
                                    <tr class="perm-group-row">
                                        <td colspan="6">{{ $grupTerakhir }}</td>
                                    </tr>
                                @endif
                                <tr class="perm-row" id="perm-row-{{ $mKey }}" data-modul="{{ $mKey }}">
                                    <td>
                                        <div class="modul-title">
                                            <span>{{ $mInfo['nama'] }}</span>
                                            <span class="status-pill-akses pill-full" id="pill-status-{{ $mKey }}">Akses Penuh</span>
                                        </div>
                                        <div class="modul-desc">{{ $mInfo['deskripsi'] }}</div>
                                    </td>
                                    <td>
                                        <select class="preset-select-row" id="preset-row-{{ $mKey }}" onchange="ubahPresetBaris('{{ $mKey }}', this.value)">
                                            @if($onlyView)
                                                <option value="full">Tampilkan Menu</option>
                                                <option value="hidden">Sembunyikan</option>
                                            @else
                                                <option value="full">Akses Penuh</option>
                                                <option value="readonly">Hanya Lihat</option>
                                                <option value="hidden">Sembunyikan</option>
                                                <option value="custom">Kustom</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <div class="cap-cell-wrap">
                                            <label class="switch" title="{{ $mInfo['labels']['view'] ?? 'Tampilkan Menu' }}">
                                                <input type="checkbox" id="chk-view-{{ $mKey }}" onchange="saatToggleBerubah('{{ $mKey }}', 'view')">
                                                <span class="slider slider-view"></span>
                                            </label>
                                            <span class="cap-sub-label">{{ $mInfo['labels']['view'] ?? 'Lihat' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($hasCreate)
                                            <div class="cap-cell-wrap">
                                                <label class="switch" title="{{ $mInfo['labels']['create'] ?? 'Tambah Data' }}">
                                                    <input type="checkbox" id="chk-create-{{ $mKey }}" onchange="saatToggleBerubah('{{ $mKey }}', 'create')">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="cap-sub-label">{{ $mInfo['labels']['create'] ?? 'Tambah' }}</span>
                                            </div>
                                        @else
                                            <span class="cap-na" title="Modul ini tidak memiliki aksi tambah">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hasEdit)
                                            <div class="cap-cell-wrap">
                                                <label class="switch" title="{{ $mInfo['labels']['edit'] ?? 'Ubah Data' }}">
                                                    <input type="checkbox" id="chk-edit-{{ $mKey }}" onchange="saatToggleBerubah('{{ $mKey }}', 'edit')">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="cap-sub-label">{{ $mInfo['labels']['edit'] ?? 'Edit' }}</span>
                                            </div>
                                        @else
                                            <span class="cap-na" title="Modul ini tidak memiliki aksi ubah/edit">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($hasDelete)
                                            <div class="cap-cell-wrap">
                                                <label class="switch" title="{{ $mInfo['labels']['delete'] ?? 'Hapus Data' }}">
                                                    <input type="checkbox" id="chk-delete-{{ $mKey }}" onchange="saatToggleBerubah('{{ $mKey }}', 'delete')">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="cap-sub-label">{{ $mInfo['labels']['delete'] ?? 'Hapus' }}</span>
                                            </div>
                                        @else
                                            <span class="cap-na" title="Modul ini tidak memiliki aksi hapus">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; flex-wrap: wrap; gap: 10px; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <span style="font-size: 12px; color: #475569; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a4f91" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span><strong>Sinkronisasi Otomatis & Proteksi Anti-Spam:</strong> Setiap perubahan hak akses langsung disimpan ke layar Admin secara otomatis dengan jeda <strong>2 detik</strong> antar klik untuk mencegah spam.</span>
                    </span>
                </div>
            </div>

        </div>
    </main>

    <script>
        const DAFTAR_MODUL = @json($daftarModulSistem);
        const MODUL_KEYS = Object.keys(DAFTAR_MODUL);
        let DATA_ROLES = @json($configRoles['roles']);
        let roleAktifKey = DATA_ROLES['admin'] ? 'admin' : Object.keys(DATA_ROLES)[0];

        function showToast(message, type = 'success') {
            document.querySelectorAll('.toast-popup').forEach(el => el.remove());
            const t = document.createElement('div');
            t.className = `toast-popup toast-${type === 'error' ? 'error' : 'success'} show`;
            const iconSvg = type === 'error'
                ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`
                : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
            t.innerHTML = `${iconSvg}<span>${message}</span>`;
            document.body.appendChild(t);
            setTimeout(() => {
                t.classList.remove('show');
                setTimeout(() => t.remove(), 400);
            }, 2600);
        }

        function hitungMenuAktif(perms) {
            let count = 0;
            MODUL_KEYS.forEach(k => {
                if (perms && perms[k] && perms[k].view) count++;
            });
            return count;
        }

        function pilihRoleUntukDiatur(roleKey) {
            if (!DATA_ROLES[roleKey]) return;
            roleAktifKey = roleKey;

            document.querySelectorAll('.role-card-item').forEach(card => {
                card.classList.toggle('active', card.getAttribute('data-role-key') === roleKey);
            });

            const roleObj = DATA_ROLES[roleKey];
            const isLocked = !!roleObj.locked;

            document.getElementById('judul-role-aktif').textContent = roleObj.name;
            document.getElementById('banner-locked-master').style.display = isLocked ? 'block' : 'none';
            document.getElementById('toolbar-preset-global').style.display = isLocked ? 'none' : 'flex';

            renderMatriksRole(roleObj);
        }

        function perbaruiTampilanBaris(mKey, p, isLocked) {
            const info = DAFTAR_MODUL[mKey] || {};
            const caps = info.caps || ['view'];
            const hasCreate = caps.includes('create');
            const hasEdit   = caps.includes('edit');
            const hasDelete = caps.includes('delete');
            const onlyView  = (!hasCreate && !hasEdit && !hasDelete);

            const row = document.getElementById('perm-row-' + mKey);
            const chkView = document.getElementById('chk-view-' + mKey);
            const chkCreate = document.getElementById('chk-create-' + mKey);
            const chkEdit = document.getElementById('chk-edit-' + mKey);
            const chkDelete = document.getElementById('chk-delete-' + mKey);
            const presetSel = document.getElementById('preset-row-' + mKey);
            const pill = document.getElementById('pill-status-' + mKey);

            const v = !!p.view;
            const c = v && hasCreate && !!p.create;
            const e = v && hasEdit && !!p.edit;
            const d = v && hasDelete && !!p.delete;

            if (chkView) {
                chkView.checked = v;
                chkView.disabled = isLocked;
            }
            if (chkCreate) {
                chkCreate.checked = c;
                chkCreate.disabled = isLocked || !v;
            }
            if (chkEdit) {
                chkEdit.checked = e;
                chkEdit.disabled = isLocked || !v;
            }
            if (chkDelete) {
                chkDelete.checked = d;
                chkDelete.disabled = isLocked || !v;
            }
            if (presetSel) {
                presetSel.disabled = isLocked;
            }

            row.classList.toggle('row-disabled', !v);

            if (!v) {
                presetSel.value = 'hidden';
                pill.className = 'status-pill-akses pill-hidden';
                pill.textContent = 'Disembunyikan';
                return;
            }

            if (onlyView) {
                presetSel.value = 'full';
                pill.className = 'status-pill-akses pill-full';
                pill.textContent = 'Menu Tampil';
                return;
            }

            const allActive = (!hasCreate || c) && (!hasEdit || e) && (!hasDelete || d);
            const noneActive = (!hasCreate || !c) && (!hasEdit || !e) && (!hasDelete || !d);

            if (allActive) {
                presetSel.value = 'full';
                pill.className = 'status-pill-akses pill-full';
                pill.textContent = 'Akses Penuh';
            } else if (noneActive) {
                presetSel.value = 'readonly';
                pill.className = 'status-pill-akses pill-readonly';
                pill.textContent = 'Hanya Lihat';
            } else {
                presetSel.value = 'custom';
                pill.className = 'status-pill-akses pill-custom';
                const aktifList = ['Lihat'];
                if (c) aktifList.push(info.labels?.create || 'Tambah');
                if (e) aktifList.push(info.labels?.edit || 'Edit');
                if (d) aktifList.push(info.labels?.delete || 'Hapus');
                pill.textContent = aktifList.join(' + ');
            }
        }

        function renderMatriksRole(roleObj) {
            const isLocked = !!roleObj.locked;
            const perms = roleObj.permissions || {};

            MODUL_KEYS.forEach(mKey => {
                const p = perms[mKey] || { view: true, create: true, edit: true, delete: true };
                perbaruiTampilanBaris(mKey, p, isLocked);
            });
        }

        // COOLDOWN 2 DETIK (2000 ms) SETIAP MENGUBAH PERMISSION UNTUK MENCEGAH SPAM KLIK
        const COOLDOWN_MS = 2000;
        let isCooldownPermission = false;
        let cooldownInterval = null;

        function aktifkanCooldownPermission() {
            isCooldownPermission = true;
            document.body.classList.add('perm-in-cooldown');

            const tableWrap = document.querySelector('.perm-table-wrap');
            const toolbarGlobal = document.getElementById('toolbar-preset-global');
            const indikator = document.getElementById('status-simpan-indikator');

            if (tableWrap) tableWrap.classList.add('perm-cooldown-lock');
            if (toolbarGlobal) toolbarGlobal.classList.add('perm-cooldown-lock');

            // Kunci seluruh checkbox, dropdown preset, dan tombol preset global selama 2 detik
            document.querySelectorAll('.perm-table input[type="checkbox"], .preset-select-row, #toolbar-preset-global button').forEach(el => {
                el.disabled = true;
            });

            const svgClock = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
            const svgCheck = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;

            let sisaMs = COOLDOWN_MS;
            if (indikator) {
                indikator.style.color = '#d97706';
                indikator.innerHTML = `${svgClock} <span>Jeda anti-spam (2.0 dtk)... Menyinkronkan ke Admin</span>`;
            }

            if (cooldownInterval) clearInterval(cooldownInterval);
            cooldownInterval = setInterval(() => {
                sisaMs -= 100;
                if (sisaMs > 0) {
                    if (indikator) {
                        indikator.style.color = '#d97706';
                        indikator.innerHTML = `${svgClock} <span>Jeda anti-spam (${(sisaMs / 1000).toFixed(1)} dtk)...</span>`;
                    }
                } else {
                    clearInterval(cooldownInterval);
                    cooldownInterval = null;
                    isCooldownPermission = false;
                    document.body.classList.remove('perm-in-cooldown');

                    if (tableWrap) tableWrap.classList.remove('perm-cooldown-lock');
                    if (toolbarGlobal) toolbarGlobal.classList.remove('perm-cooldown-lock');

                    // Kembalikan state aktif/nonaktif sesuai konfigurasi role saat ini
                    const roleObj = DATA_ROLES[roleAktifKey];
                    if (roleObj) {
                        document.querySelectorAll('#toolbar-preset-global button').forEach(btn => {
                            btn.disabled = !!roleObj.locked;
                        });
                        renderMatriksRole(roleObj);
                    }

                    if (indikator) {
                        indikator.style.color = '#059669';
                        indikator.innerHTML = `${svgCheck} <span>Tersimpan & tersinkronisasi real-time ke Admin!</span>`;
                    }
                }
            }, 100);
        }

        function saatToggleBerubah(mKey, field) {
            const roleObj = DATA_ROLES[roleAktifKey];
            if (!roleObj || roleObj.locked) return;

            if (isCooldownPermission) {
                // Kembalikan tampilan ke kondisi terakhir jika sedang jeda 2 detik
                renderMatriksRole(roleObj);
                showToast('Mohon tunggu jeda 2 detik sebelum mengubah hak akses lagi.', 'error');
                return;
            }

            const caps = DAFTAR_MODUL[mKey]?.caps || ['view'];
            const chkView = document.getElementById('chk-view-' + mKey);
            const chkCreate = document.getElementById('chk-create-' + mKey);
            const chkEdit = document.getElementById('chk-edit-' + mKey);
            const chkDelete = document.getElementById('chk-delete-' + mKey);

            if (field === 'view' && !chkView.checked) {
                if (chkCreate) chkCreate.checked = false;
                if (chkEdit) chkEdit.checked = false;
                if (chkDelete) chkDelete.checked = false;
            }

            const v = chkView ? chkView.checked : false;
            const newPerm = {
                view: v,
                create: (v && caps.includes('create') && chkCreate) ? chkCreate.checked : false,
                edit:   (v && caps.includes('edit') && chkEdit) ? chkEdit.checked : false,
                delete: (v && caps.includes('delete') && chkDelete) ? chkDelete.checked : false,
            };

            roleObj.permissions[mKey] = newPerm;
            perbaruiTampilanBaris(mKey, newPerm, false);

            const subPengajuanKeys = ['pengajuan_peminjaman', 'pengajuan_pengembalian', 'pengajuan_peleburan', 'pengajuan_servis'];
            if (mKey === 'pengajuan') {
                if (!v) {
                    subPengajuanKeys.forEach(sk => {
                        roleObj.permissions[sk] = { view: false, create: false, edit: false, delete: false };
                        perbaruiTampilanBaris(sk, roleObj.permissions[sk], false);
                    });
                } else {
                    const adaSubAktif = subPengajuanKeys.some(sk => roleObj.permissions[sk]?.view);
                    if (!adaSubAktif) {
                        subPengajuanKeys.forEach(sk => {
                            roleObj.permissions[sk] = { view: true, create: false, edit: false, delete: false };
                            perbaruiTampilanBaris(sk, roleObj.permissions[sk], false);
                        });
                    }
                }
            } else if (subPengajuanKeys.includes(mKey)) {
                if (v && !roleObj.permissions['pengajuan']?.view) {
                    roleObj.permissions['pengajuan'] = { view: true, create: false, edit: false, delete: false };
                    perbaruiTampilanBaris('pengajuan', roleObj.permissions['pengajuan'], false);
                } else if (!v) {
                    const masihAdaSubAktif = subPengajuanKeys.some(sk => roleObj.permissions[sk]?.view);
                    if (!masihAdaSubAktif && roleObj.permissions['pengajuan']?.view) {
                        roleObj.permissions['pengajuan'] = { view: false, create: false, edit: false, delete: false };
                        perbaruiTampilanBaris('pengajuan', roleObj.permissions['pengajuan'], false);
                    }
                }
            }

            const namaModul = DAFTAR_MODUL[mKey]?.nama || mKey;
            const statusText = document.getElementById('pill-status-' + mKey)?.textContent || '';
            aktifkanCooldownPermission();
            simpanKeServerSekarang(`Modul ${namaModul} -> ${statusText}`);
        }

        function ubahPresetBaris(mKey, mode) {
            const roleObj = DATA_ROLES[roleAktifKey];
            if (!roleObj || roleObj.locked) return;

            if (isCooldownPermission) {
                renderMatriksRole(roleObj);
                showToast('Mohon tunggu jeda 2 detik sebelum mengubah hak akses lagi.', 'error');
                return;
            }

            const caps = DAFTAR_MODUL[mKey]?.caps || ['view'];
            let newPerm = {
                view: true,
                create: caps.includes('create'),
                edit:   caps.includes('edit'),
                delete: caps.includes('delete'),
            };

            if (mode === 'readonly') {
                newPerm = { view: true, create: false, edit: false, delete: false };
            } else if (mode === 'hidden') {
                newPerm = { view: false, create: false, edit: false, delete: false };
            } else if (mode === 'custom') {
                return;
            }

            roleObj.permissions[mKey] = newPerm;
            perbaruiTampilanBaris(mKey, newPerm, false);

            const subPengajuanKeys = ['pengajuan_peminjaman', 'pengajuan_pengembalian', 'pengajuan_peleburan', 'pengajuan_servis'];
            if (mKey === 'pengajuan') {
                subPengajuanKeys.forEach(sk => {
                    roleObj.permissions[sk] = { view: newPerm.view, create: false, edit: false, delete: false };
                    perbaruiTampilanBaris(sk, roleObj.permissions[sk], false);
                });
            } else if (subPengajuanKeys.includes(mKey)) {
                if (newPerm.view && !roleObj.permissions['pengajuan']?.view) {
                    roleObj.permissions['pengajuan'] = { view: true, create: false, edit: false, delete: false };
                    perbaruiTampilanBaris('pengajuan', roleObj.permissions['pengajuan'], false);
                } else if (!newPerm.view) {
                    const masihAdaSubAktif = subPengajuanKeys.some(sk => roleObj.permissions[sk]?.view);
                    if (!masihAdaSubAktif && roleObj.permissions['pengajuan']?.view) {
                        roleObj.permissions['pengajuan'] = { view: false, create: false, edit: false, delete: false };
                        perbaruiTampilanBaris('pengajuan', roleObj.permissions['pengajuan'], false);
                    }
                }
            }

            const namaModul = DAFTAR_MODUL[mKey]?.nama || mKey;
            const statusText = document.getElementById('pill-status-' + mKey)?.textContent || '';
            aktifkanCooldownPermission();
            simpanKeServerSekarang(`Modul ${namaModul} -> ${statusText}`);
        }

        function terapkanPresetGlobal(mode) {
            const roleObj = DATA_ROLES[roleAktifKey];
            if (!roleObj || roleObj.locked) return;

            if (isCooldownPermission) {
                showToast('Mohon tunggu jeda 2 detik sebelum mengubah hak akses lagi.', 'error');
                return;
            }

            MODUL_KEYS.forEach(mKey => {
                const caps = DAFTAR_MODUL[mKey]?.caps || ['view'];
                if (mode === 'full') {
                    roleObj.permissions[mKey] = {
                        view: true,
                        create: caps.includes('create'),
                        edit:   caps.includes('edit'),
                        delete: caps.includes('delete'),
                    };
                } else if (mode === 'readonly') {
                    roleObj.permissions[mKey] = { view: true, create: false, edit: false, delete: false };
                } else if (mode === 'minimal') {
                    const isDash = (mKey === 'dashboard');
                    roleObj.permissions[mKey] = { view: isDash, create: false, edit: false, delete: false };
                }
            });

            renderMatriksRole(roleObj);

            const labelMode = mode === 'full'
                ? 'Akses Penuh Semua Menu'
                : (mode === 'readonly' ? 'Hanya Lihat (Read-Only) Semua Menu' : 'Sembunyikan Menu (Sisakan Dashboard)');
            aktifkanCooldownPermission();
            simpanKeServerSekarang(`Set Global: ${labelMode}`);
        }

        async function simpanKeServerSekarang(ringkasan = '') {
            const roleObj = DATA_ROLES[roleAktifKey];
            if (!roleObj || roleObj.locked) return;

            const jmlAktif = hitungMenuAktif(roleObj.permissions);
            const summaryEl = document.getElementById('role-summary-menu-' + roleAktifKey);
            if (summaryEl) {
                summaryEl.textContent = `${jmlAktif} / ${MODUL_KEYS.length} Menu Aktif`;
            }

            try {
                const params = new URLSearchParams({
                    _roles_action: 'save_permissions',
                    role_key: roleAktifKey,
                    permissions_json: JSON.stringify(roleObj.permissions),
                    change_summary: ringkasan
                });

                const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });

                const data = await res.json();
                if (data && data.success) {
                    showToast(`${data.message} (Jeda 2 dtk)`, 'success');
                } else {
                    showToast(data.message || 'Gagal menyimpan hak akses.', 'error');
                }
            } catch (err) {
                showToast('Gagal menghubungi server.', 'error');
            }
        }

        async function tambahRoleBaru(e) {
            e.preventDefault();
            const nameInput = document.getElementById('input_role_name');
            const descInput = document.getElementById('input_role_desc');
            const tplInput = document.getElementById('input_role_template');
            const colorInput = document.getElementById('input_role_color');
            const btn = document.getElementById('btn-submit-role');

            const roleName = nameInput.value.trim();
            if (!roleName) return;

            btn.disabled = true;
            btn.textContent = 'Menyimpan Role...';

            try {
                const params = new URLSearchParams({
                    _roles_action: 'add_role',
                    role_name: roleName,
                    role_desc: descInput.value.trim(),
                    template_mode: tplInput.value,
                    badge_color: colorInput.value
                });

                const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });

                const data = await res.json();
                if (data && data.success && data.role) {
                    const r = data.role;
                    DATA_ROLES[r.key] = r;

                    const jmlMenu = hitungMenuAktif(r.permissions);
                    const container = document.getElementById('role-list-container');
                    const card = document.createElement('div');
                    card.className = 'role-card-item';
                    card.id = 'role-card-' + r.key;
                    card.setAttribute('data-role-key', r.key);
                    card.setAttribute('onclick', `pilihRoleUntukDiatur('${r.key}')`);
                    card.innerHTML = `
                        <div class="role-card-top">
                            <span class="role-badge-name">
                                <span class="role-dot" style="background: ${r.badge_color};"></span>
                                ${r.name}
                            </span>
                            <span class="role-user-pill">0 Akun</span>
                        </div>
                        <div class="role-card-desc">${r.desc}</div>
                        <div class="role-card-footer">
                            <span id="role-summary-menu-${r.key}">${jmlMenu} / ${MODUL_KEYS.length} Menu Aktif</span>
                            <button type="button" class="btn-delete-role" onclick="hapusRole(event, '${r.key}', '${r.name.replace(/'/g, "\'")}')">
                                Hapus Role
                            </button>
                        </div>
                    `;
                    container.appendChild(card);

                    document.getElementById('kpi-total-roles').textContent = data.total_roles;
                    document.getElementById('badge-count-role').textContent = data.total_roles + ' Role';

                    nameInput.value = '';
                    descInput.value = '';
                    pilihRoleUntukDiatur(r.key);
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Gagal menambah role.', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '+ Simpan Role Baru';
            }
        }

        async function hapusRole(e, roleKey, roleName) {
            e.stopPropagation();
            if (!confirm(`Yakin ingin menghapus role "${roleName}"?`)) return;

            try {
                const params = new URLSearchParams({
                    _roles_action: 'delete_role',
                    role_key: roleKey
                });

                const res = await fetch(`{{ url()->current() }}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    }
                });

                const data = await res.json();
                if (data && data.success) {
                    delete DATA_ROLES[roleKey];
                    const card = document.getElementById('role-card-' + roleKey);
                    if (card) card.remove();

                    document.getElementById('kpi-total-roles').textContent = data.total_roles;
                    document.getElementById('badge-count-role').textContent = data.total_roles + ' Role';

                    if (roleAktifKey === roleKey) {
                        const fallbackKey = DATA_ROLES['admin'] ? 'admin' : Object.keys(DATA_ROLES)[0];
                        if (fallbackKey) pilihRoleUntukDiatur(fallbackKey);
                    }
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message || 'Gagal menghapus role.', 'error');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi.', 'error');
            }
        }

        async function bukaBlokirLoginAdmin() {
            try {
                const res = await fetch(`{{ url()->current() }}?_roles_action=unlock_admin_login&_t=${Date.now()}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                });
                const data = await res.json();
                if (data && data.success) {
                    const banner = document.getElementById('banner-admin-terblokir');
                    if (banner) banner.style.display = 'none';
                    showToast(data.message, 'success');
                }
            } catch (e) {
                showToast('Gagal membuka blokir.', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            pilihRoleUntukDiatur(roleAktifKey);
        });
    </script>
</body>
</html>