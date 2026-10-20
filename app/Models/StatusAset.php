<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusAset extends Model
{
    use HasFactory;

    protected $table = 'status_asets';
    protected $fillable = ['nama_status', 'warna_status'];
}