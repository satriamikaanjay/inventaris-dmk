<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminJabatanController extends Controller
{
    public function index()
    {
        return view('jabatan');
    }
}