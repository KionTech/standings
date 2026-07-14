<?php

declare(strict_types=1);

use App\Jobs\SyncCharacterStandings;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use NicolasKion\Esi\Enums\EsiScope;

beforeEach(function () {
    // Pin the clock outside EVE's daily downtime window (11:00-11:15 UTC) so
    // the sync command's downtime guard never trips depending on when tests run.
    Carbon::setTestNow(Carbon::parse('2026-06-25 14:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Create a user who owns the configured admin character, with a corp and alliance.
 */
function adminUser(): User
{
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 2000, 'name' => 'Admin Corp', 'ticker' => 'ADMC']);
    Alliance::query()->create(['id' => 3000, 'name' => 'Admin Alliance', 'ticker' => 'ADMA']);
    $character = Character::factory()->for($user)->create([
        'corporation_id' => 2000,
        'alliance_id' => 3000,
    ]);
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('forbids non-admins from updating the source', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'corporation'])
        ->assertForbidden();

    expect(StandingsSource::current())->toBeNull();
});

it('derives the entity id from the admin character', function () {
    $user = adminUser();

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'corporation'])
        ->assertRedirect();

    $source = StandingsSource::current();
    expect($source->type->value)->toBe('corporation')
        ->and($source->entity_id)->toBe(2000);
});

it('uses the alliance id when the alliance is selected', function () {
    $user = adminUser();

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'alliance'])
        ->assertRedirect();

    expect(StandingsSource::current()->entity_id)->toBe(3000);
});

it('rejects a source type the admin character does not have', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'alliance'])
        ->assertSessionHasErrors(['type']);

    expect(StandingsSource::current())->toBeNull();
});

it('clears canonical contacts when the source changes', function () {
    $user = adminUser();
    StandingsSource::create(['type' => 'character', 'entity_id' => 1]);
    SourceContact::factory()->count(3)->create();

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'corporation'])
        ->assertRedirect();

    expect(SourceContact::count())->toBe(0)
        ->and(StandingsSource::current()->entity_id)->toBe(2000);
});

it('validates the source type', function () {
    $user = adminUser();

    $this->actingAs($user)
        ->put(route('admin.standings.update'), ['type' => 'invalid'])
        ->assertSessionHasErrors(['type']);
});

it('queues a sync for opted-in characters when the admin clicks sync now', function () {
    Queue::fake();

    $user = adminUser();
    $admin = $user->characters()->first();
    grantScopes($admin, EsiScope::ReadCorporationContacts);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $syncable = Character::factory()->create(['should_sync' => true]);
    grantScopes($syncable, EsiScope::WriteCharacterContacts);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => 100, 'name' => 'Contact One'],
        ], 200),
        ...esiAffiliationFakes(100, 2001, corporation_name: 'Contact Corp'),
    ]);

    $this->actingAs($user)
        ->post(route('admin.standings.sync'))
        ->assertRedirect();

    Queue::assertPushed(SyncCharacterStandings::class, 1);
});
