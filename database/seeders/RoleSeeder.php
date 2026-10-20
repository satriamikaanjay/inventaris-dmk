<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['nama_role' => 'Master Admin', 'deskripsi' => 'Memiliki akses penuh ke seluruh sistem'],
            ['nama_role' => 'Admin', 'deskripsi' => 'Mengelola operasional harian inventaris'],
            ['nama_role' => 'Cabang', 'deskripsi' => 'Pengelola inventaris tingkat cabang'],
            ['nama_role' => 'Divisi', 'deskripsi' => 'Pengelola inventaris tingkat divisi'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
