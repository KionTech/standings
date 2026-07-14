<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

test('users can set their main character', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $main = Character::factory()->for($user)->create();

    $this->actingAs($user);

    $this->put(route('auth.character.main', $main))
        ->assertRedirect();

    expect($user->refresh()->main_character_id)->toBe($main->id);
});

test('users cannot set a character they do not own as main', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $otherCharacter = Character::factory()->create();

    $this->actingAs($user);

    $this->put(route('auth.character.main', $otherCharacter))
        ->assertForbidden();

    expect($user->refresh()->main_character_id)->toBeNull();
});

test('removing the main character clears the main selection', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    $main = Character::factory()->for($user)->create();
    $user->mainCharacter()->associate($main)->save();

    $this->actingAs($user);

    $this->delete(route('auth.character.destroy', $main))
        ->assertRedirect();

    expect($user->refresh()->main_character_id)->toBeNull();
});

test('removing an alt keeps the main selection', function () {
    $user = User::factory()->create();
    $main = Character::factory()->for($user)->create();
    $alt = Character::factory()->for($user)->create();
    $user->mainCharacter()->associate($main)->save();

    $this->actingAs($user);

    $this->delete(route('auth.character.destroy', $alt))
        ->assertRedirect();

    expect($user->refresh()->main_character_id)->toBe($main->id);
});

test('the dashboard marks the main character', function () {
    $user = User::factory()->create();
    $main = Character::factory()->for($user)->create();
    Character::factory()->for($user)->create();
    $user->mainCharacter()->associate($main)->save();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('characters', fn ($characters) => collect($characters)
                ->contains(fn (array $character): bool => $character['id'] === $main->id && $character['is_main'] === true)));
});
