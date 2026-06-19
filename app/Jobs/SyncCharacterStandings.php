<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Character;
use App\Services\CharacterStandingsSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCharacterStandings implements ShouldQueue
{
    use Queueable;

    public function __construct(public Character $character) {}

    public function handle(CharacterStandingsSyncService $service): void
    {
        $service->sync($this->character);
    }
}
