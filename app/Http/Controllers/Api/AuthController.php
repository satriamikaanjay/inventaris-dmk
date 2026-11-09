<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Temanmu menulis logika cek email & password di sini
        // Lalu wajib merespons dengan JSON, BUKAN view() atau redirect()
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user_name' => 'Budi'
            ]
        ]);
    }
}