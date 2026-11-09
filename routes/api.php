<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/inventory', [InventoryController::class, 'index']);
Route::get('/categories', [InventoryController::class, 'categories']);
Route::get('/branches', [InventoryController::class, 'branches']);