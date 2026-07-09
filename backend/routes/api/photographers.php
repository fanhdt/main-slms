<?php
use Illuminate\Support\Facades\Route;
use App\Domain\Portfolio\Controllers\PhotographerController;

Route::get('/', [PhotographerController::class, 'index'])->middleware('can:portfolios.view');
Route::post('/', [PhotographerController::class, 'store'])->middleware('can:portfolios.create');
Route::put('/{uuid}', [PhotographerController::class, 'update'])->middleware('can:portfolios.update');
Route::post('/{uuid}/photo', [PhotographerController::class, 'updatePhoto'])->middleware('can:portfolios.update');
Route::delete('/{uuid}', [PhotographerController::class, 'destroy'])->middleware('can:portfolios.delete');