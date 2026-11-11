<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminStatusAset extends Model
{
   use HasFactory;

    protected $table = 'status_asets';
    protected $guarded = ['id'];
}
