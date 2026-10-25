<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminLaporanController extends Controller
{
    public function index()
    {
        return view('laporan');
    }
}