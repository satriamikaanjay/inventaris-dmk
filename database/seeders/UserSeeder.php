<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role; // Pastikan model Role dipanggil (jika ada), atau pakai DB facade

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT DATA ROLE TERLEBIH DAHULU (Agar foreign key terpenuhi)
        // Catatan: Ubah 'nama_role' menjadi 'name' jika di tabel roles Anda menggunakan kolom 'name'
        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
            ['id' => 1],
            ['nama_role' => 'Master Admin'] // Ganti 'nama_role' sesuai dengan kolom di tabel roles Anda
        );

        // 2. BARU BUAT DATA USER-NYA
        User::create([
            'nama_lengkap' => 'Administrator Utama', 
            'email'        => 'master@admin.com',
            'password'     => Hash::make('password123'),
            'role_id'      => 1, 
            'role'         => 'master_admin', 
        ]);
    }
}