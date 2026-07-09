<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domain\Portfolio\Controllers\PortfolioController;

Route::get('/', [PortfolioController::class, 'index'])
    ->middleware('can:portfolios.view');
Route::post('/', [PortfolioController::class, 'store'])
    ->middleware('can:portfolios.create');
Route::put('/{uuid}', [PortfolioController::class, 'update'])
    ->middleware('can:portfolios.update');
Route::post('/{uuid}/image', [PortfolioController::class, 'updateImage'])
    ->middleware('can:portfolios.update');
Route::delete('/{uuid}', [PortfolioController::class, 'destroy'])
    ->middleware('can:portfolios.delete');