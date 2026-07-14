<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

function pilotsAdmin(): User
{
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('filters pilots by character name', function () {
    $admin = pilotsAdmin();

    $pilot = User::factory()->create();
    Character::factory()->for($pilot)->create(['name' => 'Jita Trader']);
    $other = User::factory()->create();
    Character::factory()->for($other)->create(['name' => 'Amarr Hauler']);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['search' => 'Jita']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Pilots')
            ->has('users.data', 1)
            ->where('users.data.0.id', $pilot->id)
            ->where('filters.search', 'Jita'));
});

it('filters pilots by account name', function () {
    $admin = pilotsAdmin();

    $pilot = User::factory()->create(['name' => 'Fleet Commander']);
    Character::factory()->for($pilot)->create(['name' => 'Some Character']);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['search' => 'Fleet Commander']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $pilot->id));
});

it('returns every pilot without a search term', function () {
    $admin = pilotsAdmin();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.pilots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 3));
});

it('forbids non-admins from the pilots page', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)->get(route('admin.pilots.index'))->assertForbidden();
});

it('groups accounts by corporation with their via characters', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Blue Corp', 'ticker' => 'BLUE']);
    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Main Pilot', 'corporation_id' => 5000]);
    Character::factory()->for($pilot)->create(['name' => 'Alt Pilot', 'corporation_id' => 5000]);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'corporations', 'search' => 'BLUE']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Pilots')
            ->where('users', null)
            ->has('groups.data', 1)
            ->where('groups.data.0.name', 'Blue Corp')
            ->where('groups.data.0.accounts.0.name', 'Main Pilot')
            ->where('groups.data.0.accounts.0.via', ['Alt Pilot', 'Main Pilot'])
            ->where('filters.view', 'corporations'));
});

it('groups accounts by alliance', function () {
    $admin = pilotsAdmin();

    App\Models\Alliance::query()->create(['id' => 6000, 'name' => 'Blue Alliance', 'ticker' => 'BLUA']);
    $pilot = User::factory()->create();
    Character::factory()->for($pilot)->create(['name' => 'Alliance Pilot', 'alliance_id' => 6000]);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'alliances']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('groups.data', 1)
            ->where('groups.data.0.name', 'Blue Alliance')
            ->where('groups.data.0.accounts.0.via', ['Alliance Pilot']));
});

it('excludes corporations without registered characters', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Empty Corp']);
    Character::factory()->create(['corporation_id' => 5000, 'user_id' => null]);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'corporations']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('groups.data', 0));
});
