<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Public — info lab untuk landing page (tidak perlu auth)
Route::get('/', [\App\Domain\Lab\Controllers\LabController::class, 'index']);
Route::get('/{slug}', [\App\Domain\Lab\Controllers\LabController::class, 'show']);
Route::get('/{slug}/branding', [\App\Domain\Lab\Controllers\LabController::class, 'branding']);
