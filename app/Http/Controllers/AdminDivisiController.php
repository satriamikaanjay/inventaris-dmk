<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminDivisiController extends Controller
{
    public function index()
    {
        return view('divisi');
    }
}