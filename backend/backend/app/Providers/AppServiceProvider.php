<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\Booking\Listeners\CreatePhotoProjectOnBookingCompleted;
use App\Domain\User\Models\User;
use App\Domain\User\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });

        // Auto-buka Photo Project begitu booking completed & mengandung jasa fotografi
        Event::listen(BookingStatusChanged::class, CreatePhotoProjectOnBookingCompleted::class);
    }
}