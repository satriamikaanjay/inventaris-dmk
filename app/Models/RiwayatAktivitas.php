<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RiwayatAktivitas extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'riwayat_aktivitas';
    protected $fillable = [
        'user_id',
        'aktivitas',
        'modul',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}