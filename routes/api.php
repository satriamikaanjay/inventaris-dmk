<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\AdminPengajuanController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');
Route::get('/user-access-status', [AuthController::class, 'accessStatus'])->middleware('throttle:60,1');
Route::get('/inventory', [InventoryController::class, 'index'])->middleware('throttle:120,1');
Route::get('/inventory/categories', [InventoryController::class, 'categories'])->middleware('throttle:120,1');
Route::get('/inventory/branches', [InventoryController::class, 'branches'])->middleware('throttle:120,1');
Route::get('/categories', [InventoryController::class, 'categories'])->middleware('throttle:120,1');
Route::get('/branches', [InventoryController::class, 'branches'])->middleware('throttle:120,1');

Route::get('/pengajuan', [AdminPengajuanController::class, 'apiIndex'])->middleware('throttle:120,1');
Route::post('/pengajuan', [AdminPengajuanController::class, 'apiStore'])->middleware('throttle:30,1');
Route::get('/pengajuan/user', [AdminPengajuanController::class, 'apiUser'])->middleware('throttle:120,1');
Route::get('/pengajuan/lampiran/{filename}', [AdminPengajuanController::class, 'viewLampiran'])
    ->middleware('throttle:120,1')
    ->name('api.pengajuan.lampiran');
