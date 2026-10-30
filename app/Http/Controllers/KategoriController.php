<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\RiwayatAktivitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class KategoriController extends Controller
{
    private function catatRiwayat(string $aktivitas, string $modul = 'Kategori Aset', string $status = 'Berhasil'): void
    {
        if (!class_exists(RiwayatAktivitas::class)) {
            return;
        }

        try {
            $userId = Auth::id();
            $sudahAda = RiwayatAktivitas::where('user_id', $userId)
                ->where('aktivitas', $aktivitas)
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();

            if ($sudahAda) {
                return;
            }

            $riwayat = new RiwayatAktivitas();
            $table = $riwayat->getTable();
            $data = [];

            if (Schema::hasColumn($table, 'user_id')) {
                $data['user_id'] = $userId;
            }
            if (Schema::hasColumn($table, 'aktivitas')) {
                $data['aktivitas'] = $aktivitas;
            }
            if (Schema::hasColumn($table, 'modul')) {
                $data['modul'] = $modul;
            }
            if (Schema::hasColumn($table, 'status')) {
                $data['status'] = $status;
            }

            $riwayat->forceFill($data)->save();
        } catch (\Throwable $e) {
            // Abaikan jika gagal mencatat log agar tidak mengganggu proses utama
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'kode_kategori' => 'required|string|max:50',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => $request->nama_kategori,
            'kode_kategori' => $request->kode_kategori,
        ]);

        $this->catatRiwayat(
            'Menambah kategori aset baru: ' . $kategori->nama_kategori . ' (' . $kategori->kode_kategori . ')',
            'Kategori Aset',
            'Berhasil'
        );

        // JIKA REQUEST DARI AJAX (JAVASCRIPT)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori baru berhasil ditambahkan!'
            ]);
        }

        // JIKA BUKAN AJAX (CARA LAMA)
        return redirect()->back()->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        $namaLama = $kategori->nama_kategori;
        $kodeLama = $kategori->kode_kategori;

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'kode_kategori' => $request->kode_kategori,
        ]);

        $this->catatRiwayat(
            'Memperbarui kategori aset: ' . $namaLama . ' (' . $kodeLama . ') menjadi ' . $kategori->nama_kategori . ' (' . $kategori->kode_kategori . ')',
            'Kategori Aset',
            'Informasi'
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
    }

    // Fungsi untuk memproses Hapus Kategori (Dukungan AJAX)
    public function destroy(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);
        $namaKat = $kategori->nama_kategori;
        $kodeKat = $kategori->kode_kategori;
        $kategori->delete();

        $this->catatRiwayat(
            'Menghapus kategori aset: ' . $namaKat . ' (' . $kodeKat . ')',
            'Kategori Aset',
            'Peringatan'
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus!'
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }
}