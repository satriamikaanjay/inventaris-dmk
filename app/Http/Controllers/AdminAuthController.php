<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return app(AuthController::class)->index();
    }

    public function prosesLogin(Request $request)
    {
        return app(AuthController::class)->login($request);
    }

    public function logout(Request $request)
    {
        return app(AuthController::class)->logout($request);
    }
}
