<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingsSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStandingsSourceRequest;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StandingsSourceController extends Controller
{
    public function update(UpdateStandingsSourceRequest $request): RedirectResponse
    {
        $type = StandingsSourceType::from($request->validated('type'));
        $character = $request->user()->getActiveCharacter();

        $entity_id = match ($type) {
            StandingsSourceType::Character => $character->id,
            StandingsSourceType::Corporation => $character->corporation_id,
            StandingsSourceType::Alliance => $character->alliance_id,
        };

        if ($entity_id === null) {
            return back()->withErrors([
                'type' => sprintf('Your character has no %s to use as a source.', $type->value),
            ]);
        }

        $source = StandingsSource::current();
        $changed = ! $source instanceof StandingsSource || $source->type !== $type || $source->entity_id !== $entity_id;

        DB::transaction(function () use ($type, $entity_id, $changed): void {
            StandingsSource::query()->updateOrCreate([], [
                'type' => $type,
                'entity_id' => $entity_id,
                'last_synced_at' => null,
            ]);

            // The canonical set is tied to the source; clear it when the source changes.
            if ($changed) {
                SourceContact::query()->delete();
            }
        });

        return back()->with('success', 'Standings source updated.');
    }

    public function sync(): RedirectResponse
    {
        Gate::authorize('standings.admin');

        Artisan::call('standings:sync', ['--force' => true]);

        return back()->with('success', 'Standings sync started.');
    }
}
