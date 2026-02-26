<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled tasks ───────────────────────────────────────────
// Run via cron: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1

Schedule::command('tokens:prune')->daily();
Schedule::command('queue:work --stop-when-empty --tries=3')->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
