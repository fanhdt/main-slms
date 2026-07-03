<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes — Public (tidak perlu token)
|--------------------------------------------------------------------------
*/

// Akan diisi di Langkah 3: Authentication
Route::post('login', [\App\Domain\Auth\Controllers\AuthController::class, 'login']);
Route::post('register', [\App\Domain\Auth\Controllers\AuthController::class, 'register']);
Route::post('forgot-password', [\App\Domain\Auth\Controllers\AuthController::class, 'forgotPassword']);
Route::post('reset-password', [\App\Domain\Auth\Controllers\AuthController::class, 'resetPassword']);
