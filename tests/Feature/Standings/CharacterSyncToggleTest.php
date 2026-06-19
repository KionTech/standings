<?php

declare(strict_types=1);

use App\Jobs\SyncCharacterStandings;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use NicolasKion\Esi\Enums\EsiScope;

it('lets a user re-sync only their own opted-in characters', function () {
    Queue::fake();

    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['should_sync' => true]);
    grantScopes($character, EsiScope::WriteCharacterContacts);

    $other = Character::factory()->for(User::factory())->create(['should_sync' => true]);
    grantScopes($other, EsiScope::WriteCharacterContacts);

    $this->actingAs($user)
        ->post(route('standings.sync'))
        ->assertRedirect();

    Queue::assertPushed(SyncCharacterStandings::class, 1);
    Queue::assertPushed(SyncCharacterStandings::class, fn (SyncCharacterStandings $job): bool => $job->character->is($character));
});

it('lets a user enable syncing for their own character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['should_sync' => false]);

    $this->actingAs($user)
        ->put(route('standings.update', $character), ['should_sync' => true])
        ->assertRedirect();

    expect($character->refresh()->should_sync)->toBeTrue();
});

it('lets a user disable syncing for their own character', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['should_sync' => true]);

    $this->actingAs($user)
        ->put(route('standings.update', $character), ['should_sync' => false])
        ->assertRedirect();

    expect($character->refresh()->should_sync)->toBeFalse();
});

it('forbids toggling a character belonging to another user', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    $otherCharacter = Character::factory()->for(User::factory())->create(['should_sync' => false]);

    $this->actingAs($user)
        ->put(route('standings.update', $otherCharacter), ['should_sync' => true])
        ->assertForbidden();

    expect($otherCharacter->refresh()->should_sync)->toBeFalse();
});

it('requires authentication to toggle syncing', function () {
    $character = Character::factory()->create();

    $this->put(route('standings.update', $character), ['should_sync' => true])
        ->assertRedirect(route('login'));
});
