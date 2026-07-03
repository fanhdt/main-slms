<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SLMS
|--------------------------------------------------------------------------
|
| Semua route API dikelompokkan dalam prefix /api/v1.
| Versi API dibuat eksplisit dari awal agar mudah ditambahkan v2 nanti
| tanpa breaking change.
|
*/

Route::prefix('v1')->group(function () {

    // ---- Public routes (tidak perlu auth) ----
    Route::prefix('auth')->group(base_path('routes/api/auth.php'));

    // ---- Lab public info (untuk landing page per lab) ----
    Route::prefix('labs')->group(base_path('routes/api/labs-public.php'));

    // ---- Protected routes (butuh Sanctum token) ----
    Route::middleware(['auth:sanctum'])->group(function () {

        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Auth
        Route::prefix('auth')->group(base_path('routes/api/auth-protected.php'));

        // Users
        Route::prefix('users')->group(base_path('routes/api/users.php'));
        
        Route::get('/{uuid}/users', [\App\Domain\User\Controllers\UserLabController::class, 'getLabUsers']);

        // Labs (admin)
        Route::prefix('labs')->group(base_path('routes/api/labs.php'));

        // Assets
        Route::prefix('assets')->group(base_path('routes/api/assets.php'));

        // Services & Packages
        Route::prefix('services')->group(base_path('routes/api/services.php'));
        Route::prefix('packages')->group(base_path('routes/api/packages.php'));

        // Bookings
        Route::prefix('bookings')->group(base_path('routes/api/bookings.php'));

        // Photo Delivery
        Route::prefix('photo-projects')->group(base_path('routes/api/photo-projects.php'));

        //Notifications
        Route::prefix('notifications')->group(base_path('routes/api/notifications.php'));

        

    });

    

});
