<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\StandingRequestResource;
use App\Http\Resources\StandingsSourceSummaryResource;
use App\Models\Character;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Models\User;
use App\Services\EffectiveStandingResolver;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('standings.admin');

        $source = StandingsSource::current();

        $recentRequests = StandingRequest::query()
            ->with(['character:id,name,corporation_id,alliance_id', 'character.corporation:id,name', 'character.alliance:id,name'])
            ->where('status', StandingRequestStatus::Pending)
            ->latest()
            ->limit(5)
            ->get();

        $resolver = new EffectiveStandingResolver;
        $recentRequests->each(fn (StandingRequest $request) => $request->setAttribute(
            'effective_standing',
            $resolver->resolve($request),
        ));

        return Inertia::render('admin/Overview', [
            'source' => $source ? (new StandingsSourceSummaryResource($source))->resolve() : null,
            'stats' => [
                'pending_requests' => StandingRequest::query()->where('status', StandingRequestStatus::Pending)->count(),
                'pilots' => User::query()->count(),
                'syncing_characters' => Character::query()->whereNotNull('user_id')->where('should_sync', true)->count(),
                'source_contacts' => SourceContact::query()->count(),
            ],
            'recentRequests' => StandingRequestResource::collection($recentRequests)->resolve(),
        ]);
    }
}
