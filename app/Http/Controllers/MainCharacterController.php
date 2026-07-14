<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MainCharacterController extends Controller
{
    public function update(Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        /** @var User $user */
        $user = Auth::user();

        $user->mainCharacter()->associate($character);

        if ($user->setup_completed_at === null) {
            $user->setup_completed_at = now()->toImmutable();
        }

        $user->save();

        return back()->with('success', sprintf('%s is now your main character.', $character->name));
    }
}
