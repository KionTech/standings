<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveCharacterFromUserAction;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserCharacterController extends Controller
{
    public function update(Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        /** @var User $user */
        $user = Auth::user();

        $user->setActiveCharacter($character);

        return back()->with('success', sprintf('Switched to %s.', $character->name));
    }

    public function destroy(Character $character, RemoveCharacterFromUserAction $action): RedirectResponse
    {
        Gate::authorize('delete', $character);

        /** @var User $user */
        $user = Auth::user();

        $character_name = $character->name;
        $is_last_character = $user->characters()->count() <= 1;

        $action->handle($character);

        if ($is_last_character) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            $user->delete();

            return to_route('home')->with('success', 'Your account has been deleted.');
        }

        $user->setActiveCharacter($user->characters()->first());

        return back()->with('success', sprintf('%s has been removed from your account.', $character_name));
    }
}
