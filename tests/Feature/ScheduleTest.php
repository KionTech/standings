<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('schedules the standings sync every five minutes', function () {
    $schedule = app(Schedule::class);

    $event = collect($schedule->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'standings:sync'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *');
});

it('schedules the affiliation sync hourly', function () {
    $schedule = app(Schedule::class);

    $event = collect($schedule->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'characters:sync-affiliations'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('37 * * * *');
});

it('schedules the token check daily', function () {
    $schedule = app(Schedule::class);

    $event = collect($schedule->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'standings:check-tokens'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});
