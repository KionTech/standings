<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;

it('shows the source standings and the user characters on the dashboard', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);

    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);
    SourceContact::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('canViewStandings', true)
            ->has('standings', 2)
            ->has('characters', 1));
});

it('flags a character standing as redundant when its alliance carries the same standing', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);

    Alliance::query()->create(['id' => 4000, 'name' => 'Blue Alliance']);
    $member = Character::factory()->create(['alliance_id' => 4000]);

    SourceContact::factory()->create(['contact_id' => 4000, 'contact_type' => 'alliance', 'standing' => 10]);
    SourceContact::factory()->create(['contact_id' => $member->id, 'contact_type' => 'character', 'standing' => 10]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('standings.0.redundant_via', null)
            ->where('standings.1.redundant_via.contact_type', 'alliance')
            ->where('standings.1.redundant_via.contact_id', 4000)
            ->where('standings.1.redundant_via.name', 'Blue Alliance'));
});

it('flags a corporation standing as redundant when its alliance carries the same standing', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);

    Alliance::query()->create(['id' => 4000, 'name' => 'Blue Alliance']);
    Corporation::query()->create(['id' => 2000, 'name' => 'Blue Corp', 'alliance_id' => 4000]);

    SourceContact::factory()->create(['contact_id' => 4000, 'contact_type' => 'alliance', 'standing' => 5]);
    SourceContact::factory()->create(['contact_id' => 2000, 'contact_type' => 'corporation', 'standing' => 5]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('standings.1.redundant_via.contact_type', 'alliance')
            ->where('standings.1.redundant_via.contact_id', 4000));
});

it('flags a weaker standing that its parent covers in the same direction', function (float $parentStanding, float $memberStanding) {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);

    Alliance::query()->create(['id' => 4000, 'name' => 'Blue Alliance']);
    $member = Character::factory()->create(['alliance_id' => 4000]);

    SourceContact::factory()->create(['contact_id' => 4000, 'contact_type' => 'alliance', 'standing' => $parentStanding]);
    SourceContact::factory()->create(['contact_id' => $member->id, 'contact_type' => 'character', 'standing' => $memberStanding]);

    // Standings sort by value descending, so the weaker negative child
    // appears above its more-negative parent.
    $memberIndex = $memberStanding > $parentStanding ? 0 : 1;

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('standings.'.(1 - $memberIndex).'.redundant_via', null)
            ->where("standings.{$memberIndex}.redundant_via.contact_id", 4000));
})->with([
    'positive parent covers weaker positive' => [10.0, 5.0],
    'negative parent covers weaker negative' => [-10.0, -5.0],
]);

it('does not flag a standing its parent does not cover', function (float $parentStanding, float $memberStanding) {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);

    Alliance::query()->create(['id' => 4000, 'name' => 'Blue Alliance']);
    $member = Character::factory()->create(['alliance_id' => 4000]);

    SourceContact::factory()->create(['contact_id' => 4000, 'contact_type' => 'alliance', 'standing' => $parentStanding]);
    SourceContact::factory()->create(['contact_id' => $member->id, 'contact_type' => 'character', 'standing' => $memberStanding]);

    $memberIndex = $memberStanding > $parentStanding ? 0 : 1;

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("standings.{$memberIndex}.redundant_via", null));
})->with([
    'stronger positive than parent' => [5.0, 10.0],
    'stronger negative than parent' => [-5.0, -10.0],
    'opposite direction of parent' => [-10.0, 5.0],
    'neutral under a blue parent' => [10.0, 0.0],
]);

it('prefers the corporation over the alliance when both make a character redundant', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);

    Alliance::query()->create(['id' => 4000, 'name' => 'Blue Alliance']);
    Corporation::query()->create(['id' => 2000, 'name' => 'Blue Corp', 'alliance_id' => 4000]);
    $member = Character::factory()->create(['corporation_id' => 2000, 'alliance_id' => 4000]);

    SourceContact::factory()->create(['contact_id' => 4000, 'contact_type' => 'alliance', 'standing' => 10]);
    SourceContact::factory()->create(['contact_id' => 2000, 'contact_type' => 'corporation', 'standing' => 10]);
    SourceContact::factory()->create(['contact_id' => $member->id, 'contact_type' => 'character', 'standing' => 10]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('standings.1.redundant_via.contact_type', 'corporation')
            ->where('standings.1.redundant_via.contact_id', 2000));
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
