<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Domain\Booking\Controllers\BookingController::class, 'index'])
    ->middleware('can:bookings.view');

Route::get('/my', [\App\Domain\Booking\Controllers\BookingController::class, 'myBookings']);

// Check-in via scan QR code — taruh sebelum '/{uuid}' agar tidak ambigu
Route::post('/checkin', [\App\Domain\Booking\Controllers\BookingController::class, 'checkin'])
    ->middleware('can:bookings.checkin');

Route::get('/{uuid}', [\App\Domain\Booking\Controllers\BookingController::class, 'show'])
    ->middleware('can:bookings.view');

// Semua user (termasuk mahasiswa/customer) boleh membuat booking
Route::post('/', [\App\Domain\Booking\Controllers\BookingController::class, 'store']);

// Hanya yang punya izin update yang boleh mengubah status persetujuan
Route::patch('/{uuid}/status', [\App\Domain\Booking\Controllers\BookingController::class, 'updateStatus'])
    ->middleware('can:bookings.update');
// Update status pembayaran (Hanya Admin)
Route::patch('/{uuid}/payment-status', [\App\Domain\Booking\Controllers\BookingController::class, 'updatePaymentStatus'])
    ->middleware('can:bookings.update');
// Tambah aset fisik ke dalam booking (Saat Check-in)
Route::post('/{uuid}/assets', [\App\Domain\Booking\Controllers\BookingController::class, 'addAsset'])
    ->middleware('can:bookings.update');