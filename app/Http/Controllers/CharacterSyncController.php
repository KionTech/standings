<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncCharacterStandings;
use App\Models\Character;
use App\Models\StandingsSource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CharacterSyncController extends Controller
{
    public function update(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $request->validate([
            'should_sync' => ['required', 'boolean'],
        ]);

        $character->update(['should_sync' => $validated['should_sync']]);

        return back()->with('success', $validated['should_sync']
            ? sprintf('%s will now sync standings.', $character->name)
            : sprintf('%s will no longer sync standings.', $character->name));
    }

    /**
     * Manually re-apply the current standings to the user's opted-in characters.
     */
    public function sync(#[CurrentUser] User $user): RedirectResponse
    {
        $source = StandingsSource::current();
        $queued = 0;

        $user->characters()->syncable()->each(function (Character $character) use (&$queued, $source): void {
            if ($source?->coversCharacter($character)) {
                return;
            }

            SyncCharacterStandings::dispatch($character);
            $queued++;
        });

        return back()->with(
            $queued > 0 ? 'success' : 'info',
            $queued > 0
                ? sprintf('Re-syncing %d character(s).', $queued)
                : 'No characters are set to sync.',
        );
    }
}
