<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user_name' => 'Budi'
            ]
        ]);
    }
}