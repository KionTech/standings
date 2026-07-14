<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StandingRequestStatus;
use App\Enums\StandingsSourceType;
use App\Http\Resources\CharacterSyncResource;
use App\Http\Resources\StandingResource;
use App\Models\Character;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(#[CurrentUser] User $user): Response
    {
        $source = StandingsSource::current();

        $characters = $user->characters()
            ->with(['corporation:id,name,ticker', 'alliance:id,name,ticker'])
            ->withCount('syncedContacts')
            ->get();

        $characters->each(fn (Character $character) => $character->setAttribute(
            'inherits_source',
            $source?->coversCharacter($character) ?? false,
        ));

        $requestStatuses = StandingRequest::query()->get(['subject_type', 'subject_id', 'status'])
            ->keyBy(fn (StandingRequest $request): string => $request->subject_type->value.':'.$request->subject_id)
            ->map(fn (StandingRequest $request): string => $request->status->value);

        $canViewStandings = $user->canViewStandings();

        return Inertia::render('Dashboard', [
            'showSetupWizard' => $user->needsSetup(),
            'source' => $source ? [
                'type' => $source->type->value,
                'entity_id' => $source->entity_id,
                'entity_name' => $source->entityName(),
                'last_synced_at' => $source->last_synced_at?->toIso8601String(),
            ] : null,
            'canViewStandings' => $canViewStandings,
            'standings' => $canViewStandings ? StandingResource::collection(
                SourceContact::query()
                    ->orderByDesc('standing')
                    ->orderBy('contact_type')
                    ->get()
            )->resolve() : null,
            'characters' => CharacterSyncResource::collection($characters)->resolve(),
            'requestableOptions' => $this->requestableOptions($characters, $source, $requestStatuses),
        ]);
    }

    /**
     * A deduplicated list of the character/corporation/alliance entities the user
     * could request a standing for, each carrying a character to request through.
     * Whether an entity already has a standing is decided on the client against the
     * displayed standings list.
     *
     * @param  Collection<int, Character>  $characters
     * @param  Collection<string, value-of<StandingRequestStatus>>  $requestStatuses
     * @return array<int, array{type: string, id: int, name: string|null, via_character_id: int, status: string|null}>
     */
    private function requestableOptions(Collection $characters, ?StandingsSource $source, Collection $requestStatuses): array
    {
        if (! $source instanceof StandingsSource) {
            return [];
        }

        $options = [];
        $seen = [];

        foreach ($characters as $character) {
            if ($source->coversCharacter($character)) {
                continue;
            }

            $entities = [
                [StandingsSourceType::Character, $character->id, $character->name],
                [StandingsSourceType::Corporation, $character->corporation_id, $character->corporation?->name],
                [StandingsSourceType::Alliance, $character->alliance_id, $character->alliance?->name],
            ];

            foreach ($entities as [$type, $id, $name]) {
                if ($id === null) {
                    continue;
                }

                // The source entity itself is never requestable.
                if ($type === $source->type && $id === $source->entity_id) {
                    continue;
                }

                $key = $type->value.':'.$id;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $options[] = [
                    'type' => $type->value,
                    'id' => $id,
                    'name' => $name,
                    'via_character_id' => $character->id,
                    'status' => $requestStatuses->get($key),
                ];
            }
        }

        return $options;
    }
}
