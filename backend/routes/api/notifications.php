<?php

use App\Domain\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NotificationControlle::class, 'index']);
Route::post('/{uuid}/read', [NotificationController::class, 'markAsRead']);
Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);