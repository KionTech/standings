<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\StandingRequestResource;
use App\Models\StandingRequest;
use App\Services\EffectiveStandingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StandingRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('standings.admin');

        $status = StandingRequestStatus::tryFrom($request->string('status')->value()) ?? StandingRequestStatus::Pending;

        $resolver = new EffectiveStandingResolver;

        $standingRequests = StandingRequest::query()
            ->with(['character:id,name,corporation_id,alliance_id', 'character.corporation:id,name', 'character.alliance:id,name'])
            ->where('status', $status)
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(function (StandingRequest $standingRequest) use ($resolver): array {
                $standingRequest->setAttribute('effective_standing', $resolver->resolve($standingRequest));

                return (new StandingRequestResource($standingRequest))->resolve();
            });

        /** @var object{pending: int, done: int, rejected: int}|null $counts */
        $counts = StandingRequest::query()
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        return Inertia::render('admin/StandingRequests', [
            'standingRequests' => $standingRequests,
            'counts' => [
                'pending' => (int) ($counts->pending ?? 0),
                'done' => (int) ($counts->done ?? 0),
                'rejected' => (int) ($counts->rejected ?? 0),
            ],
            'filters' => [
                'status' => $status->value,
            ],
        ]);
    }

    public function update(Request $request, StandingRequest $standingRequest): RedirectResponse
    {
        Gate::authorize('standings.admin');

        $validated = $request->validate([
            'status' => ['required', Rule::enum(StandingRequestStatus::class)],
        ]);

        $standingRequest->update(['status' => $validated['status']]);

        return back()->with('success', 'Standing request updated.');
    }
}
