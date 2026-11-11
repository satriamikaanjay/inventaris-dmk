<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\RiwayatAktivitas;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Silakan login terlebih dahulu untuk mengakses halaman ini.',
            ]);
        }

        $user = Auth::user();
        $userRole = strtolower(str_replace(' ', '_', trim((string) ($user->role ?? ''))));
        if ($userRole === 'masteradmin') {
            $userRole = 'master_admin';
        }

        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $norm = strtolower(str_replace(' ', '_', trim($part)));
                if ($norm === 'masteradmin') {
                    $norm = 'master_admin';
                }
                if ($norm !== '') {
                    $allowedRoles[] = $norm;
                }
            }
        }

        if (empty($allowedRoles) || in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        $this->catatPelanggaranAkses($request, $user);

        if ($userRole === 'admin') {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman Master Admin.');
        }

        if ($userRole === 'master_admin') {
            return redirect()->route('masteradmin.dashboard')
                ->with('error', 'Akses ditolak! Halaman tersebut dikhususkan untuk operasional Admin.');
        }

        return abort(403, 'Akses Ditolak: Role Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    private function catatPelanggaranAkses(Request $request, $user): void
    {
        if (!class_exists(RiwayatAktivitas::class)) {
            return;
        }

        try {
            $path = '/' . ltrim($request->path(), '/');
            $namaMenu = match (true) {
                str_contains($path, 'roles') => 'Roles & Permission',
                str_contains($path, 'kelola-akun') => 'Kelola Akun',
                str_contains($path, 'admin-dashboard') => 'Dashboard Admin',
                str_contains($path, 'masteradmin') => 'Dashboard Master Admin',
                default => $path,
            };

            $riwayat = new RiwayatAktivitas();
            $tbl = $riwayat->getTable();
            $data = [];

            if (Schema::hasColumn($tbl, 'user_id')) {
                $data['user_id'] = $user->id ?? null;
            }
            if (Schema::hasColumn($tbl, 'aktivitas')) {
                $data['aktivitas'] = 'Mencoba mengakses menu ' . $namaMenu . ' tanpa hak akses';
            }
            if (Schema::hasColumn($tbl, 'modul')) {
                $data['modul'] = $namaMenu;
            }
            if (Schema::hasColumn($tbl, 'status')) {
                $data['status'] = 'Peringatan';
            }

            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
        }
    }
}