<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;

// ---- PROTECTED ----
Route::post('/', [\App\Domain\LabService\Controllers\PackageController::class, 'store'])
    ->middleware('can:packages.create');
Route::match(['put', 'patch'], '/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'update'])
    ->middleware('can:packages.update');
Route::post('/{uuid}/image', [\App\Domain\LabService\Controllers\PackageController::class, 'updateImage'])
    ->middleware('can:packages.update');
Route::delete('/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'destroy'])
    ->middleware('can:packages.delete');