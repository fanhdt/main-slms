<?php

use App\Domain\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NotificationController::class, 'index']);
Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
Route::post('/{uuid}/read', [NotificationController::class, 'markAsRead']);
Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);