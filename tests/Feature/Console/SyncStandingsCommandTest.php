<?php

declare(strict_types=1);

use App\Jobs\NotifySourceUnreadable;
use App\Jobs\SyncCharacterStandings;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\DiscordSetting;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use NicolasKion\Esi\Enums\EsiScope;

afterEach(function () {
    Carbon::setTestNow();
});

it('refreshes the source and queues every opted-in character', function () {
    Queue::fake();

    Corporation::query()->firstOrCreate(['id' => 2000], ['name' => 'Source Corp']);
    $admin = Character::factory()->create(['corporation_id' => 2000]);
    grantScopes($admin, EsiScope::ReadCorporationContacts);
    config(['services.eveonline.admin_character_ids' => [$admin->id]]);

    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    $syncable = Character::factory()->create(['should_sync' => true]);
    grantScopes($syncable, EsiScope::WriteCharacterContacts);

    // Opted out / missing scope characters must be ignored.
    Character::factory()->create(['should_sync' => false]);
    $noScope = Character::factory()->create(['should_sync' => true]);
    grantScopes($noScope, EsiScope::ReadCharacterContacts);

    // A character inside the source corporation already inherits and must be skipped.
    $inheriting = Character::factory()->create(['should_sync' => true, 'corporation_id' => 2000]);
    grantScopes($inheriting, EsiScope::WriteCharacterContacts);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
        'esi.evetech.net/universe/names/*' => Http::response([
            ['category' => 'character', 'id' => 100, 'name' => 'Contact One'],
        ], 200),
    ]);

    $this->artisan('standings:sync')->assertSuccessful();

    Queue::assertPushed(SyncCharacterStandings::class, 1);
    Queue::assertPushed(SyncCharacterStandings::class, fn (SyncCharacterStandings $job): bool => $job->character->is($syncable));
});

it('does not queue any syncs when the source is unchanged', function () {
    Queue::fake();

    Corporation::query()->firstOrCreate(['id' => 2000], ['name' => 'Source Corp']);
    $admin = Character::factory()->create(['corporation_id' => 2000]);
    grantScopes($admin, EsiScope::ReadCorporationContacts);
    config(['services.eveonline.admin_character_ids' => [$admin->id]]);

    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create(['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5]);

    $syncable = Character::factory()->create(['should_sync' => true]);
    grantScopes($syncable, EsiScope::WriteCharacterContacts);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
    ]);

    $this->artisan('standings:sync')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('queues syncs for unchanged standings when forced', function () {
    Queue::fake();

    Corporation::query()->firstOrCreate(['id' => 2000], ['name' => 'Source Corp']);
    $admin = Character::factory()->create(['corporation_id' => 2000]);
    grantScopes($admin, EsiScope::ReadCorporationContacts);
    config(['services.eveonline.admin_character_ids' => [$admin->id]]);

    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    SourceContact::factory()->create(['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5]);

    $syncable = Character::factory()->create(['should_sync' => true]);
    grantScopes($syncable, EsiScope::WriteCharacterContacts);

    Http::fake([
        'esi.evetech.net/corporations/2000/contacts/*' => Http::response([
            ['contact_id' => 100, 'contact_type' => 'character', 'standing' => 5, 'label_ids' => []],
        ], 200),
    ]);

    $this->artisan('standings:sync', ['--force' => true])->assertSuccessful();

    Queue::assertPushed(SyncCharacterStandings::class, 1);
});

it('skips the sync during eve downtime without calling esi or alerting', function () {
    Queue::fake();
    Http::fake();

    // 11:05 UTC is inside EVE's daily downtime window.
    Carbon::setTestNow(Carbon::parse('2026-06-25 11:05:00', 'UTC'));

    // A source that cannot be read would normally fail and alert Discord.
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    config(['services.eveonline.admin_character_ids' => []]);

    $this->artisan('standings:sync')->assertSuccessful();

    Http::assertNothingSent();
    Queue::assertNothingPushed();
});

it('fails when no source is configured', function () {
    Queue::fake();

    $this->artisan('standings:sync')->assertFailed();

    Queue::assertNothingPushed();
});

it('alerts discord at most once per hour when the source cannot be read', function () {
    Queue::fake();

    // Source configured, but no admin character can read it.
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    config(['services.eveonline.admin_character_ids' => []]);

    $this->artisan('standings:sync')->assertFailed();
    Queue::assertPushed(NotifySourceUnreadable::class, 1);

    // Throttled: a second run within the same hour does not re-alert.
    $this->artisan('standings:sync')->assertFailed();
    Queue::assertPushed(NotifySourceUnreadable::class, 1);
});

it('does not alert discord when no source is configured', function () {
    Queue::fake();

    $this->artisan('standings:sync')->assertFailed();

    Queue::assertNotPushed(NotifySourceUnreadable::class);
});

it('posts a source-unreadable alert to the discord webhook', function () {
    DiscordSetting::query()->create(['webhook_url' => 'https://discord.test/webhook', 'role_id' => '777']);
    Corporation::query()->create(['id' => 2000, 'name' => 'Source Corp']);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    Http::fake();

    (new NotifySourceUnreadable)->handle();

    Http::assertSent(function ($request) {
        $body = json_encode($request->data());

        return $request->url() === 'https://discord.test/webhook'
            && str_contains($body, 'Source Corp')
            && str_contains($body, '<@&777>')
            && str_contains($body, 'Click here to re-authenticate');
    });
});
