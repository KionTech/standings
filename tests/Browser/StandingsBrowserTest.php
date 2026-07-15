<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;

it('renders the welcome page without errors', function () {
    visit('/')
        ->assertSee('Bluebook')
        ->assertNoSmoke();
});

it('shows a grant-access banner for synced characters missing scopes', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create(['name' => 'Tokenless', 'should_sync' => true]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('need re-authentication')
        ->assertSee('Grant access')
        ->assertNoSmoke();
});

it('shows the standings hub with the user characters', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create(['name' => 'Test Pilot']);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Current standings')
        ->assertSee('Your characters')
        ->assertSee('Test Pilot')
        ->assertNoSmoke();
});

it('shows the source standings overview with resolved names', function () {
    $user = User::factory()->create();
    Alliance::query()->create(['id' => 3000, 'name' => 'Home Alliance']);
    Character::factory()->for($user)->create(['alliance_id' => 3000]);

    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);
    Corporation::query()->create(['id' => 4000, 'name' => 'Goonswarm Federation']);
    SourceContact::factory()->create([
        'contact_type' => 'corporation',
        'contact_id' => 4000,
        'standing' => 10,
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Goonswarm Federation')
        ->assertSee('+10')
        ->assertNoSmoke();
});

it('marks a character that inherits the source as inherited', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Source Corp', 'ticker' => 'SRCC']);
    Character::factory()->for($user)->create(['name' => 'Inheritor', 'corporation_id' => 2000]);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Inherited')
        ->assertSee('Inherits the source')
        ->assertNoSmoke();
});

it('shows each character corporation and alliance', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Test Corporation', 'ticker' => 'TSTC']);
    Alliance::query()->create(['id' => 3000, 'name' => 'Test Alliance', 'ticker' => 'TSTA']);
    Character::factory()->for($user)->create([
        'name' => 'Test Pilot',
        'corporation_id' => 2000,
        'alliance_id' => 3000,
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Test Corporation')
        ->assertSee('Test Alliance')
        ->assertNoSmoke();
});

it('shows the live last-refreshed time and a next-refresh countdown', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    StandingsSource::create([
        'type' => 'alliance',
        'entity_id' => 3000,
        'last_synced_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Last refreshed')
        ->assertSee('ago')
        ->assertSee('Next refresh')
        ->assertNoSmoke();
});

it('lets a user trigger a manual sync from the dashboard', function () {
    $user = User::factory()->create();
    // No write scope: nothing to queue, so the action is ESI-free.
    Character::factory()->for($user)->create(['name' => 'Pilot', 'should_sync' => true]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Sync now')
        ->click('Sync now')
        ->assertSee('No characters are set to sync.')
        ->assertNoSmoke();
});

it('opens a request-standing dialog with the eligible entities', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create(['name' => 'Outsider']);
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Request standing')
        ->click('Request standing')
        ->assertSee('Ask the admins to add')
        ->assertSee('Outsider')
        ->assertNoSmoke();
});

it('marks a request option that is already in the standings list', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Blue Corp']);
    Character::factory()->for($user)->create(['name' => 'Pilot', 'corporation_id' => 2000]);
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 2000, 'standing' => 5]);

    $this->actingAs($user);

    visit('/dashboard')
        ->click('Request standing')
        ->assertSee('On the list')
        ->assertNoSmoke();
});

it('does not offer a request for a character that inherits the source', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Source Corp']);
    Character::factory()->for($user)->create(['name' => 'Member', 'corporation_id' => 2000]);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertDontSee('Request standing')
        ->assertNoSmoke();
});
