<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('photo:expire')->daily();
Schedule::command('photo:cleanup-expired-files')->daily();
Schedule::command('bookings:send-reminders')->hourly();
