<?php

declare(strict_types=1);

use App\Enums\StandingRequestStatus;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Services\StandingsSourceService;
use Illuminate\Support\Facades\Http;
use NicolasKion\Esi\Enums\EsiScope;

/**
 * Create an admin character that can read a corp/alliance/character source.
 */
function adminReader(string $type, int $entityId, EsiScope $scope): Character
{
    $attributes = ['id' => $entityId];

    if ($type === 'corporation') {
        Corporation::query()->firstOrCreate(['id' => $entityId]);
        $attributes = ['corporation_id' => $entityId];
    } elseif ($type === 'alliance') {
        Alliance::query()->firstOrCreate(['id' => $entityId]);
        $attributes = ['alliance_id' => $entityId];
    }

    $admin = Character::factory()->create($attributes);
    grantScopes($admin, $scope);
    config(['services.eveonline.admin_character_ids' => [$admin->id]]);

    return $admin;
}

it('fetches and stores corporation source contacts', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'alliance', 'standing' => 5, 'label_ids' => []],
            ['contact_id' => 200, 'contact_type' => 'character', 'standing' => -10, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'alliance', 'id' => 100, 'name' => 'Alpha Alliance'],
            ['category' => 'character', 'id' => 200, 'name' => 'Bravo Pilot'],
        ], 200),
        ...esiAffiliationFakes(200, 2001, 100, 'Bravo Corp'),
        'esi.evetech.net/alliances/100/*' => Http::response(esiAlliancePayload('Alpha Alliance'), 200),
    ]);

    expect(app(StandingsSourceService::class)->refresh())->toBeTrue();

    expect(SourceContact::count())->toBe(2)
        ->and(SourceContact::where('contact_id', 100)->first()->standing)->toBe(5.0)
        ->and(SourceContact::where('contact_id', 100)->first()->name)->toBe('Alpha Alliance')
        ->and(SourceContact::where('contact_id', 200)->first()->name)->toBe('Bravo Pilot')
        ->and(StandingsSource::current()->last_synced_at)->not->toBeNull();
});

it('stores contact entities locally with their affiliations', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 95_000_300, 'contact_type' => 'character', 'standing' => 10, 'label_ids' => []],
            ['contact_id' => 2001, 'contact_type' => 'corporation', 'standing' => 10, 'label_ids' => []],
            ['contact_id' => 100, 'contact_type' => 'alliance', 'standing' => 10, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => 95_000_300, 'name' => 'Member Pilot'],
        ], 200),
        ...esiAffiliationFakes(95_000_300, 2001, 100),
        'esi.evetech.net/alliances/100/*' => Http::response(esiAlliancePayload('Alpha Alliance'), 200),
    ]);

    app(StandingsSourceService::class)->refresh();

    $character = Character::query()->find(95_000_300);

    expect($character)->not->toBeNull()
        ->and($character->name)->toBe('Member Pilot')
        ->and($character->corporation_id)->toBe(2001)
        ->and($character->alliance_id)->toBe(100)
        ->and(Corporation::query()->find(2001)->alliance_id)->toBe(100)
        ->and(Alliance::query()->find(100)->name)->toBe('Alpha Alliance');
});

it('removes source contacts the source no longer has', function () {
    adminReader('alliance', 3000, EsiScope::ReadAllianceContacts);
    StandingsSource::create(['type' => 'alliance', 'entity_id' => 3000]);
    SourceContact::factory()->create(['contact_id' => 999, 'standing' => 10]);

    Http::fake([
        'esi.evetech.net/alliances/3000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => 100, 'name' => 'Charlie Pilot'],
        ], 200),
        ...esiAffiliationFakes(100, 2001, corporation_name: 'Charlie Corp'),
    ]);

    app(StandingsSourceService::class)->refresh();

    expect(SourceContact::where('contact_id', 999)->exists())->toBeFalse()
        ->and(SourceContact::where('contact_id', 100)->exists())->toBeTrue();
});

it('does nothing when no source is configured', function () {
    adminReader('character', 95_000_001, EsiScope::ReadCharacterContacts);
    Http::fake();

    expect(app(StandingsSourceService::class)->refresh())->toBeNull();
    Http::assertNothingSent();
});

it('does nothing when no admin can read the source', function () {
    adminReader('corporation', 2000, EsiScope::ReadCharacterContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    Http::fake();

    expect(app(StandingsSourceService::class)->refresh())->toBeNull();
    Http::assertNothingSent();
});

it('marks a pending request done when a direct blue standing appears', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $character = Character::factory()->create();
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => $character->id, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => $character->id, 'name' => 'Requested Pilot'],
        ], 200),
        ...esiAffiliationFakes($character->id, 2001, corporation_name: 'Requested Corp'),
    ]);

    app(StandingsSourceService::class)->refresh();

    expect($request->refresh()->status)->toBe(StandingRequestStatus::Done);
});

it('does not close a request when the direct standing is neutral or red', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $character = Character::factory()->create();
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => $character->id, 'contact_type' => 'character', 'standing' => 0, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => $character->id, 'name' => 'Requested Pilot'],
        ], 200),
        ...esiAffiliationFakes($character->id, 2001, corporation_name: 'Requested Corp'),
    ]);

    app(StandingsSourceService::class)->refresh();

    expect($request->refresh()->status)->toBe(StandingRequestStatus::Pending);
});

it('does not auto-close a request that is only blue through a parent', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    Corporation::query()->firstOrCreate(['id' => 4000], ['name' => 'Member Corp']);
    $character = Character::factory()->create(['corporation_id' => 4000]);
    $request = StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $character->id,
        'requested_by_character_id' => $character->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 4000, 'contact_type' => 'corporation', 'standing' => 10, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'corporation', 'id' => 4000, 'name' => 'Member Corp'],
        ], 200),
        'esi.evetech.net/corporations/4000/*' => Http::response(esiCorporationPayload('Member Corp'), 200),
    ]);

    app(StandingsSourceService::class)->refresh();

    expect($request->refresh()->status)->toBe(StandingRequestStatus::Pending);
});

it('reports no change and skips name resolution when the standings are unchanged', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create(['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5]);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
    ]);

    expect(app(StandingsSourceService::class)->refresh())->toBeFalse();

    // No name-resolution call when nothing changed, to save requests.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/universe/names/'));
});
