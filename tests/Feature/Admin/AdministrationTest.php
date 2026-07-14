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

it('shows the overview with stats and recent requests to the admin', function () {
    $user = administrationAdmin();
    StandingRequest::factory()->for(Character::factory()->create(['name' => 'Requester']))->create(['status' => 'pending']);

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Overview')
            ->where('stats.pending_requests', 1)
            ->where('stats.pilots', 2)
            ->has('recentRequests', 1));
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

it('lists every registered character under its account on the pilots page', function () {
    $admin = administrationAdmin();

    $pilot = User::factory()->create();
    $alt = Character::factory()->for($pilot)->create(['name' => 'Alt Pilot']);
    $main = Character::factory()->for($pilot)->create(['name' => 'Main Pilot']);
    $pilot->mainCharacter()->associate($main)->save();

    $this->actingAs($admin)
        ->get(route('admin.pilots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Pilots')
            ->where('characters', function ($characters) use ($main, $alt) {
                $rows = collect($characters);
                $mainRow = $rows->firstWhere('id', $main->id);
                $altRow = $rows->firstWhere('id', $alt->id);

                return $mainRow !== null && $altRow !== null
                    && $mainRow['is_main'] === true
                    && $altRow['is_main'] === false
                    && $mainRow['account']['name'] === 'Main Pilot'
                    && $altRow['account']['name'] === 'Main Pilot';
            }));
});

it('shows pending requests on the standing requests page by default', function () {
    $user = administrationAdmin();
    StandingRequest::factory()->for(Character::factory()->create(['name' => 'Requester']))->create();
    StandingRequest::factory()->create(['status' => 'done']);

    $this->actingAs($user)
        ->get(route('admin.standing-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/StandingRequests')
            ->has('standingRequests.data', 1)
            ->where('standingRequests.data.0.status', 'pending')
            ->where('counts.pending', 1)
            ->where('counts.done', 1)
            ->where('filters.status', 'pending'));
});

it('shows done requests only when the done filter is selected', function () {
    $user = administrationAdmin();
    StandingRequest::factory()->create(['status' => 'pending']);
    StandingRequest::factory()->create(['status' => 'done']);

    $this->actingAs($user)
        ->get(route('admin.standing-requests.index', ['status' => 'done']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('standingRequests.data', 1)
            ->where('standingRequests.data.0.status', 'done')
            ->where('filters.status', 'done'));
});

it('paginates the standing requests list', function () {
    $user = administrationAdmin();
    StandingRequest::factory()->count(30)->create(['status' => 'pending']);

    $this->actingAs($user)
        ->get(route('admin.standing-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('standingRequests.data', 25)
            ->where('standingRequests.total', 30));
});
