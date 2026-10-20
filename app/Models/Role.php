<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_role',
        'deskripsi',
    ];

    // Opsional: Definisikan relasi "One to Many" (Satu role bisa dimiliki banyak user)
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
