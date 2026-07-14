<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

it('walks a new user through the wizard and sets the main character', function () {
    $user = User::factory()->withoutSetup()->create();
    $main = Character::factory()->for($user)->create(['name' => 'First Pilot']);
    Character::factory()->for($user)->create(['name' => 'Second Pilot']);

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Welcome, pilot')
        ->click('Next')
        ->assertSee('Add all your characters')
        ->assertSee('Currently on your account:')
        ->assertSee('Second Pilot')
        ->click('Next')
        ->assertSee('Standings sync themselves')
        ->click('Next')
        ->assertSee('Pick your main character')
        ->click("[data-test=wizard-character-{$main->id}]")
        ->click('Set main & finish')
        ->assertDontSee('Welcome, pilot')
        ->assertNoSmoke();

    expect($user->refresh()->main_character_id)->toBe($main->id);
    expect($user->setup_completed_at)->not->toBeNull();
});

it('lets a new user skip the wizard', function () {
    $user = User::factory()->withoutSetup()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->assertSee('Welcome, pilot')
        ->click('Skip for now')
        ->assertDontSee('Welcome, pilot')
        ->assertNoSmoke();

    expect($user->refresh()->setup_completed_at)->not->toBeNull();
    expect($user->main_character_id)->toBeNull();
});

it('does not show the wizard to a user who completed setup', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->assertDontSee('Welcome, pilot')
        ->assertNoSmoke();
});
