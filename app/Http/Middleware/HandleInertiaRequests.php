<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Resources\CharacterResource;
use App\Models\StandingsSource;
use App\Models\User;
use App\Services\StandingsSourceService;
use App\Support\EveSso;
use Illuminate\Http\Request;
use Inertia\Middleware;
use NicolasKion\Esi\Enums\EsiScope;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => function () use ($user) {
                if (! $user) {
                    return ['user' => null];
                }

                $user->loadMissing(['characters.corporation:id,name,ticker', 'characters.alliance:id,name,ticker']);

                $isAdmin = $user->isStandingsAdmin();

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'is_admin' => $isAdmin,
                    'source_unreadable' => $isAdmin && $this->sourceUnreadable(),
                    // Only characters that opted into syncing need the
                    // contacts scopes; scopeless registrations are fine.
                    'reauth_characters' => $user->characters()
                        ->where('should_sync', true)
                        ->whereDoesntHave('esiTokens.esiScopes', fn ($query) => $query->where('name', EsiScope::WriteCharacterContacts))
                        ->get(['id', 'name'])
                        ->map(fn ($character): array => ['id' => $character->id, 'name' => $character->name])
                        ->all(),
                    'sync_scopes_url' => EveSso::grantScopesUrl('services.eveonline.sync_scopes'),
                    'admin_scopes_url' => $isAdmin ? EveSso::grantScopesUrl('services.eveonline.admin_scopes') : null,
                    'active_character' => new CharacterResource($user->getActiveCharacter()),
                    'characters' => CharacterResource::collection($user->characters),
                ];
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * Whether a source is configured but no admin character can currently read it.
     */
    private function sourceUnreadable(): bool
    {
        $source = StandingsSource::current();

        return $source instanceof StandingsSource && app(StandingsSourceService::class)->reader($source) === null;
    }
}
