<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasis';
    protected $guarded = ['id'];
}
