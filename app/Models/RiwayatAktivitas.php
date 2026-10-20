<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Tambahkan ini

class RiwayatAktivitas extends Model
{
    use HasFactory, SoftDeletes; // Tambahkan SoftDeletes di sini

    protected $table = 'riwayat_aktivitas'; // Sesuaikan dengan nama tabel Anda

    protected $fillable = [
        'user_id',
        'aktivitas',
        'modul',
        'status',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}