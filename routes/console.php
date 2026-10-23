<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('aura:send-cart-reminders')->hourly();
Schedule::command('aura:remind-stale-orders')->hourly()->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('04:00')->withoutOverlapping();
