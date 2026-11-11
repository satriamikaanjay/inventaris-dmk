<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
            ['id' => 1],
            ['nama_role' => 'Master Admin']        );

        User::create([
            'nama_lengkap' => 'Administrator Utama', 
            'email'        => 'master@admin.com',
            'password'     => Hash::make('password123'),
            'role_id'      => 1, 
            'role'         => 'master_admin', 
        ]);
    }
}