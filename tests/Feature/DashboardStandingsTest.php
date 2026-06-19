<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;

it('shows the source standings and the user characters on the dashboard', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);
    SourceContact::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('standings', 2)
            ->has('characters', 1));
});

it('offers a request option for a character with no standing', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('requestableOptions.0.type', 'character')
            ->where('requestableOptions.0.status', null));
});

it('exposes the corporation entity as a deduplicated request option', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Blue Corp']);
    $character = Character::factory()->for($user)->create(['corporation_id' => 2000]);
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('requestableOptions.1.type', 'corporation')
            ->where('requestableOptions.1.id', 2000)
            ->where('requestableOptions.1.via_character_id', $character->id));
});
