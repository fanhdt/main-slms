<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Admin — kelola lab (butuh auth + permission)
Route::post('/', [\App\Domain\Lab\Controllers\LabController::class, 'store'])
    ->middleware('can:labs.create');

Route::put('/{uuid}', [\App\Domain\Lab\Controllers\LabController::class, 'update'])
    ->middleware('can:labs.update');

Route::delete('/{uuid}', [\App\Domain\Lab\Controllers\LabController::class, 'destroy'])
    ->middleware('can:labs.delete');

Route::put('/{uuid}/branding', [\App\Domain\Lab\Controllers\LabController::class, 'updateBranding'])
    ->middleware('can:labs.branding');