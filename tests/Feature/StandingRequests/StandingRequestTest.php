<?php

declare(strict_types=1);

use App\Jobs\NotifyStandingRequest;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\DiscordSetting;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('lets a user request standing for their character', function () {
    Queue::fake();

    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->post(route('standing-requests.store', $character), ['type' => 'character'])
        ->assertRedirect();

    $this->assertDatabaseHas('standing_requests', [
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
        'status' => 'pending',
    ]);
    Queue::assertPushed(NotifyStandingRequest::class);
});

it('lets a user request standing for their corporation', function () {
    Queue::fake();

    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'My Corp']);
    $character = Character::factory()->for($user)->create(['corporation_id' => 2000]);
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->post(route('standing-requests.store', $character), ['type' => 'corporation'])
        ->assertRedirect();

    $this->assertDatabaseHas('standing_requests', [
        'subject_type' => 'corporation',
        'subject_id' => 2000,
        'requested_by_character_id' => $character->id,
    ]);
});

it('does not allow a request when the subject already has standing', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create(['contact_type' => 'character', 'contact_id' => $character->id]);

    $this->actingAs($user)
        ->post(route('standing-requests.store', $character), ['type' => 'character'])
        ->assertRedirect();

    $this->assertDatabaseMissing('standing_requests', ['subject_id' => $character->id]);
});

it('forbids requesting standing for another users character', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $other = Character::factory()->for(User::factory())->create();
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);

    $this->actingAs($user)
        ->post(route('standing-requests.store', $other), ['type' => 'character'])
        ->assertForbidden();
});

it('does not duplicate a pending request for the same subject', function () {
    Queue::fake();

    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('standing-requests.store', $character), ['type' => 'character'])
        ->assertRedirect();

    Queue::assertNothingPushed();
    expect(StandingRequest::query()->count())->toBe(1);
});

it('posts a standing request to the configured discord webhook and pings the role', function () {
    DiscordSetting::query()->create([
        'webhook_url' => 'https://discord.test/webhook',
        'role_id' => '123456789',
    ]);
    Http::fake();

    $character = Character::factory()->create(['name' => 'Lonewolf']);
    $standingRequest = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
    ]);

    (new NotifyStandingRequest($standingRequest))->handle();

    Http::assertSent(function ($request) {
        $body = json_encode($request->data());

        return $request->url() === 'https://discord.test/webhook'
            && str_contains($body, '<@&123456789>')
            // Name wrapped in a copyable code block.
            && str_contains($body, '```\nLonewolf\n```')
            // Entity portrait/logo as the embed thumbnail.
            && str_contains($body, 'images.evetech.net\/characters\/');
    });
});

it('skips the discord webhook when none is configured', function () {
    config(['services.discord.standing_request_webhook' => null]);
    Http::fake();

    (new NotifyStandingRequest(StandingRequest::factory()->create()))->handle();

    Http::assertNothingSent();
});
