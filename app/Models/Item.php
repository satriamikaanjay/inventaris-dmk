<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $fillable = ['kategori_id', 'urutan', 'nama_aset', 'awal_serial', 'warna_seri'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function details()
    {
        return $this->hasMany(DetailAset::class, 'item_id');
    }
}