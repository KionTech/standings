<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\EveSocialiteUser;
use App\Models\Character;
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
        private EveEntityService $entities,
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
        $this->entities->ensureAffiliationsExist($affiliation);

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

        $this->transferOwnership($character, $user);

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

        $character->character_owner_hash = $socialite_user->character_owner_hash;
        $this->transferOwnership($character, $user);

        return $user;
    }

    /**
     * Move a character onto a user's account. When the character belonged to a
     * different user before (an in-game character transfer, or someone adding
     * it to another account), the previous owner loses any stale references:
     * a main-character selection pointing at it is cleared, and an account
     * left without characters is removed entirely.
     */
    private function transferOwnership(Character $character, User $user): void
    {
        $previous_user = $character->user;

        $character->user()->associate($user);
        $character->save();

        if (! $previous_user instanceof User || $previous_user->is($user)) {
            return;
        }

        if ($previous_user->main_character_id === $character->id) {
            $previous_user->mainCharacter()->disassociate();
            $previous_user->save();
        }

        if (! $previous_user->characters()->exists()) {
            $previous_user->delete();
        }
    }
}
