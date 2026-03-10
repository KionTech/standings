<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EsiAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use NicolasKion\Esi\Enums\EsiScope;
use SocialiteProviders\Eveonline\Provider;

class EveController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function store(EsiAuthService $esiAuthService): RedirectResponse
    {
        $account_id = Session::get('add_to_account');

        [$user, $character] = $esiAuthService->getUser($account_id);

        if (! $user) {
            return to_route('home')
                ->with('error', 'Unable to retrieve user information from EVE Online. Please try again later.');
        }

        Auth::login($user, remember: true);
        $user->setActiveCharacter($character);

        if ($account_id) {
            return to_route('dashboard')
                ->with('success', 'Your character has been added to your account.');
        }

        $redirect = redirect()->intended(route('dashboard'))->getTargetUrl();

        return redirect($redirect)
            ->with('success', sprintf('Welcome back, %s!', $character->name));
    }

    public function show(Request $request): RedirectResponse
    {
        if ($request->query('add_to_account')) {
            $request->session()->put('add_to_account', auth()->id());
        }

        $eve_provider = Socialite::driver('eveonline');

        assert($eve_provider instanceof Provider);

        if ($request->boolean('without_scopes')) {
            return $eve_provider->redirect();
        }

        return $eve_provider
            ->scopes(
                $request->scopes
                    ? EsiScope::fromRequest($request->scopes)
                    : array_map(
                        static fn (EsiScope $scope) => $scope->value,
                        config('services.eveonline.required_scopes', [])
                    )
            )
            ->redirect();
    }
}
