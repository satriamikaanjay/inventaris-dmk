<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 'cabangs';
    protected $fillable = ['nama_cabang'];

    public function asets()
    {
        return $this->hasMany(DetailAset::class, 'cabang_id');
    }
}