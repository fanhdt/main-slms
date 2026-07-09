<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Route broadcasting/auth sudah didefinisikan manual di routes/api.php
        // (di dalam group prefix api/v1 + middleware auth:sanctum), jadi di sini
        // kita cukup load channel authorization definitions saja.
        require base_path('routes/channels.php');
    }
}