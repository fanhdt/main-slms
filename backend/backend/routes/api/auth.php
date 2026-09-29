<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::post('login', [\App\Domain\Auth\Controllers\AuthController::class, 'login']);
Route::post('register', [\App\Domain\Auth\Controllers\AuthController::class, 'register']);
Route::post('forgot-password', [\App\Domain\Auth\Controllers\AuthController::class, 'forgotPassword']);
Route::post('reset-password', [\App\Domain\Auth\Controllers\AuthController::class, 'resetPassword']);

// NEW — klik dari email, redirect ke frontend setelah verifikasi
Route::get('/email/verify/{id}/{hash}', [\App\Domain\Auth\Controllers\AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('email/resend', [\App\Domain\Auth\Controllers\AuthController::class, 'resendVerification']);
    
