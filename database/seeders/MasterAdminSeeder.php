<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nama_lengkap' => 'Master Admin',
            'email'        => 'masteradmin@gmail.com', // Email untuk login nanti
            'password'     => Hash::make('password123'), 
            'role_id'      => 1, // Pastikan role_id sesuai dengan role 'Master Admin' di tabel roles
            'role'         => 'master_admin', // Pastikan rolenya sesuai rancangan sistem Anda
        ]);
    }
}
