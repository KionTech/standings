<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\StandingRequest;
use App\Models\User;

function browserAdmin(): User
{
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('shows the overview with stats and the section tabs', function () {
    $user = browserAdmin();
    StandingRequest::factory()
        ->for(Character::factory()->create(['name' => 'Requester Bob']))
        ->create(['status' => 'pending']);

    $this->actingAs($user);

    visit('/admin')
        ->assertSee('Overview')
        ->assertSee('Pending requests')
        ->assertSee('Standings source')
        ->assertSee('Requester Bob')
        ->assertNoSmoke();
});

it('shows the read-only source with confirm-to-change on the settings page', function () {
    $user = browserAdmin();

    $this->actingAs($user);

    visit('/admin/settings')
        ->assertSee('Standings source')
        ->assertSee('Discord notifications')
        ->click('Set source')
        ->assertSee('Save source')
        ->assertNoSmoke();
});

it('confirms and resolves a standing request with a toast', function () {
    $user = browserAdmin();
    $standingRequest = StandingRequest::factory()
        ->for(Character::factory()->create(['name' => 'Pending Pilot']))
        ->create(['status' => 'pending']);

    $this->actingAs($user);

    visit('/admin/standing-requests')
        ->assertSee('Pending Pilot')
        ->click('Mark done')
        ->assertSee('Mark request as done?')
        ->click('Confirm')
        ->assertSee('Standing request updated.')
        ->assertNoSmoke();

    expect($standingRequest->refresh()->status->value)->toBe('done');
});

it('shows a success toast after saving discord settings', function () {
    $user = browserAdmin();

    $this->actingAs($user);

    visit('/admin/settings')
        ->fill('webhook_url', 'https://discord.com/api/webhooks/1/abc')
        ->click('Save Discord settings')
        ->assertSee('Discord settings updated.')
        ->assertNoSmoke();
});

it('lists pilots with their main character and alts', function () {
    $user = browserAdmin();

    $pilot = User::factory()->create();
    $main = Character::factory()->for($pilot)->create(['name' => 'Main Pilot']);
    Character::factory()->for($pilot)->create(['name' => 'Alt Pilot']);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($user);

    visit('/admin/pilots')
        ->assertSee('Main Pilot')
        ->assertSee('Alt Pilot')
        ->assertNoSmoke();
});
