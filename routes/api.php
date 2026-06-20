<?php

use App\Http\Controllers\MobileAuthController;
use Illuminate\Support\Facades\Route;

// Mobile auth — returns Bearer tokens for Flutter app
Route::post('/auth/login', [MobileAuthController::class, 'login']);
Route::post('/auth/register', [MobileAuthController::class, 'register']);

Route::middleware('auth:sanctum')->post('/auth/logout', [MobileAuthController::class, 'logout']);
