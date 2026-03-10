<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    public function update(User $user, Character $character): bool
    {
        return $this->ownsCharacter($user, $character);
    }

    public function delete(User $user, Character $character): bool
    {
        return $this->ownsCharacter($user, $character);
    }

    private function ownsCharacter(User $user, Character $character): bool
    {
        return $user->characters()->where('id', $character->id)->exists();
    }
}
