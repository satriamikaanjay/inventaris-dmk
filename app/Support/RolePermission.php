<?php

namespace App\Support;

use Illuminate\Http\Request;

class RolePermission
{
    public const MODULES = [
        'dashboard'             => ['view'],
        'pengajuan'             => ['view'],
        'pengajuan_peminjaman'  => ['view'],
        'pengajuan_permintaan'  => ['view'],
        'pengajuan_peleburan'   => ['view'],
        'pengajuan_perpindahan' => ['view'],
        'pengajuan_servis'      => ['view'],
        'notifikasi'            => ['view'],
        'data_aset'             => ['view', 'create', 'edit', 'delete'],
        'barcode_aset'          => ['view', 'create', 'edit'],
        'tracking_pengadaan'    => ['view'],
        'kategori_aset'         => ['view', 'create', 'edit', 'delete'],
        'cabang'                => ['view', 'create', 'edit', 'delete'],
        'divisi'                => ['view', 'create', 'edit', 'delete'],
        'jabatan'               => ['view', 'create', 'edit', 'delete'],
        'laporan'               => ['view', 'create'],
        'riwayat_aktivitas'     => ['view', 'delete'],
    ];

    public static function normalizeRole(?string $role): string
    {
        $norm = strtolower(str_replace([' ', '-'], '_', trim((string) ($role ?? ''))));
        if ($norm === 'masteradmin') {
            return 'master_admin';
        }
        return $norm !== '' ? $norm : 'admin';
    }

    public static function isMasterAdmin(?string $role): bool
    {
        return self::normalizeRole($role) === 'master_admin';
    }

    public static function storagePath(): string
    {
        return storage_path('app/roles_permissions.json');
    }

    public static function readConfig(): array
    {
        $path = self::storagePath();
        clearstatcache(true, $path);
        if (!is_file($path)) {
            return ['updated_at' => 0, 'roles' => []];
        }

        $decoded = json_decode((string) @file_get_contents($path), true);
        if (!is_array($decoded)) {
            return ['updated_at' => 0, 'roles' => []];
        }

        return [
            'updated_at' => $decoded['updated_at'] ?? 0,
            'roles'      => is_array($decoded['roles'] ?? null) ? $decoded['roles'] : [],
        ];
    }

    /**
     * Ambil matriks permission ter-normalisasi untuk role tertentu.
     *
     * @return array<string, array{view: bool, create: bool, edit: bool, delete: bool}>
     */
    public static function forRole(?string $role): array
    {
        $normRole = self::normalizeRole($role);
        $isMaster = ($normRole === 'master_admin');

        $perms = [];
        foreach (self::MODULES as $mKey => $caps) {
            $perms[$mKey] = [
                'view'   => true,
                'create' => in_array('create', $caps, true),
                'edit'   => in_array('edit', $caps, true),
                'delete' => in_array('delete', $caps, true),
            ];
        }

        if ($isMaster) {
            return $perms;
        }

        $config = self::readConfig();
        $roleData = $config['roles'][$normRole] ?? ($config['roles']['admin'] ?? null);

        if (is_array($roleData) && isset($roleData['permissions']) && is_array($roleData['permissions'])) {
            foreach (self::MODULES as $mKey => $caps) {
                if (isset($roleData['permissions'][$mKey]) && is_array($roleData['permissions'][$mKey])) {
                    $p = $roleData['permissions'][$mKey];
                    $v = !empty($p['view']);
                    $perms[$mKey] = [
                        'view'   => $v,
                        'create' => ($v && in_array('create', $caps, true)) ? !empty($p['create']) : false,
                        'edit'   => ($v && in_array('edit', $caps, true)) ? !empty($p['edit']) : false,
                        'delete' => ($v && in_array('delete', $caps, true)) ? !empty($p['delete']) : false,
                    ];
                }
            }
        }

        return $perms;
    }

    public static function version(): int|float
    {
        $config = self::readConfig();
        return $config['updated_at'] ?? 0;
    }

    public static function can(?string $role, string $modul, string $action = 'view'): bool
    {
        $normRole = self::normalizeRole($role);
        if ($normRole === 'master_admin') {
            return true;
        }

        if (!isset(self::MODULES[$modul])) {
            return true;
        }

        $perms = self::forRole($normRole);
        if (!isset($perms[$modul])) {
            return true;
        }

        if (empty($perms[$modul]['view'])) {
            return false;
        }

        if ($action === 'view') {
            return true;
        }

        return !empty($perms[$modul][$action]);
    }

    public static function denyResponse(Request $request, string $pesan = 'Anda tidak memiliki izin melakukan aksi ini. Hubungi Master Admin.')
    {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $pesan,
            ], 403);
        }

        return redirect()->back()->withErrors(['permission' => $pesan]);
    }
}
