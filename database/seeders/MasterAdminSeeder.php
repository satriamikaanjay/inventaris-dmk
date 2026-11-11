<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama_lengkap' => 'Master Admin',
            'email'        => 'masteradmin@gmail.com',
            'password'     => Hash::make('password123'), 
            'role_id'      => 1,
            'role'         => 'master_admin',
        ]);
    }
}
