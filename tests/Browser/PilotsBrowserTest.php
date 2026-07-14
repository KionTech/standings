<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\User;

it('renders the pilots corporation view with standings and member characters', function () {
    $admin = User::factory()->create();
    $adminCharacter = Character::factory()->for($admin)->create(['name' => 'Admin Pilot']);
    config(['services.eveonline.admin_character_ids' => [$adminCharacter->id]]);

    Alliance::query()->create(['id' => 99014466, 'name' => 'Strix Ridens.', 'ticker' => 'STRIX']);
    Corporation::query()->create(['id' => 98630705, 'name' => 'Strix Ridens', 'ticker' => 'STRX', 'alliance_id' => 99014466]);
    Corporation::query()->create(['id' => 98792349, 'name' => 'Kion Trading Inc.', 'ticker' => 'KION']);

    SourceContact::factory()->create(['contact_type' => 'alliance', 'contact_id' => 99014466, 'standing' => 10]);
    SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 98792349, 'standing' => -5]);

    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Nicolas Pilot', 'corporation_id' => 98630705, 'alliance_id' => 99014466]);
    Character::factory()->for($pilot)->create(['name' => 'Tina Pilot', 'corporation_id' => 98792349]);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($admin);

    // Groups list their member characters directly; collapsing a group hides
    // its rows.
    visit('/admin/pilots?view=corporations')
        ->assertSee('Kion Trading Inc.')
        ->assertSee('via alliance')
        ->assertSee('via corporation')
        ->assertSee('Tina Pilot')
        ->click('Kion Trading Inc.')
        ->assertDontSee('Tina Pilot')
        ->assertNoSmoke();
});

it('renders the flat character list with standings', function () {
    $admin = User::factory()->create();
    $adminCharacter = Character::factory()->for($admin)->create(['name' => 'Admin Pilot']);
    config(['services.eveonline.admin_character_ids' => [$adminCharacter->id]]);

    Corporation::query()->create(['id' => 98792349, 'name' => 'Kion Trading Inc.', 'ticker' => 'KION']);
    SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 98792349, 'standing' => -5]);

    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Nicolas Pilot', 'corporation_id' => 98792349]);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($admin);

    visit('/admin/pilots')
        ->assertSee('Nicolas Pilot')
        ->assertSee('Kion Trading Inc.')
        ->assertSee('-5')
        ->assertNoSmoke();
});
