<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminKategori extends Model
{
   use HasFactory;

    protected $table = 'kategoris';
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(AdminItem::class, 'kategori_id');
    }
}
