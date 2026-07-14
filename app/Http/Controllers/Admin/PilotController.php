<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntitySummaryResource;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PilotController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('standings.admin');

        $search = $request->string('search')->trim()->value();
        $view = $request->string('view')->value();

        if (! in_array($view, ['corporations', 'alliances'], true)) {
            $view = 'accounts';
        }

        return Inertia::render('admin/Pilots', [
            'users' => $view === 'accounts' ? $this->accounts($search) : null,
            'groups' => match ($view) {
                'corporations' => $this->affiliations(Corporation::query(), 'corporation_id', $search),
                'alliances' => $this->affiliations(Alliance::query(), 'alliance_id', $search),
                default => null,
            },
            'filters' => [
                'search' => $search,
                'view' => $view,
            ],
        ]);
    }

    /**
     * Registered accounts with their characters, main character first.
     */
    private function accounts(string $search): LengthAwarePaginator
    {
        return User::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhereHas('characters', fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search)))))
            ->with([
                'characters:id,name,user_id,corporation_id,alliance_id',
                'characters.corporation:id,name,ticker',
                'characters.alliance:id,name,ticker',
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'characters' => $user->characters
                    ->sortBy('name')
                    ->sortByDesc(fn (Character $character): bool => $character->id === $user->main_character_id)
                    ->values()
                    ->map(fn (Character $character): array => [
                        'id' => $character->id,
                        'name' => $character->name,
                        'is_main' => $character->id === $user->main_character_id,
                        'corporation' => $character->corporation ? new EntitySummaryResource($character->corporation) : null,
                        'alliance' => $character->alliance ? new EntitySummaryResource($character->alliance) : null,
                    ])->all(),
            ]);
    }

    /**
     * Corporations or alliances that registered characters belong to, each with
     * the accounts affiliated through those characters.
     *
     * @template TModel of Corporation|Alliance
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TModel>  $query
     */
    private function affiliations(Builder $query, string $characterForeignKey, string $search): LengthAwarePaginator
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
                    ->select(['id', 'name', 'user_id', $characterForeignKey])
                    ->whereNotNull('user_id')
                    ->with(['user:id,name,main_character_id', 'user.mainCharacter:id,name']),
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Corporation|Alliance $entity): array => [
                'id' => $entity->id,
                'name' => $entity->name,
                'ticker' => $entity->ticker,
                'accounts' => $entity->characters
                    ->groupBy('user_id')
                    ->map(function (EloquentCollection $characters): array {
                        /** @var Character $first */
                        $first = $characters->first();

                        return [
                            'id' => $first->user_id,
                            'name' => $first->user->mainCharacter->name ?? $first->user?->name,
                            'avatar_character_id' => $first->user->main_character_id ?? $first->id,
                            'via' => $characters->sortBy('name')->pluck('name')->all(),
                        ];
                    })
                    ->sortBy('name')
                    ->values()
                    ->all(),
            ]);
    }
}
