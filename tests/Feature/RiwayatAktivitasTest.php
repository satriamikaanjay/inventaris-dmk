<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\RiwayatAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiwayatAktivitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_riwayat_page_can_be_accessed_by_authenticated_user(): void
    {
        $role = Role::create([
            'nama_role' => 'Master Admin',
            'deskripsi' => 'Full access',
        ]);

        $user = User::factory()->create([
            'role' => 'master_admin',
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('/riwayat-aktivitas');

        $response->assertStatus(200);
        $response->assertViewIs('masteradmin.riwayat');
    }

    public function test_riwayat_logs_activity_correctly(): void
    {
        $role = Role::create([
            'nama_role' => 'Master Admin',
            'deskripsi' => 'Full access',
        ]);

        $user = User::factory()->create([
            'role' => 'master_admin',
            'role_id' => $role->id,
        ]);

        RiwayatAktivitas::create([
            'user_id' => $user->users_id,
            'aktivitas' => 'Menambahkan data aset baru (#AST-TEST)',
            'modul' => 'Data Aset',
            'status' => 'Berhasil',
        ]);

        $response = $this->actingAs($user)->get('/riwayat-aktivitas');

        $response->assertStatus(200);
        $response->assertSee('Menambahkan data aset baru (#AST-TEST)');
        $response->assertSee('Data Aset');
    }
}
