<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Send booking reminders daily at 9am
Schedule::command('bookings:send-reminders')->dailyAt('09:00');

// Schedule provider payouts weekly on Fridays at 2am
Schedule::command('payouts:process --all')
    ->weeklyOn(5, '02:00')
    ->withoutOverlapping()
    ->onOneServer();
