<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\CharacterSyncedContact;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Services\CharacterStandingsSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use NicolasKion\Esi\Enums\EsiScope;

/**
 * Fake the ESI contacts endpoints. The GET (read current contacts) returns the
 * given contacts; all writes return an empty 200.
 *
 * @param  array<int, float>  $current  contact_id => standing
 */
function fakeContacts(array $current = []): void
{
    Http::fake(function (Request $request) use ($current) {
        if ($request->method() === 'GET') {
            return Http::response(
                collect($current)->map(fn ($standing, $id): array => [
                    'contact_id' => $id,
                    'contact_type' => 'character',
                    'standing' => $standing,
                    'label_ids' => [],
                ])->values()->all(),
                200,
            );
        }

        return Http::response([], 200);
    });
}

function syncingCharacter(): Character
{
    $character = Character::factory()->create(['should_sync' => true]);
    grantScopes($character, EsiScope::ReadCharacterContacts, EsiScope::WriteCharacterContacts);

    return $character->fresh();
}

it('adds new source contacts to a syncing character', function () {
    $character = syncingCharacter();
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    SourceContact::factory()->create(['contact_id' => 200, 'standing' => -10]);
    fakeContacts([]);

    app(CharacterStandingsSyncService::class)->sync($character);

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && str_contains($r->url(), "/characters/{$character->id}/contacts/")
        && in_array(100, $r->data(), true));

    expect($character->syncedContacts()->count())->toBe(2)
        ->and($character->syncedContacts()->where('contact_id', 100)->first()->standing)->toBe(5.0);
});

it('updates contacts whose standing changed', function () {
    $character = syncingCharacter();
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 10]);
    fakeContacts([100 => 5.0]);

    app(CharacterStandingsSyncService::class)->sync($character);

    Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
        && str_contains($r->url(), "/characters/{$character->id}/contacts/")
        && in_array(100, $r->data(), true));
});

it('does not write contacts that already match the source', function () {
    $character = syncingCharacter();
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    fakeContacts([100 => 5.0]);

    app(CharacterStandingsSyncService::class)->sync($character);

    Http::assertNotSent(fn (Request $r) => in_array($r->method(), ['POST', 'PUT'], true));
});

it('removes a previously synced contact once the source drops it', function () {
    $character = syncingCharacter();
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    CharacterSyncedContact::factory()->for($character)->create(['contact_id' => 100, 'standing' => 5]);
    CharacterSyncedContact::factory()->for($character)->create(['contact_id' => 999, 'standing' => -10]);

    // The character still has 100 and 999, plus a personal contact 555.
    fakeContacts([100 => 5.0, 999 => -10.0, 555 => 10.0]);

    app(CharacterStandingsSyncService::class)->sync($character);

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_contains($r->url(), 'contact_ids=999'));
    expect($character->syncedContacts()->where('contact_id', 999)->exists())->toBeFalse()
        ->and($character->syncedContacts()->where('contact_id', 100)->exists())->toBeTrue();
});

it('never deletes personal contacts that were never synced', function () {
    $character = syncingCharacter();
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    // 555 is a personal contact, not in the source nor the ledger.
    fakeContacts([100 => 5.0, 555 => 10.0]);

    app(CharacterStandingsSyncService::class)->sync($character);

    Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
});

it('skips characters that have not opted in', function () {
    $character = Character::factory()->create(['should_sync' => false]);
    grantScopes($character, EsiScope::ReadCharacterContacts, EsiScope::WriteCharacterContacts);
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    Http::fake();

    app(CharacterStandingsSyncService::class)->sync($character->fresh());

    Http::assertNothingSent();
});

it('skips characters without the write scope', function () {
    $character = Character::factory()->create(['should_sync' => true]);
    grantScopes($character, EsiScope::ReadCharacterContacts);
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    Http::fake();

    app(CharacterStandingsSyncService::class)->sync($character->fresh());

    Http::assertNothingSent();
});

it('skips characters that already inherit the source corporation standings', function () {
    Corporation::query()->create(['id' => 2000, 'name' => 'Source Corp']);
    $character = Character::factory()->create(['should_sync' => true, 'corporation_id' => 2000]);
    grantScopes($character, EsiScope::ReadCharacterContacts, EsiScope::WriteCharacterContacts);

    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create(['contact_id' => 100, 'standing' => 5]);
    Http::fake();

    app(CharacterStandingsSyncService::class)->sync($character->fresh());

    Http::assertNothingSent();
});
