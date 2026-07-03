<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes — Protected (butuh Sanctum token)
|--------------------------------------------------------------------------
*/

Route::post('logout', [\App\Domain\Auth\Controllers\AuthController::class, 'logout']);
Route::get('me', [\App\Domain\Auth\Controllers\AuthController::class, 'me']);
Route::put('me', [\App\Domain\Auth\Controllers\AuthController::class, 'updateProfile']);
Route::put('me/password', [\App\Domain\Auth\Controllers\AuthController::class, 'changePassword']);
