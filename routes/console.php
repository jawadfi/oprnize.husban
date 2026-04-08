<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run on the 1st of every month at 08:00 (server time / UTC)
Schedule::command('loans:process-monthly')->monthlyOn(1, '08:00');
