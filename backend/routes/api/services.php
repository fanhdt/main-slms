<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Index dibuka untuk semua user yang login (termasuk customer untuk browsing sebelum booking).
// lab_id tetap wajib dikirim dari frontend untuk filter, tapi tidak perlu proteksi lab.access
// karena ini read-only dan memang harus bisa diakses siapa saja yang mau booking.
Route::get('/', [\App\Domain\LabService\Controllers\ServiceController::class, 'index']);

Route::get('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'show']);

Route::post('/', [\App\Domain\LabService\Controllers\ServiceController::class, 'store'])
    ->middleware('can:services.create');
Route::put('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'update'])
    ->middleware('can:services.update');
Route::post('/{uuid}/image', [\App\Domain\LabService\Controllers\ServiceController::class, 'updateImage'])
    ->middleware('can:services.update');
Route::delete('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'destroy'])
    ->middleware('can:services.delete');