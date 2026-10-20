<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAset extends Model
{
    use HasFactory;

    protected $table = 'detail_aset';
    
    // Beri tahu Laravel bahwa primary key-nya adalah aset_id, bukan id
    protected $primaryKey = 'aset_id'; 

    // Masukkan semua field panjang Anda di sini
    protected $fillable = [
        'item_id', 
        'kode_aset',
        'kategori_id',
        'cabang_id', // INI YANG SEBELUMNYA TERTINGGAL
        'merk',
        'nomor_seri',
        'unit',
        'tanggal_pengadaan',
        'harga_beli',
        'Ruangan',
        'vendor_pengadaan',
        'lokasi_saat_ini',
        'penanggung_jawab',
        'status_aset',
        'status_berjalan',
        'status_maintenance',
        'servis_terakhir',
        'biaya_servis',
        'teknisi',
        'servis_berikutnya',
        'riwayat_mutasi',
        'tanggal_mutasi',
        'status_peleburan',
        'tanggal_peleburan',
        'metode_peleburan',
        'nilai_jual',
        'no_berita_acara',
        'lampiran',
    ];

    // Relasi kembali ke Master Item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Relasi ke Cabang
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}