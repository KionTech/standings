<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
            'auth' => fn () => $user ? [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'active_character' => $this->formatCharacter($user->getActiveCharacter()),
                'characters' => $user->characters->map(fn ($character) => $this->formatCharacter($character))->values()->all(),
            ] : ['user' => null],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{id: int, name: string|null, corporation_id: int|null, alliance_id: int|null}
     */
    private function formatCharacter(\App\Models\Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'corporation_id' => $character->corporation_id,
            'alliance_id' => $character->alliance_id,
        ];
    }
}
