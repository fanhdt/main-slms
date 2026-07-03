<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Models\User;
use App\Domain\User\Policies\UserPolicy;
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
        // Daftarkan Policy
        Gate::policy(User::class, UserPolicy::class);

        // Super Admin bypass semua permission check
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}