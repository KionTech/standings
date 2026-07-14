<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

test('the setup wizard is shown to a user without a main character', function () {
    $user = User::factory()->withoutSetup()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('showSetupWizard', true));
});

test('the setup wizard is not shown once a main character is selected', function () {
    $user = User::factory()->withoutSetup()->create();
    $main = Character::factory()->for($user)->create();
    $user->mainCharacter()->associate($main)->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('showSetupWizard', false));
});

test('the setup wizard is not shown after it was skipped', function () {
    $user = User::factory()->withoutSetup()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user);

    $this->post(route('setup.complete'))->assertRedirect();

    expect($user->refresh()->setup_completed_at)->not->toBeNull();

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('showSetupWizard', false));
});

test('selecting a main character completes the setup', function () {
    $user = User::factory()->withoutSetup()->create();
    $main = Character::factory()->for($user)->create();

    $this->actingAs($user);

    $this->put(route('auth.character.main', $main))->assertRedirect();

    $user->refresh();
    expect($user->main_character_id)->toBe($main->id);
    expect($user->setup_completed_at)->not->toBeNull();
});

test('completing the setup does not overwrite an earlier completion time', function () {
    $user = User::factory()->create(['setup_completed_at' => now()->subDay()]);
    Character::factory()->for($user)->create();

    $this->actingAs($user);

    $completedAt = $user->refresh()->setup_completed_at;

    $this->post(route('setup.complete'))->assertRedirect();

    expect($user->refresh()->setup_completed_at->timestamp)->toBe($completedAt->timestamp);
});
