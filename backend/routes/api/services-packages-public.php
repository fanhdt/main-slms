<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/catalog/services', [\App\Domain\LabService\Controllers\ServiceController::class, 'catalog']);
Route::get('/catalog/packages', [\App\Domain\LabService\Controllers\PackageController::class, 'catalog']);

// Public — browsing layanan & paket tanpa perlu login
Route::get('/services', [\App\Domain\LabService\Controllers\ServiceController::class, 'index']);
Route::get('/services/{uuid}', [\App\Domain\LabService\Controllers\ServiceController::class, 'show']);

Route::get('/packages', [\App\Domain\LabService\Controllers\PackageController::class, 'index']);
Route::get('/packages/{uuid}', [\App\Domain\LabService\Controllers\PackageController::class, 'show']);
