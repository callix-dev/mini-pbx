<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Import Asterisk security logs every minute
Schedule::command('asterisk:import-security-logs', ['--tail' => 500])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
