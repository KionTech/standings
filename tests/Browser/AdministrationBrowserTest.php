<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\StandingRequest;
use App\Models\User;

it('shows the read-only source and standing requests to the admin', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);
    StandingRequest::factory()->for(Character::factory()->create(['name' => 'Requester Bob']))->create();

    $this->actingAs($user);

    visit('/admin')
        ->assertSee('Standings source')
        ->assertSee('Standing requests')
        ->assertSee('Requester Bob')
        ->assertSee('Mark done')
        ->click('Set source')
        ->assertSee('Save source')
        ->assertNoSmoke();
});

it('shows the discord settings form', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user);

    visit('/admin')
        ->assertSee('Discord notifications')
        ->assertSee('Webhook URL')
        ->assertSee('Role to ping')
        ->assertNoSmoke();
});

it('confirms and resolves a standing request with a toast', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);
    $standingRequest = StandingRequest::factory()
        ->for(Character::factory()->create(['name' => 'Pending Pilot']))
        ->create(['status' => 'pending']);

    $this->actingAs($user);

    visit('/admin')
        ->click('Mark done')
        ->assertSee('Mark request as done?')
        ->click('Confirm')
        ->assertSee('Standing request updated.')
        ->assertNoSmoke();

    expect($standingRequest->refresh()->status->value)->toBe('done');
});

it('shows a success toast after saving discord settings', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user);

    visit('/admin')
        ->fill('webhook_url', 'https://discord.com/api/webhooks/1/abc')
        ->click('Save Discord settings')
        ->assertSee('Discord settings updated.')
        ->assertNoSmoke();
});
