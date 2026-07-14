<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    /**
     * Mark the first-login setup wizard as completed (or skipped) so it does
     * not open again automatically.
     */
    public function store(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->setup_completed_at === null) {
            $user->setup_completed_at = now()->toImmutable();
            $user->save();
        }

        return back();
    }
}
