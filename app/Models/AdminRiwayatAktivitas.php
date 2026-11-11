<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminRiwayatAktivitas extends Model
{
    use HasFactory;

    protected $table = 'riwayat_aktivitas';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}