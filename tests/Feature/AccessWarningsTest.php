<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\StandingsSource;
use App\Models\User;
use NicolasKion\Esi\Enums\EsiScope;

it('flags characters missing the write-contacts token', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create(['name' => 'Tokenless']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.reauth_characters.0.name', 'Tokenless'));
});

it('does not flag a character that has the write-contacts token', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    grantScopes($character, EsiScope::WriteCharacterContacts);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.reauth_characters', []));
});

it('flags an unreadable source for admins', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    grantScopes($character, EsiScope::WriteCharacterContacts);
    config(['services.eveonline.admin_character_ids' => [$character->id]]);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.source_unreadable', true));
});

it('does not flag the source for non-admins', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    grantScopes($character, EsiScope::WriteCharacterContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.source_unreadable', false));
});
