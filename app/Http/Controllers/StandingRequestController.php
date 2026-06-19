<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StandingRequestStatus;
use App\Enums\StandingsSourceType;
use App\Jobs\NotifyStandingRequest;
use App\Models\Character;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StandingRequestController extends Controller
{
    public function store(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(StandingsSourceType::class)],
        ]);

        $type = StandingsSourceType::from($validated['type']);

        $subjectId = match ($type) {
            StandingsSourceType::Character => $character->id,
            StandingsSourceType::Corporation => $character->corporation_id,
            StandingsSourceType::Alliance => $character->alliance_id,
        };

        $source = StandingsSource::current();

        if ($subjectId === null || ! $source instanceof StandingsSource || ! $source->requiresStandingForEntity($type, $subjectId)) {
            return back()->with('error', 'This standing cannot be requested.');
        }

        $existing = StandingRequest::query()
            ->where('subject_type', $type)
            ->where('subject_id', $subjectId)
            ->first();

        if ($existing && in_array($existing->status, [StandingRequestStatus::Pending, StandingRequestStatus::Done], true)) {
            return back()->with('info', 'This standing has already been requested.');
        }

        $standingRequest = StandingRequest::query()->updateOrCreate(
            ['subject_type' => $type, 'subject_id' => $subjectId],
            ['requested_by_character_id' => $character->id, 'status' => StandingRequestStatus::Pending],
        );

        NotifyStandingRequest::dispatch($standingRequest);

        return back()->with('success', 'Standing request submitted.');
    }
}
