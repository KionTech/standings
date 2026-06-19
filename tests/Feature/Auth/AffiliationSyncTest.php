<?php

declare(strict_types=1);

use App\DTO\EveSocialiteUser;
use App\Models\Alliance;
use App\Models\Corporation;
use App\Services\EsiAuthService;
use Illuminate\Support\Facades\Http;
use NicolasKion\Esi\DTO\CharacterAffiliation;

function socialiteUser(int $characterId): EveSocialiteUser
{
    return new EveSocialiteUser(
        character_id: $characterId,
        character_name: 'Test Pilot',
        character_owner_hash: 'owner-hash',
        token: 'access-token',
        refresh_token: 'refresh-token',
        token_type: 'Bearer',
        expires_in: 1200,
        scopes: [],
    );
}

function fakeCorporationResponse(int $allianceId): array
{
    return [
        'alliance_id' => $allianceId,
        'ceo_id' => 95_000_002,
        'creator_id' => 95_000_003,
        'date_founded' => '2020-01-01T00:00:00Z',
        'description' => 'A test corporation.',
        'faction_id' => null,
        'home_station_id' => null,
        'member_count' => 42,
        'name' => 'Test Corporation',
        'shares' => 1000,
        'tax_rate' => 0.1,
        'ticker' => 'TSTC',
        'url' => 'https://example.com',
        'war_eligible' => true,
    ];
}

function fakeAllianceResponse(): array
{
    return [
        'creator_corporation_id' => 98_000_002,
        'creator_id' => 95_000_004,
        'date_founded' => '2019-01-01T00:00:00Z',
        'name' => 'Test Alliance',
        'ticker' => 'TSTA',
        'executor_corporation_id' => 98_000_001,
        'faction_id' => null,
    ];
}

it('fetches and stores corporation and alliance details on login', function () {
    Http::fake([
        'esi.evetech.net/corporations/98000001/*' => Http::response(fakeCorporationResponse(99_000_001), 200),
        'esi.evetech.net/alliances/99000001/*' => Http::response(fakeAllianceResponse(), 200),
    ]);

    $affiliation = new CharacterAffiliation(
        character_id: 95_000_001,
        corporation_id: 98_000_001,
        alliance_id: 99_000_001,
        faction_id: null,
    );

    $character = app(EsiAuthService::class)->resolveCharacter(socialiteUser(95_000_001), $affiliation);

    expect($character->corporation_id)->toBe(98_000_001)
        ->and($character->alliance_id)->toBe(99_000_001);

    $corporation = Corporation::query()->find(98_000_001);
    expect($corporation->name)->toBe('Test Corporation')
        ->and($corporation->ticker)->toBe('TSTC')
        ->and($corporation->member_count)->toBe(42);

    $alliance = Alliance::query()->find(99_000_001);
    expect($alliance->name)->toBe('Test Alliance')
        ->and($alliance->ticker)->toBe('TSTA');
});

it('stores a character with no alliance', function () {
    $corporation = fakeCorporationResponse(0);
    unset($corporation['alliance_id']);

    Http::fake([
        'esi.evetech.net/corporations/98000001/*' => Http::response($corporation, 200),
    ]);

    $affiliation = new CharacterAffiliation(
        character_id: 95_000_001,
        corporation_id: 98_000_001,
        alliance_id: null,
        faction_id: null,
    );

    $character = app(EsiAuthService::class)->resolveCharacter(socialiteUser(95_000_001), $affiliation);

    expect($character->alliance_id)->toBeNull()
        ->and(Corporation::query()->find(98_000_001)->name)->toBe('Test Corporation')
        ->and(Alliance::query()->count())->toBe(0);
});
