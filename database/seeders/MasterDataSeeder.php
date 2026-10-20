<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\StatusAset;
use App\Models\Item;
use App\Models\DetailAset;
use App\Models\RiwayatAktivitas;
use App\Models\User;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Kategori
        $kategori1 = Kategori::firstOrCreate(['kode_kategori' => 'ELK'], ['nama_kategori' => 'Elektronik']);
        $kategori2 = Kategori::firstOrCreate(['kode_kategori' => 'FUR'], ['nama_kategori' => 'Furniture']);
        $kategori3 = Kategori::firstOrCreate(['kode_kategori' => 'KND'], ['nama_kategori' => 'Kendaraan']);

        // 2. Seed Lokasi
        Lokasi::firstOrCreate(['kode_lokasi' => 'SRV01'], ['nama_lokasi' => 'Ruang Server Lt. 3']);
        Lokasi::firstOrCreate(['kode_lokasi' => 'DIR01'], ['nama_lokasi' => 'Ruang Direksi']);
        Lokasi::firstOrCreate(['kode_lokasi' => 'GDG01'], ['nama_lokasi' => 'Gudang Utama']);

        // 3. Seed Status Aset
        StatusAset::firstOrCreate(['nama_status' => 'Tersedia'], ['warna_status' => '#10b981']);
        StatusAset::firstOrCreate(['nama_status' => 'Perbaikan'], ['warna_status' => '#f59e0b']);
        StatusAset::firstOrCreate(['nama_status' => 'Rusak Parah'], ['warna_status' => '#ef4444']);
        StatusAset::firstOrCreate(['nama_status' => 'Dipinjam'], ['warna_status' => '#3b82f6']);

        // 4. Seed Master Item (Item)
        $item1 = Item::firstOrCreate(
            ['awal_serial' => 'AST-LNK-2026'],
            [
                'kategori_id' => $kategori1->id,
                'urutan' => 1,
                'nama_aset' => 'LAPTOP LENOVO THINKPAD',
                'warna_seri' => '#3b82f6'
            ]
        );

        $item2 = Item::firstOrCreate(
            ['awal_serial' => 'AST-ACD-2026'],
            [
                'kategori_id' => $kategori1->id,
                'urutan' => 2,
                'nama_aset' => 'AC DAIKIN 2PK',
                'warna_seri' => '#10b981'
            ]
        );

        $item3 = Item::firstOrCreate(
            ['awal_serial' => 'AST-KRS-2026'],
            [
                'kategori_id' => $kategori2->id,
                'urutan' => 3,
                'nama_aset' => 'KURSI KANTOR ERGONOMIS',
                'warna_seri' => '#f59e0b'
            ]
        );

        $masterAdmin = User::where('role', 'master_admin')->first();
        $adminId = $masterAdmin ? $masterAdmin->users_id : null;

        // 5. Seed Detail Aset & Riwayat Aktivitas
        $detailsData = [
            [
                'item_id' => $item1->id,
                'kategori_id' => $kategori1->id,
                'kode_aset' => 'AST-LNK-001',
                'nomor_seri' => 'SN-LNK-88921',
                'merk' => 'Lenovo',
                'unit' => 1,
                'tanggal_pengadaan' => now()->subDays(10)->toDateString(),
                'harga_beli' => 12500000,
                'vendor_pengadaan' => 'PT Jaya Komputer',
                'no_berita_acara' => 'BA-001/2026',
                'Ruangan' => 'Ruang Server Lt. 3',
                'lokasi_saat_ini' => 'Ruang Server Lt. 3',
                'penanggung_jawab' => 'Satria Mika N.',
                'status_aset' => 'Tersedia',
                'biaya_servis' => 0,
                'servis_terakhir' => now()->toDateString(),
                'servis_berikutnya' => now()->addMonths(6)->toDateString(),
                'teknisi' => '-',
                'riwayat_mutasi' => '-',
                'tanggal_mutasi' => now()->toDateString(),
                'status_peleburan' => '-',
                'tanggal_peleburan' => now()->toDateString(),
                'metode_peleburan' => '-',
                'nilai_jual' => 0,
                'aktivitas_log' => 'Menambahkan data aset baru (AST-LNK-001)'
            ],
            [
                'item_id' => $item2->id,
                'kategori_id' => $kategori1->id,
                'kode_aset' => 'AST-ACD-002',
                'nomor_seri' => 'SN-ACD-55410',
                'merk' => 'Daikin',
                'unit' => 1,
                'tanggal_pengadaan' => now()->subDays(5)->toDateString(),
                'harga_beli' => 6000000,
                'vendor_pengadaan' => 'CV Sejuk Mandiri',
                'no_berita_acara' => 'BA-002/2026',
                'Ruangan' => 'Ruang Direksi',
                'lokasi_saat_ini' => 'Ruang Direksi',
                'penanggung_jawab' => 'Rian Hidayat',
                'status_aset' => 'Tersedia',
                'biaya_servis' => 0,
                'servis_terakhir' => now()->toDateString(),
                'servis_berikutnya' => now()->addMonths(6)->toDateString(),
                'teknisi' => '-',
                'riwayat_mutasi' => '-',
                'tanggal_mutasi' => now()->toDateString(),
                'status_peleburan' => '-',
                'tanggal_peleburan' => now()->toDateString(),
                'metode_peleburan' => '-',
                'nilai_jual' => 0,
                'aktivitas_log' => 'Menambahkan data aset baru (AST-ACD-002)'
            ],
            [
                'item_id' => $item3->id,
                'kategori_id' => $kategori2->id,
                'kode_aset' => 'AST-KRS-003',
                'nomor_seri' => 'SN-KRS-11029',
                'merk' => 'Indachi',
                'unit' => 2,
                'tanggal_pengadaan' => now()->subDays(2)->toDateString(),
                'harga_beli' => 1800000,
                'vendor_pengadaan' => 'PT Mebel Nusantara',
                'no_berita_acara' => 'BA-003/2026',
                'Ruangan' => 'Gudang Utama',
                'lokasi_saat_ini' => 'Gudang Utama',
                'penanggung_jawab' => 'Dewi Lestari',
                'status_aset' => 'Tersedia',
                'biaya_servis' => 0,
                'servis_terakhir' => now()->toDateString(),
                'servis_berikutnya' => now()->addMonths(6)->toDateString(),
                'teknisi' => '-',
                'riwayat_mutasi' => '-',
                'tanggal_mutasi' => now()->toDateString(),
                'status_peleburan' => '-',
                'tanggal_peleburan' => now()->toDateString(),
                'metode_peleburan' => '-',
                'nilai_jual' => 0,
                'aktivitas_log' => 'Menambahkan data aset baru (AST-KRS-003)'
            ],
        ];

        foreach ($detailsData as $data) {
            $logText = $data['aktivitas_log'];
            unset($data['aktivitas_log']);

            $detail = DetailAset::firstOrCreate(
                ['kode_aset' => $data['kode_aset']],
                $data
            );

            // Cek apakah riwayat untuk aset ini sudah ada
            $existsLog = RiwayatAktivitas::where('aktivitas', $logText)->exists();
            if (!$existsLog) {
                RiwayatAktivitas::create([
                    'user_id' => $adminId,
                    'aktivitas' => $logText,
                    'modul' => 'Data Aset',
                    'status' => 'Berhasil',
                ]);
            }
        }
    }
}
