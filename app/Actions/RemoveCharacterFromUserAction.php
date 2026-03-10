<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class RemoveCharacterFromUserAction
{
    public function handle(Character $character): void
    {
        DB::transaction(function () use ($character): void {
            $character->esiTokens()->delete();
            $character->user()->disassociate();
            $character->character_owner_hash = null;
            $character->save();
        });
    }
}
