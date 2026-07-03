<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Domain\LabService\Controllers\PackageController::class, 'index']);
Route::get('/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'show']);
Route::post('/', [\App\Domain\LabService\Controllers\PackageController::class, 'store'])
    ->middleware('can:packages.create');
Route::put('/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'update'])
    ->middleware('can:packages.update');
Route::delete('/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'destroy'])
    ->middleware('can:packages.delete');