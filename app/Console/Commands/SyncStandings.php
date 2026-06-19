<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\NotifySourceUnreadable;
use App\Jobs\SyncCharacterStandings;
use App\Models\Character;
use App\Models\StandingsSource;
use App\Services\StandingsSourceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('standings:sync {--force : Queue character syncs even when the source is unchanged}')]
#[Description('Refresh the standings source and, when it changed, queue a sync for every opted-in character.')]
class SyncStandings extends Command
{
    public function handle(StandingsSourceService $source): int
    {
        $changed = $source->refresh();

        if ($changed === null) {
            // A configured source that can't be read means a token is missing or
            // lacks permissions. Alert Discord, but at most once per clock hour.
            if (StandingsSource::current() instanceof StandingsSource
                && Cache::add('standings:source-unreadable:'.now()->format('YmdH'), true, now()->addHour())) {
                NotifySourceUnreadable::dispatch();
            }

            $this->components->warn('No standings source is configured or the source could not be read.');

            return self::FAILURE;
        }

        if (! $changed && ! $this->option('force')) {
            $this->components->info('Source standings are unchanged; no character syncs queued.');

            return self::SUCCESS;
        }

        $queued = 0;
        $standingsSource = StandingsSource::current();

        Character::query()->syncable()->each(function (Character $character) use (&$queued, $standingsSource): void {
            // Characters in the source corp/alliance already inherit the standings.
            if ($standingsSource?->coversCharacter($character)) {
                return;
            }

            SyncCharacterStandings::dispatch($character);
            $queued++;
        });

        $this->components->info(sprintf('Queued %d character sync(s).', $queued));

        return self::SUCCESS;
    }
}
