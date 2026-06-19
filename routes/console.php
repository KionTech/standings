<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The ESI contacts cache expires after roughly five minutes; refresh on that
// cadence and only fan out character syncs when the source actually changed.
Schedule::command('standings:sync')->everyFiveMinutes()->withoutOverlapping();

// Daily, mail synced characters whose token expired so they re-authenticate.
Schedule::command('standings:check-tokens')->daily()->withoutOverlapping();
