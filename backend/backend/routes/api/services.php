<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// ---- PROTECTED (create/update/delete) ----
Route::post('/', [\App\Domain\LabService\Controllers\ServiceController::class, 'store'])
    ->middleware('can:services.create');
Route::put('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'update'])
    ->middleware('can:services.update');
Route::post('/{uuid}/image', [\App\Domain\LabService\Controllers\ServiceController::class, 'updateImage'])
    ->middleware('can:services.update');
Route::delete('/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'destroy'])
    ->middleware('can:services.delete');

// ---- Service Options ----
Route::get('/{uuid}/options', [\App\Domain\LabService\Controllers\ServiceController::class, 'listOptions'])
    ->middleware('can:services.view');
Route::post('/{uuid}/options', [\App\Domain\LabService\Controllers\ServiceController::class, 'storeOption'])
    ->middleware('can:services.update');
Route::put('/{uuid}/options/{optionUuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'updateOption'])
    ->middleware('can:services.update');
Route::delete('/{uuid}/options/{optionUuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'destroyOption'])
    ->middleware('can:services.update');