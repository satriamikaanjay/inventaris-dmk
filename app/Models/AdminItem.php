<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminItem extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $guarded = ['id'];

    public function kategori()
    {
        return $this->belongsTo(AdminKategori::class, 'kategori_id');
    }
}
