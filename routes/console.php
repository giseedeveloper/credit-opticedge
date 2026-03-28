<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Apply late penalties at 12:01 AM every day
Schedule::command(\App\Console\Commands\PenaltyAutomator::class)->dailyAt('00:01');


