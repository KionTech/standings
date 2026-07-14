<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

function pilotsAdmin(): User
{
    $user = User::factory()->create();
    // A fixed name so alphabetical assertions on the character list hold.
    $character = Character::factory()->for($user)->create(['name' => 'Admin Pilot']);
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('lists every registered character with its account and standing', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Blue Corp', 'ticker' => 'BLUE']);
    App\Models\SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 5000, 'standing' => 5]);

    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Main Pilot', 'corporation_id' => 5000]);
    Character::factory()->for($pilot)->create(['name' => 'Alt Pilot']);
    $pilot->mainCharacter()->associate($main)->save();

    // Characters without an account never show.
    Character::factory()->create(['user_id' => null, 'name' => 'Unregistered']);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Pilots')
            ->where('groups', null)
            ->has('characters', 3)
            ->where('characters.1.name', 'Alt Pilot')
            ->where('characters.1.account.name', 'Main Pilot')
            ->where('characters.1.standing', null)
            ->where('characters.2.name', 'Main Pilot')
            ->where('characters.2.is_main', true)
            ->where('characters.2.standing.value', 5)
            ->where('characters.2.standing.source', 'corporation'));
});

it('passes the search term through for client-side filtering', function () {
    $admin = pilotsAdmin();
    Character::factory()->for(User::factory()->create())->create(['name' => 'Jita Trader']);

    // The characters list stays complete; the client applies the filter.
    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['search' => 'Jita']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('characters', 2)
            ->where('filters.search', 'Jita'));
});

it('forbids non-admins from the pilots page', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)->get(route('admin.pilots.index'))->assertForbidden();
});

it('groups corporations with their standing and member characters', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Blue Corp', 'ticker' => 'BLUE']);
    App\Models\SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 5000, 'standing' => 5]);

    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Main Pilot', 'corporation_id' => 5000]);
    Character::factory()->for($pilot)->create(['name' => 'Alt Pilot', 'corporation_id' => 5000]);
    // An alt outside the corporation is not part of its member list.
    Character::factory()->for($pilot)->create(['name' => 'Outside Alt']);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'corporations', 'search' => 'BLUE']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Pilots')
            ->where('characters', null)
            ->has('groups.data', 1)
            ->where('groups.data.0.name', 'Blue Corp')
            ->where('groups.data.0.standing.value', 5)
            ->where('groups.data.0.standing.source', 'corporation')
            ->has('groups.data.0.characters', 2)
            ->where('groups.data.0.characters.0.name', 'Alt Pilot')
            ->where('groups.data.0.characters.0.account.name', 'Main Pilot')
            ->where('groups.data.0.characters.0.standing.value', 5)
            ->where('groups.data.0.characters.1.is_main', true)
            ->where('filters.view', 'corporations'));
});

it('groups alliances with their member characters', function () {
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
            ->where('groups.data.0.standing', null)
            ->where('groups.data.0.characters.0.name', 'Alliance Pilot'));
});

it('filters group views by character name on the server', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Blue Corp']);
    Character::factory()->for(User::factory()->create())->create(['name' => 'Jita Trader', 'corporation_id' => 5000]);
    App\Models\Corporation::query()->create(['id' => 5001, 'name' => 'Other Corp']);
    Character::factory()->for(User::factory()->create())->create(['name' => 'Amarr Hauler', 'corporation_id' => 5001]);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'corporations', 'search' => 'Jita']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('groups.data', 1)
            ->where('groups.data.0.name', 'Blue Corp'));
});

it('marks characters and corporations covered by the source as green source standings', function () {
    $admin = pilotsAdmin();

    App\Models\Corporation::query()->create(['id' => 5000, 'name' => 'Source Corp']);
    App\Models\StandingsSource::create(['type' => 'corporation', 'entity_id' => 5000]);

    $pilot = User::factory()->create();
    Character::factory()->for($pilot)->create(['name' => 'Member Pilot', 'corporation_id' => 5000]);

    $this->actingAs($admin)
        ->get(route('admin.pilots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('characters', function ($characters) {
                $row = collect($characters)->firstWhere('name', 'Member Pilot');

                return $row !== null
                    && $row['standing']['value'] === 10
                    && $row['standing']['source'] === 'source';
            }));

    $this->actingAs($admin)
        ->get(route('admin.pilots.index', ['view' => 'corporations']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('groups.data.0.name', 'Source Corp')
            ->where('groups.data.0.standing.source', 'source'));
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
