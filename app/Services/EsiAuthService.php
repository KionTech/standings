<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\EveSocialiteUser;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\EsiScope;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Socialite\Facades\Socialite;
use NicolasKion\Esi\DTO\CharacterAffiliation;
use NicolasKion\Esi\Esi;
use Throwable;

readonly class EsiAuthService
{
    public function __construct(
        private Esi $esi,
    ) {}

    /**
     * @return array{0: ?User, 1: ?Character}
     *
     * @throws ConnectionException
     * @throws Throwable
     */
    public function getUser(?int $add_to_user_id = null): array
    {
        $socialite_user = $this->getSocialiteUser();

        if (! $socialite_user) {
            return [null, null];
        }

        $affiliations = $this->esi->getAffiliations([$socialite_user->character_id]);

        if ($affiliations->failed() || $affiliations->data === []) {
            return [null, null];
        }

        $character = $this->resolveCharacter($socialite_user, $affiliations->data[0]);

        $this->createEsiToken($socialite_user, $character);

        if ($add_to_user_id) {
            return [
                $this->addToAccount($character, $add_to_user_id),
                $character,
            ];
        }

        if ($character->user()->exists() && $character->character_owner_hash === $socialite_user->character_owner_hash) {
            return [$character->user, $character];
        }

        return [
            $this->createNewUser($socialite_user, $character),
            $character,
        ];
    }

    public function getSocialiteUser(): EveSocialiteUser|false
    {
        try {
            $data = Socialite::driver('eveonline')->user();
        } catch (Exception) {
            return false;
        }

        return EveSocialiteUser::fromSocialiteUser($data);
    }

    public function resolveCharacter(EveSocialiteUser $socialite_user, CharacterAffiliation $affiliation): Character
    {
        $this->ensureAffiliationsExist($affiliation);

        return Character::query()->updateOrCreate([
            'id' => $socialite_user->character_id,
        ], [
            'id' => $socialite_user->character_id,
            'name' => $socialite_user->character_name,
            'alliance_id' => $affiliation->alliance_id,
            'corporation_id' => $affiliation->corporation_id,
        ]);
    }

    public function createEsiToken(EveSocialiteUser $socialite_user, Character $character): void
    {
        $token = $character->esiTokens()->create([
            'access_token' => $socialite_user->token,
            'refresh_token' => $socialite_user->refresh_token,
            'token_type' => $socialite_user->token_type,
            'character_owner_hash' => $socialite_user->character_owner_hash,
            'expires_at' => now()->addSeconds($socialite_user->expires_in),
        ]);

        $token->esiScopes()->sync(
            EsiScope::query()->whereIn('name', $socialite_user->scopes)->pluck('id')
        );
    }

    public function addToAccount(Character $character, int $user_id): User
    {
        $user = User::query()->findOrFail($user_id);

        $character->user()->associate($user);
        $character->save();

        return $user;
    }

    /**
     * @throws Throwable
     */
    public function createNewUser(EveSocialiteUser $socialite_user, Character $character): User
    {
        $user = User::query()->create([
            'name' => $socialite_user->character_name,
        ]);

        $character->user()->associate($user);
        $character->character_owner_hash = $socialite_user->character_owner_hash;
        $character->save();

        return $user;
    }

    private function ensureAffiliationsExist(CharacterAffiliation $affiliation): void
    {
        if ($affiliation->alliance_id) {
            $this->ensureAlliance($affiliation->alliance_id);
        }

        $this->ensureCorporation($affiliation->corporation_id);
    }

    /**
     * Make sure the alliance exists locally, fetching its public details from
     * ESI the first time we see it. Only non-foreign-key columns are stored.
     */
    private function ensureAlliance(int $id): void
    {
        $alliance = Alliance::query()->firstOrNew(['id' => $id]);

        if ($alliance->exists && $alliance->name !== null) {
            return;
        }

        $alliance->id = $id;

        $result = $this->esi->getAlliance($id);

        if ($result->wasSuccessful()) {
            $alliance->fill([
                'name' => $result->data->name,
                'ticker' => $result->data->ticker,
                'date_founded' => $result->data->date_founded,
            ]);
        }

        $alliance->save();
    }

    /**
     * Make sure the corporation exists locally, fetching its public details from
     * ESI the first time we see it. Only non-foreign-key columns are stored.
     */
    private function ensureCorporation(int $id): void
    {
        $corporation = Corporation::query()->firstOrNew(['id' => $id]);

        if ($corporation->exists && $corporation->name !== null) {
            return;
        }

        $corporation->id = $id;

        $result = $this->esi->getCorporation($id);

        if ($result->wasSuccessful()) {
            $alliance_id = $result->data->alliance_id ?: null;

            if ($alliance_id) {
                $this->ensureAlliance($alliance_id);
            }

            $corporation->fill([
                'name' => $result->data->name,
                'ticker' => $result->data->ticker,
                'member_count' => $result->data->member_count,
                'tax_rate' => $result->data->tax_rate,
                'war_eligible' => $result->data->war_eligible,
                'date_founded' => $result->data->date_founded,
                'description' => $result->data->description,
                'url' => $result->data->url,
                'shares' => $result->data->shares,
                'alliance_id' => $alliance_id,
            ]);
        }

        $corporation->save();
    }
}
