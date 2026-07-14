<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntitySummaryResource;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Services\EffectiveStandingResolver;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PilotController extends Controller
{
    public function __construct(private readonly EffectiveStandingResolver $standings) {}

    public function index(Request $request): Response
    {
        Gate::authorize('standings.admin');

        $search = $request->string('search')->trim()->value();
        $view = $request->string('view')->value();

        if (! in_array($view, ['corporations', 'alliances'], true)) {
            $view = 'characters';
        }

        return Inertia::render('admin/Pilots', [
            'characters' => $view === 'characters' ? $this->characters() : null,
            'groups' => match ($view) {
                'corporations' => $this->affiliations(Corporation::query(), $search),
                'alliances' => $this->affiliations(Alliance::query(), $search),
                default => null,
            },
            'filters' => [
                'search' => $search,
                'view' => $view,
            ],
        ]);
    }

    /**
     * Every registered character with its account, affiliations, and effective
     * standing. The list is complete; searching and sorting happen client-side.
     *
     * @return array<int, array<string, mixed>>
     */
    private function characters(): array
    {
        return Character::query()
            ->whereNotNull('user_id')
            ->with([
                'corporation:id,name,ticker',
                'alliance:id,name,ticker',
                'user:id,name,main_character_id',
                'user.mainCharacter:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Character $character): array => $this->characterRow($character))
            ->all();
    }

    /**
     * Corporations or alliances that registered characters belong to, each
     * with its effective standing and the registered characters inside it.
     *
     * @template TModel of Corporation|Alliance
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     */
    private function affiliations(Builder $query, string $search): LengthAwarePaginator
    {
        return $query
            ->whereHas('characters', fn (Builder $query) => $query->whereNotNull('user_id'))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('ticker', 'like', sprintf('%%%s%%', $search))
                ->orWhereHas('characters', fn (Builder $query) => $query
                    ->whereNotNull('user_id')
                    ->where('name', 'like', sprintf('%%%s%%', $search)))))
            ->with([
                'characters' => fn ($query) => $query
                    ->select(['id', 'name', 'user_id', 'corporation_id', 'alliance_id'])
                    ->whereNotNull('user_id')
                    ->orderBy('name')
                    ->with([
                        'corporation:id,name,ticker',
                        'alliance:id,name,ticker',
                        'user:id,name,main_character_id',
                        'user.mainCharacter:id,name',
                    ]),
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Corporation|Alliance $entity): array => [
                'id' => $entity->id,
                'name' => $entity->name,
                'ticker' => $entity->ticker,
                'standing' => $this->standings->standingForEntity($entity),
                'characters' => $entity->characters
                    ->map(fn (Character $character): array => $this->characterRow($character))
                    ->all(),
            ]);
    }

    /**
     * A character row for the pilot tables: the character, the account it
     * belongs to (named after its main character), its affiliations, and its
     * effective standing.
     *
     * @return array{id: int, name: string|null, is_main: bool, account: array{id: int|null, name: string|null}, standing: array{value: float, source: string}|null, corporation: EntitySummaryResource|null, alliance: EntitySummaryResource|null}
     */
    private function characterRow(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'is_main' => $character->id === $character->user?->main_character_id,
            'account' => [
                'id' => $character->user_id,
                'name' => $character->user?->mainCharacter->name ?? $character->user?->name,
            ],
            'standing' => $this->standings->standingForCharacter($character),
            'corporation' => $character->corporation ? new EntitySummaryResource($character->corporation) : null,
            'alliance' => $character->alliance ? new EntitySummaryResource($character->alliance) : null,
        ];
    }
}
