<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminDetailAset extends Model
{
    use HasFactory;

    protected $table = 'detail_aset';
    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(AdminItem::class, 'item_id');
    }

    public function kategori()
    {
        return $this->belongsTo(AdminKategori::class, 'kategori_id');
    }

    public function cabang()
    {
        return $this->belongsTo(AdminCabang::class, 'cabang_id');
    }
}
