<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Domain\Booking\Controllers\BookingController::class, 'index'])
    ->middleware(['can:bookings.view', 'lab.access']);

Route::get('/my', [\App\Domain\Booking\Controllers\BookingController::class, 'myBookings']);

// Check-in via scan QR code — taruh sebelum '/{uuid}' agar tidak ambigu
Route::post('/checkin', [\App\Domain\Booking\Controllers\BookingController::class, 'checkin'])
    ->middleware('can:bookings.checkin');

// Cancel oleh pemilik booking sendiri — beda dari updateStatus (approval staff).
// Tidak pakai middleware `can:` karena ini bukan permission staff, tapi validasi
// kepemilikan dilakukan di service layer (lihat cancelByOwner()).
Route::post('/{uuid}/cancel', [\App\Domain\Booking\Controllers\BookingController::class, 'cancel'])
     ->middleware('auth:sanctum'); 
     
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
// Payment Gateaway
Route::post('/{uuid}/pay', [\App\Domain\Payment\Controllers\PaymentController::class, 'createSnapToken']);

Route::post('/{uuid}/assets/{bookingAssetId}/return', [\App\Domain\Booking\Controllers\BookingController::class, 'verifyAssetReturn'])
    ->middleware('can:bookings.update');