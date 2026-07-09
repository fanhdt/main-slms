<?php

declare(strict_types=1);


use Illuminate\Support\Facades\Route;

// Public — info lab untuk landing page (tidak perlu auth)
Route::get('/', [\App\Domain\Lab\Controllers\LabController::class, 'index']);
Route::get('/{slug}', [\App\Domain\Lab\Controllers\LabController::class, 'show']);
Route::get('/{slug}/branding', [\App\Domain\Lab\Controllers\LabController::class, 'branding']);
Route::get('/{slug}/availability', [\App\Domain\Booking\Controllers\AvailabilityController::class, 'month']);
Route::get('/{slug}/availability/{date}', [\App\Domain\Booking\Controllers\AvailabilityController::class, 'day']);
Route::get('/{slug}/availability', [\App\Domain\Booking\Controllers\AvailabilityController::class, 'month']);
Route::get('/{slug}/availability/{date}', [\App\Domain\Booking\Controllers\AvailabilityController::class, 'day']);
Route::post('/{slug}/rfid-login', [\App\Domain\User\Controllers\RfidController::class, 'loginAndCheckin']);
Route::get('/{lab_id}/photographers', [\App\Domain\Portfolio\Controllers\PhotographerController::class, 'publicIndex']);
Route::get('/{lab_id}/portfolios/{photographerUuid}', [\App\Domain\Portfolio\Controllers\PortfolioController::class, 'galleryByPhotographer']);
