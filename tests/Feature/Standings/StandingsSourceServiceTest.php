<?php

declare(strict_types=1);

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
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
    ]);

    expect(app(StandingsSourceService::class)->refresh())->toBeTrue();

    expect(SourceContact::count())->toBe(2)
        ->and(SourceContact::where('contact_id', 100)->first()->standing)->toBe(5.0)
        ->and(SourceContact::where('contact_id', 100)->first()->name)->toBe('Alpha Alliance')
        ->and(SourceContact::where('contact_id', 200)->first()->name)->toBe('Bravo Pilot')
        ->and(StandingsSource::current()->last_synced_at)->not->toBeNull();
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

it('reports no change and skips name resolution when the standings are unchanged', function () {
    adminReader('corporation', 2000, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create(['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'name' => 'Existing']);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
    ]);

    expect(app(StandingsSourceService::class)->refresh())->toBeFalse();

    // No name-resolution call when nothing changed, to save requests.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/universe/names/'));
});
