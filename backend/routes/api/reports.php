<?php

declare(strict_types=1);

use App\Domain\Report\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReportController::class, 'index'])
    ->middleware(['can:reports.view', 'lab.access']);

Route::get('/{uuid}', [ReportController::class, 'show'])
    ->middleware('can:reports.view');