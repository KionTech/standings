<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingsSourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\StandingRequestResource;
use App\Models\Character;
use App\Models\DiscordSetting;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('standings.admin');

        $source = StandingsSource::current();
        $admin = $user->getActiveCharacter()->loadMissing(['corporation:id,name', 'alliance:id,name']);

        return Inertia::render('admin/Administration', [
            'source' => $source ? [
                'type' => $source->type->value,
                'entity_id' => $source->entity_id,
                'entity_name' => $source->entityName(),
                'last_synced_at' => $source->last_synced_at?->toIso8601String(),
            ] : null,
            'sourceTypes' => collect(StandingsSourceType::cases())
                ->map(function (StandingsSourceType $type) use ($admin): array {
                    [$entity_id, $entity_name] = $this->resolveAdminEntity($admin, $type);

                    return [
                        'value' => $type->value,
                        'label' => $type->label(),
                        'entity_id' => $entity_id,
                        'entity_name' => $entity_name,
                        'has_scope' => $admin?->hasEsiTokenWithScope($type->requiredScope()) ?? false,
                        'available' => $entity_id !== null && ($admin?->hasEsiTokenWithScope($type->requiredScope()) ?? false),
                    ];
                })->values(),
            'adminCharacter' => $admin ? ['id' => $admin->id, 'name' => $admin->name] : null,
            'contactsCount' => SourceContact::query()->count(),
            'discordSettings' => [
                'webhook_url' => DiscordSetting::current()->webhook_url,
                'role_id' => DiscordSetting::current()->role_id,
            ],
            'standingRequests' => StandingRequestResource::collection(
                StandingRequest::query()
                    ->with(['character:id,name,corporation_id,alliance_id', 'character.corporation:id,name', 'character.alliance:id,name'])
                    ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                    ->latest()
                    ->get()
            )->resolve(),
        ]);
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveAdminEntity(?Character $admin, StandingsSourceType $type): array
    {
        if (! $admin) {
            return [null, null];
        }

        return match ($type) {
            StandingsSourceType::Character => [$admin->id, $admin->name],
            StandingsSourceType::Corporation => [$admin->corporation_id, $admin->corporation?->name],
            StandingsSourceType::Alliance => [$admin->alliance_id, $admin->alliance?->name],
        };
    }
}
