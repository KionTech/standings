<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingsSourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\StandingsSourceSummaryResource;
use App\Models\Character;
use App\Models\DiscordSetting;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;
use App\Support\EveSso;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use NicolasKion\Esi\Enums\EsiScope;

class SettingsController extends Controller
{
    public function edit(#[CurrentUser] User $user): Response
    {
        Gate::authorize('standings.admin');

        $source = StandingsSource::current();
        $admin = $user->getActiveCharacter()->loadMissing(['corporation:id,name', 'alliance:id,name']);

        return Inertia::render('admin/Settings', [
            'source' => $source ? new StandingsSourceSummaryResource($source) : null,
            'sourceTypes' => collect(StandingsSourceType::cases())
                ->map(function (StandingsSourceType $type) use ($admin): array {
                    [$entity_id, $entity_name] = $this->resolveAdminEntity($admin, $type);

                    return [
                        'value' => $type->value,
                        'label' => $type->label(),
                        'entity_id' => $entity_id,
                        'entity_name' => $entity_name,
                        'has_scope' => $admin->hasEsiTokenWithScope($type->requiredScope()),
                        'available' => $entity_id !== null && $admin->hasEsiTokenWithScope($type->requiredScope()),
                    ];
                })->values(),
            'adminCharacter' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'has_mail_scope' => $admin->hasEsiTokenWithScope(EsiScope::SendMail),
            ],
            'grantMailScopeUrl' => EveSso::grantScopesUrl('services.eveonline.admin_scopes'),
            'contactsCount' => SourceContact::query()->count(),
            'discordSettings' => [
                'webhook_url' => DiscordSetting::current()->webhook_url,
                'role_id' => DiscordSetting::current()->role_id,
            ],
        ]);
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveAdminEntity(Character $admin, StandingsSourceType $type): array
    {
        return match ($type) {
            StandingsSourceType::Character => [$admin->id, $admin->name],
            StandingsSourceType::Corporation => [$admin->corporation_id, $admin->corporation?->name],
            StandingsSourceType::Alliance => [$admin->alliance_id, $admin->alliance?->name],
        };
    }
}
