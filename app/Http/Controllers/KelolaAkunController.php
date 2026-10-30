<?php

namespace App\Http\Controllers;

use App\Models\RiwayatAktivitas;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kelola Akun - hanya untuk Master Admin.
 * - Menambah akun Master Admin, Admin, dan User (email + password ditentukan Master Admin).
 * - Mengubah nama / email / password semua akun.
 * - Menghapus akun Admin dan User (akun Master Admin tidak bisa dihapus).
 * - Semua aktivitas dicatat ke Riwayat Aktivitas (modul "Kelola Akun").
 */
class KelolaAkunController extends Controller
{
    private const MODUL = 'Kelola Akun';

    // nilai kolom role => label tampilan
    private const ROLES = [
        'master_admin' => 'Master Admin',
        'admin'        => 'Admin',
        'user'         => 'User',
    ];

    // role yang boleh dihapus
    private const DAPAT_DIHAPUS = ['admin', 'user'];

    public function index()
    {
        $this->wajibMasterAdmin();

        // kelompokkan akun per role (nilai role dinormalkan: "Master Admin" / "master_admin" sama saja)
        $akuns = User::orderBy('nama_lengkap')->get()
            ->groupBy(fn ($u) => $this->normalisasiRole($u->role));

        return view('masteradmin.kelola_akun', [
            'roles'  => self::ROLES,
            'akuns'  => $akuns,
            'idSaya' => Auth::id(),
        ]);
    }

    public function store(Request $request)
    {
        $this->wajibMasterAdmin();

        $data = $request->validate([
            'role'         => ['required', Rule::in(array_keys(self::ROLES))],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:150', Rule::unique((new User)->getTable(), 'email')],
            'password'     => ['required', 'string', 'min:8', 'max:100'],
        ], $this->pesan());

        // Mapping role teks ke role_id angka
        $roleIdMap = [
            'master_admin' => 1,
            'admin'        => 2,
            'user'         => 3,
        ];

        $user = new User();
        $user->nama_lengkap = trim($data['nama_lengkap']);
        $user->email        = $data['email'];
        $user->password     = Hash::make($data['password']);
        $user->role         = $data['role']; // Simpan teks perannya
        $user->role_id      = $roleIdMap[$data['role']] ?? 3; // Simpan angka ID perannya
        $user->save();

        $label = self::ROLES[$data['role']];
        $this->catat("Menambahkan akun {$label}: {$user->nama_lengkap} ({$user->email})");

        return redirect()->route('kelola_akun.index')
            ->with('success', "Akun {$label} \"{$user->nama_lengkap}\" berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $this->wajibMasterAdmin();

        $user = User::findOrFail($id);

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email'        => [
                'required', 'email', 'max:150',
                Rule::unique($user->getTable(), 'email')->ignore($user->getKey(), $user->getKeyName()),
            ],
            'password'     => ['nullable', 'string', 'min:8', 'max:100'],
        ], $this->pesan());

        $diubah = [];
        if ($user->nama_lengkap !== trim($data['nama_lengkap'])) {
            $diubah[] = 'nama';
        }
        if ($user->email !== $data['email']) {
            $diubah[] = 'email';
        }

        // PASTIKAN role_id TIDAK KOSONG SAAT UPDATE (Pencegahan Error)
        if (empty($user->role_id)) {
            $roleIdMap = ['master_admin' => 1, 'admin' => 2, 'user' => 3];
            $user->role_id = $roleIdMap[$user->role] ?? 3;
        }

        $user->nama_lengkap = trim($data['nama_lengkap']);
        $user->email        = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $diubah[] = 'password';
        }

        if (empty($diubah)) {
            return redirect()->route('kelola_akun.index')->with('success', 'Tidak ada perubahan yang disimpan.');
        }

        $user->save();

        $label = $this->labelRole($user->role);
        $this->catat("Memperbarui akun {$label}: {$user->nama_lengkap} ({$user->email}) - yang diubah: " . implode(', ', $diubah));

        return redirect()->route('kelola_akun.index')
            ->with('success', "Akun \"{$user->nama_lengkap}\" berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $this->wajibMasterAdmin();

        $user  = User::findOrFail($id);
        $label = $this->labelRole($user->role);
        $info  = "{$label}: {$user->nama_lengkap} ({$user->email})";

        if ((int) $user->getKey() === (int) Auth::id()) {
            $this->catat("Mencoba menghapus akunnya sendiri - ditolak", 'Peringatan');

            return redirect()->route('kelola_akun.index')
                ->withErrors(['Anda tidak dapat menghapus akun yang sedang Anda gunakan.']);
        }

        if (!in_array($this->normalisasiRole($user->role), self::DAPAT_DIHAPUS, true)) {
            $this->catat("Mencoba menghapus akun {$info} - ditolak (Master Admin tidak dapat dihapus)", 'Peringatan');

            return redirect()->route('kelola_akun.index')
                ->withErrors(['Akun Master Admin tidak dapat dihapus.']);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            $this->catat("Gagal menghapus akun {$info} - masih terhubung dengan data lain", 'Peringatan');

            return redirect()->route('kelola_akun.index')
                ->withErrors(['Akun tidak dapat dihapus karena masih terhubung dengan data lain.']);
        }

        $this->catat("Menghapus akun {$info}");

        return redirect()->route('kelola_akun.index')
            ->with('success', "Akun \"{$user->nama_lengkap}\" berhasil dihapus.");
    }

    // ------------------------------------------------------------------

    /** Hanya Master Admin yang boleh masuk. Percobaan lain dicatat sebagai Peringatan. */
    private function wajibMasterAdmin(): void
    {
        $pengguna = Auth::user();

        if (!$pengguna || $this->normalisasiRole($pengguna->role) !== 'master_admin') {
            $this->catat('Mencoba mengakses menu Kelola Akun tanpa hak akses', 'Peringatan');
            throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect(\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin-dashboard'))->with('error', 'Hanya Master Admin yang dapat mengelola akun.'));
        }
    }

    /** "Master Admin", "master-admin", "MASTER_ADMIN" => "master_admin" */
    private function normalisasiRole($role): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim((string) $role)));
    }

    private function labelRole($role): string
    {
        return self::ROLES[$this->normalisasiRole($role)] ?? (string) $role;
    }

    private function catat(string $aktivitas, string $status = 'Berhasil'): void
    {
        RiwayatAktivitas::create([
            'user_id'   => Auth::id(),
            'aktivitas' => Str::limit($aktivitas, 250, ''),
            'modul'     => self::MODUL,
            'status'    => $status,
        ]);
    }

    private function pesan(): array
    {
        return [
            'role.required'         => 'Role akun tidak valid.',
            'role.in'               => 'Role akun tidak valid.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan akun lain.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.max'          => 'Password maksimal 100 karakter.',
        ];
    }
}