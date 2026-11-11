<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAset extends Model
{
    use HasFactory;

    protected $table = 'detail_aset';
    
    protected $primaryKey = 'aset_id'; 

    protected $fillable = [
        'item_id', 
        'kode_aset',
        'kategori_id',
        'cabang_id',
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

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}