<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Domain\LabService\Controllers\ServiceController::class, 'index']);
Route::get('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'show']);
Route::post('/', [\App\Domain\LabService\Controllers\ServiceController::class, 'store'])
    ->middleware('can:services.create');
Route::put('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'update'])
    ->middleware('can:services.update');
Route::delete('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'destroy'])
    ->middleware('can:services.delete');