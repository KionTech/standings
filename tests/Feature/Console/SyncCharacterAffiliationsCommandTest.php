<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Pin the clock outside EVE's daily downtime window (11:00-11:15 UTC) so
    // the command's downtime guard never trips depending on when tests run.
    Carbon::setTestNow(Carbon::parse('2026-06-25 14:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('refreshes the affiliations of user characters and source contacts', function () {
    // A user character whose corporation changed in game.
    Corporation::query()->create(['id' => 2001, 'name' => 'Old Corp']);
    $userCharacter = Character::factory()->create(['corporation_id' => 2001]);

    // A contact character the app has never seen locally.
    SourceContact::factory()->create(['contact_id' => 95_000_500, 'contact_type' => 'character', 'standing' => 10]);

    // A contact corporation that joined an alliance since it was stored.
    Corporation::query()->create(['id' => 2002, 'name' => 'Member Corp']);
    SourceContact::factory()->create(['contact_id' => 2002, 'contact_type' => 'corporation', 'standing' => 10]);

    // A contact alliance not yet known locally.
    SourceContact::factory()->create(['contact_id' => 100, 'contact_type' => 'alliance', 'standing' => 10]);

    Http::fake([
        'esi.evetech.net/characters/affiliation/*' => Http::response([
            ['character_id' => $userCharacter->id, 'corporation_id' => 2003],
            ['character_id' => 95_000_500, 'corporation_id' => 2002, 'alliance_id' => 100],
        ], 200),
        'esi.evetech.net/corporations/2002/*' => Http::response(esiCorporationPayload('Member Corp', 100), 200),
        'esi.evetech.net/corporations/2003/*' => Http::response(esiCorporationPayload('New Corp'), 200),
        'esi.evetech.net/alliances/100/*' => Http::response(esiAlliancePayload('Alpha Alliance'), 200),
    ]);

    $this->artisan('characters:sync-affiliations')->assertSuccessful();

    expect($userCharacter->refresh()->corporation_id)->toBe(2003)
        ->and(Character::query()->find(95_000_500)->corporation_id)->toBe(2002)
        ->and(Character::query()->find(95_000_500)->alliance_id)->toBe(100)
        ->and(Corporation::query()->find(2002)->alliance_id)->toBe(100)
        ->and(Alliance::query()->find(100)->name)->toBe('Alpha Alliance');
});

it('skips the affiliation sync during eve downtime', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-25 11:05:00', 'UTC'));
    Character::factory()->create();
    Http::fake();

    $this->artisan('characters:sync-affiliations')->assertSuccessful();

    Http::assertNothingSent();
});
