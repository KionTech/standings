<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Services\EffectiveStandingResolver;

function resolveFor(StandingRequest $request): ?array
{
    return (new EffectiveStandingResolver)->resolve($request->load('character'));
}

it('resolves a standing set directly on the entity', function () {
    $character = Character::factory()->create();
    SourceContact::factory()->create([
        'contact_type' => 'character',
        'contact_id' => $character->id,
        'standing' => 7,
    ]);
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
    ]);

    expect(resolveFor($request))->toMatchArray([
        'standing' => 7.0,
        'source' => 'direct',
        'via_type' => 'character',
        'via_id' => $character->id,
    ]);
});

it('inherits a standing from the requesting characters corporation', function () {
    Corporation::query()->create(['id' => 4000, 'name' => 'Blue Corp']);
    $character = Character::factory()->create(['corporation_id' => 4000]);
    SourceContact::factory()->create([
        'contact_type' => 'corporation',
        'contact_id' => 4000,
        'standing' => 5,
    ]);
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
    ]);

    expect(resolveFor($request))->toMatchArray([
        'standing' => 5.0,
        'source' => 'corporation',
        'via_id' => 4000,
        'via_name' => 'Blue Corp',
    ]);
});

it('prefers a direct standing over an inherited one', function () {
    Corporation::query()->create(['id' => 4000, 'name' => 'Blue Corp']);
    $character = Character::factory()->create(['corporation_id' => 4000]);
    SourceContact::factory()->create(['contact_type' => 'character', 'contact_id' => $character->id, 'standing' => 3]);
    SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 4000, 'standing' => 10]);
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
    ]);

    expect(resolveFor($request))->toMatchArray(['standing' => 3.0, 'source' => 'direct']);
});

it('returns null when neither the entity nor a parent has a standing', function () {
    $character = Character::factory()->create();
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
    ]);

    expect(resolveFor($request))->toBeNull();
});
