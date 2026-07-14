<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use App\Services\EsiAuthService;

test('adding another users character clears their main selection', function () {
    $previousOwner = User::factory()->create();
    Character::factory()->for($previousOwner)->create();
    $transferred = Character::factory()->for($previousOwner)->create();
    $previousOwner->mainCharacter()->associate($transferred)->save();

    $newOwner = User::factory()->create();
    Character::factory()->for($newOwner)->create();

    app(EsiAuthService::class)->addToAccount($transferred, $newOwner->id);

    expect($transferred->refresh()->user_id)->toBe($newOwner->id);
    expect($previousOwner->refresh()->main_character_id)->toBeNull();
});

test('adding another users character keeps an unrelated main selection', function () {
    $previousOwner = User::factory()->create();
    $main = Character::factory()->for($previousOwner)->create();
    $transferred = Character::factory()->for($previousOwner)->create();
    $previousOwner->mainCharacter()->associate($main)->save();

    $newOwner = User::factory()->create();
    Character::factory()->for($newOwner)->create();

    app(EsiAuthService::class)->addToAccount($transferred, $newOwner->id);

    expect($previousOwner->refresh()->main_character_id)->toBe($main->id);
});

test('taking a users last character deletes the abandoned account', function () {
    $previousOwner = User::factory()->create();
    $transferred = Character::factory()->for($previousOwner)->create();
    $previousOwner->mainCharacter()->associate($transferred)->save();

    $newOwner = User::factory()->create();
    Character::factory()->for($newOwner)->create();

    app(EsiAuthService::class)->addToAccount($transferred, $newOwner->id);

    expect($transferred->refresh()->user_id)->toBe($newOwner->id);
    expect(User::find($previousOwner->id))->toBeNull();
});

test('re-adding your own character changes nothing', function () {
    $owner = User::factory()->create();
    $main = Character::factory()->for($owner)->create();
    $owner->mainCharacter()->associate($main)->save();

    app(EsiAuthService::class)->addToAccount($main, $owner->id);

    $owner->refresh();
    expect($owner->main_character_id)->toBe($main->id);
    expect(User::find($owner->id))->not->toBeNull();
});
