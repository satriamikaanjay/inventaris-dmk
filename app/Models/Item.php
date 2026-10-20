<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $fillable = ['kategori_id', 'urutan', 'nama_aset', 'awal_serial', 'warna_seri'];

    // Relasi: 1 Master Item dimiliki oleh 1 Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Relasi: 1 Master Item memiliki banyak Detail Aset
    public function details()
    {
        return $this->hasMany(DetailAset::class, 'item_id');
    }
}