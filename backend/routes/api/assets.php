<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Domain\Asset\Controllers\AssetController::class, 'index']);
Route::get('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'show']);
Route::post('/', [\App\Domain\Asset\Controllers\AssetController::class, 'store'])
    ->middleware('can:assets.create');
Route::put('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'update'])
    ->middleware('can:assets.update');
Route::delete('/{uuid}', [\App\Domain\Asset\Controllers\AssetController::class, 'destroy'])
    ->middleware('can:assets.delete');
Route::patch('/{uuid}/status', [\App\Domain\Asset\Controllers\AssetController::class, 'updateStatus'])
    ->middleware('can:assets.update');