<?php

declare(strict_types=1);

use App\Domain\Photo\Controllers\PhotoDeliveryController;
use App\Domain\Photo\Controllers\PhotoProjectController;
use Illuminate\Support\Facades\Route;

// ---- Admin/internal: kelola project ----
Route::get('/', [PhotoProjectController::class, 'index'])
    ->middleware(['can:media.view', 'lab.access']);

Route::post('/', [PhotoProjectController::class, 'store'])
    ->middleware('can:media.upload');

Route::get('/{uuid}', [PhotoProjectController::class, 'show']);
// ^ tanpa permission khusus: customer juga perlu akses show untuk lihat preview/hasil edit miliknya.

Route::patch('/{uuid}/max-selection', [PhotoProjectController::class, 'updateMaxSelection'])
    ->middleware('can:media.upload');

// ---- Workflow ----
Route::post('/{uuid}/previews', [PhotoDeliveryController::class, 'uploadPreviews'])
    ->middleware('can:media.upload');

Route::post('/{uuid}/edited', [PhotoDeliveryController::class, 'uploadEdited'])
    ->middleware('can:media.upload');

Route::post('/{uuid}/selection', [PhotoDeliveryController::class, 'submitSelection']);

Route::post('/{uuid}/submit-approval', [PhotoDeliveryController::class, 'submitForApproval'])
    ->middleware('can:media.upload');

Route::post('/{uuid}/approval', [PhotoDeliveryController::class, 'resolveApproval']);

Route::get('/{uuid}/files/{fileUuid}/download', [PhotoDeliveryController::class, 'downloadFile']);

Route::get('/{uuid}/download-all', [PhotoDeliveryController::class, 'downloadAll']);

Route::delete('/{uuid}/files/{fileUuid}', [PhotoDeliveryController::class, 'deleteFile'])
    ->middleware('can:media.upload');