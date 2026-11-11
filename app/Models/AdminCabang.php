<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCabang extends Model
{
    use HasFactory;

    protected $table = 'cabangs';
    protected $guarded = ['id'];

    public function asets()
    {
        return $this->hasMany(AdminDetailAset::class, 'cabang_id');
    }
}
