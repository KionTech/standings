<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\StandingRequest;
use App\Models\User;

function administrationAdmin(): User
{
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('shows the administration page to the admin', function () {
    $user = administrationAdmin();
    StandingRequest::factory()->for(Character::factory()->create(['name' => 'Requester']))->create();

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Administration')
            ->has('sourceTypes', 3)
            ->has('standingRequests', 1));
});

it('treats any of the comma-separated admin characters as an admin', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [111111, $character->id, 222222]]);

    $this->actingAs($user)->get(route('admin.index'))->assertOk();
});

it('forbids non-admins from the administration page', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
});

it('lets the admin mark a request as done', function () {
    $user = administrationAdmin();
    $standingRequest = StandingRequest::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->put(route('admin.standing-requests.update', $standingRequest), ['status' => 'done'])
        ->assertRedirect();

    expect($standingRequest->refresh()->status->value)->toBe('done');
});

it('lets the admin reject a request', function () {
    $user = administrationAdmin();
    $standingRequest = StandingRequest::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->put(route('admin.standing-requests.update', $standingRequest), ['status' => 'rejected'])
        ->assertRedirect();

    expect($standingRequest->refresh()->status->value)->toBe('rejected');
});

it('forbids non-admins from resolving requests', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);
    $standingRequest = StandingRequest::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->put(route('admin.standing-requests.update', $standingRequest), ['status' => 'done'])
        ->assertForbidden();

    expect($standingRequest->refresh()->status->value)->toBe('pending');
});

it('lets the admin update the discord settings', function () {
    $user = administrationAdmin();

    $this->actingAs($user)
        ->put(route('admin.discord-settings.update'), [
            'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'role_id' => '123456',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('discord_settings', [
        'webhook_url' => 'https://discord.com/api/webhooks/1/abc',
        'role_id' => '123456',
    ]);
});

it('validates the discord settings', function () {
    $user = administrationAdmin();

    $this->actingAs($user)
        ->put(route('admin.discord-settings.update'), [
            'webhook_url' => 'not-a-url',
            'role_id' => 'abc',
        ])
        ->assertSessionHasErrors(['webhook_url', 'role_id']);
});

it('forbids non-admins from updating discord settings', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)
        ->put(route('admin.discord-settings.update'), ['webhook_url' => 'https://discord.com/x'])
        ->assertForbidden();
});
