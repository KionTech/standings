<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;

it('hides the standings from users without an eligible character', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->count(2)->create(['standing' => 10]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canViewStandings', false)
            ->where('standings', null));
});

it('shows the standings to a user whose character is covered by the source', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Source Corp']);
    Character::factory()->for($user)->create(['corporation_id' => 2000]);

    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('canViewStandings', true)
            ->has('standings', 1));
});

it('shows the standings to a user with a directly blue character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create([
        'contact_type' => 'character',
        'contact_id' => $character->id,
        'standing' => 5,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('canViewStandings', true));
});

it('shows the standings to a user blue via their alliance', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Blue Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create([
        'contact_type' => 'alliance',
        'contact_id' => 3000,
        'standing' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('canViewStandings', true));
});

it('keeps the standings hidden from a red character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create([
        'contact_type' => 'character',
        'contact_id' => $character->id,
        'standing' => -10,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('canViewStandings', false)
            ->where('standings', null));
});

it('always shows the standings to admins', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('canViewStandings', true));
});

it('still offers request options to ineligible users', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('canViewStandings', false)
            ->where('requestableOptions.0.type', 'character'));
});
