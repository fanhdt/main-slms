<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes — Protected
|--------------------------------------------------------------------------
*/

// Rfid — taruh SEBELUM route /{uuid} generic, supaya "rfid" tidak ketangkep sebagai uuid
Route::get('/rfid/{uid}/bookings', [\App\Domain\User\Controllers\RfidController::class, 'lookupBookings']);
Route::patch('/{uuid}/rfid', [\App\Domain\User\Controllers\RfidController::class, 'assign']);

// Customer per-lab — dipakai lab_admin dari dashboard lab (bukan admin global)
Route::get('/lab/{lab_id}/customers', [\App\Domain\User\Controllers\UserController::class, 'labCustomers'])
    ->middleware('lab.access');

Route::get('/', [\App\Domain\User\Controllers\UserController::class, 'index']);
Route::post('/', [\App\Domain\User\Controllers\UserController::class, 'store']);
Route::get('/{uuid}', [\App\Domain\User\Controllers\UserController::class, 'show']);
Route::put('/{uuid}', [\App\Domain\User\Controllers\UserController::class, 'update']);
Route::delete('/{uuid}', [\App\Domain\User\Controllers\UserController::class, 'destroy']);
Route::post('/{uuid}/assign-role', [\App\Domain\User\Controllers\UserController::class, 'assignRole']);

// User Lab Management
Route::get('/{uuid}/labs', [\App\Domain\User\Controllers\UserLabController::class, 'getUserLabs']);
Route::post('/{uuid}/labs', [\App\Domain\User\Controllers\UserLabController::class, 'assign']);
Route::delete('/{uuid}/labs', [\App\Domain\User\Controllers\UserLabController::class, 'revoke']);