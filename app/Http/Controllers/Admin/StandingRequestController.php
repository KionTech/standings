<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StandingRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\StandingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StandingRequestController extends Controller
{
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
