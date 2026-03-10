<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

test('users can switch their active character', function () {
    $user = User::factory()->create();
    $character1 = Character::factory()->for($user)->create();
    $character2 = Character::factory()->for($user)->create();

    $this->actingAs($user);
    $user->setActiveCharacter($character1);

    $this->put(route('auth.character.update', $character2))
        ->assertRedirect();

    expect(session()->get(User::SESSION_ACTIVE_CHARACTER_ID))->toBe($character2->id);
});

test('users cannot switch to a character they do not own', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $otherCharacter = Character::factory()->create();

    $this->actingAs($user);

    $this->put(route('auth.character.update', $otherCharacter))
        ->assertForbidden();
});

test('users can remove a character from their account', function () {
    $user = User::factory()->create();
    $character1 = Character::factory()->for($user)->create();
    $character2 = Character::factory()->for($user)->create();

    $this->actingAs($user);
    $user->setActiveCharacter($character1);

    $this->delete(route('auth.character.destroy', $character2))
        ->assertRedirect();

    expect($user->fresh())->not->toBeNull();
    expect(Character::find($character2->id)->user_id)->toBeNull();
});

test('removing last character deletes the user account', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    $this->actingAs($user);

    $this->delete(route('auth.character.destroy', $character))
        ->assertRedirect(route('home'));

    expect(User::find($user->id))->toBeNull();
    expect(Character::find($character->id)->user_id)->toBeNull();
});

test('users cannot remove a character they do not own', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $otherCharacter = Character::factory()->create();

    $this->actingAs($user);

    $this->delete(route('auth.character.destroy', $otherCharacter))
        ->assertForbidden();
});

test('active character switches to remaining character after removal', function () {
    $user = User::factory()->create();
    $character1 = Character::factory()->for($user)->create();
    $character2 = Character::factory()->for($user)->create();

    $this->actingAs($user);
    $user->setActiveCharacter($character1);

    $this->delete(route('auth.character.destroy', $character1))
        ->assertRedirect();

    expect(session()->get(User::SESSION_ACTIVE_CHARACTER_ID))->toBe($character2->id);
});
