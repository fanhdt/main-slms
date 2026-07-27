<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;

// Index & show dibuka untuk customer browsing katalog alat sebelum sewa.
Route::get('/', [\App\Domain\Asset\Controllers\AssetController::class, 'index']);
Route::get('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'show']);
Route::post('/', [\App\Domain\Asset\Controllers\AssetController::class, 'store'])
    ->middleware('can:assets.create');
Route::put('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'update'])
    ->middleware('can:assets.update');
Route::post('/{uuid}/image', [\App\Domain\Asset\Controllers\AssetController::class, 'updateImage'])
    ->middleware('can:assets.update');
Route::delete('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'destroy'])
    ->middleware('can:assets.delete');
Route::patch('/{uuid}/status', [\App\Domain\Asset\Controllers\AssetController::class, 'updateStatus'])
    ->middleware('can:assets.update');
Route::get('/availability', [\App\Domain\Asset\Controllers\AssetController::class, 'checkAvailability']);