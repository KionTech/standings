<?php

declare(strict_types=1);

use App\Jobs\SendTokenExpiredMail;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\StandingsSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use NicolasKion\Esi\Enums\EsiScope;
use NicolasKion\Esi\Esi;

/**
 * A corporation source whose sender character can read it and send mail.
 */
function tokenCheckSetup(): Character
{
    Corporation::query()->firstOrCreate(['id' => 2000], ['name' => 'Source Corp']);
    $sender = Character::factory()->create(['corporation_id' => 2000]);
    grantScopes($sender, EsiScope::ReadCorporationContacts, EsiScope::SendMail);
    config(['services.eveonline.admin_character_ids' => [$sender->id]]);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);

    return $sender;
}

it('mails synced characters whose write-contacts token expired', function () {
    Queue::fake();
    $sender = tokenCheckSetup();
    $lapsed = Character::factory()->create(['name' => 'Lapsed', 'should_sync' => true]);

    $this->artisan('standings:check-tokens')->assertSuccessful();

    Queue::assertPushed(SendTokenExpiredMail::class, 1);
    Queue::assertPushed(SendTokenExpiredMail::class, fn (SendTokenExpiredMail $job): bool => $job->recipientId === $lapsed->id && $job->senderId === $sender->id);
});

it('does not mail characters that still have a token', function () {
    Queue::fake();
    tokenCheckSetup();
    $ok = Character::factory()->create(['should_sync' => true]);
    grantScopes($ok, EsiScope::WriteCharacterContacts);

    $this->artisan('standings:check-tokens')->assertSuccessful();

    Queue::assertNotPushed(SendTokenExpiredMail::class);
});

it('does not mail characters that inherit the source', function () {
    Queue::fake();
    tokenCheckSetup();
    Character::factory()->create(['should_sync' => true, 'corporation_id' => 2000]);

    $this->artisan('standings:check-tokens')->assertSuccessful();

    Queue::assertNotPushed(SendTokenExpiredMail::class);
});

it('mails each lapsed character at most once', function () {
    Queue::fake();
    tokenCheckSetup();
    Character::factory()->create(['should_sync' => true]);

    $this->artisan('standings:check-tokens');
    $this->artisan('standings:check-tokens');

    Queue::assertPushed(SendTokenExpiredMail::class, 1);
});

it('does nothing when the source character cannot send mail', function () {
    Queue::fake();
    Corporation::query()->firstOrCreate(['id' => 2000], ['name' => 'Source Corp']);
    $sender = Character::factory()->create(['corporation_id' => 2000]);
    grantScopes($sender, EsiScope::ReadCorporationContacts);
    config(['services.eveonline.admin_character_ids' => [$sender->id]]);
    StandingsSource::create(['type' => 'corporation', 'entity_id' => 2000]);
    Character::factory()->create(['should_sync' => true]);

    $this->artisan('standings:check-tokens')->assertSuccessful();

    Queue::assertNotPushed(SendTokenExpiredMail::class);
});

it('sends an eve mail from the source character to the lapsed character', function () {
    $sender = Character::factory()->create();
    grantScopes($sender, EsiScope::SendMail);
    $recipient = Character::factory()->create(['name' => 'Lapsed Pilot']);
    Http::fake(['esi.evetech.net/characters/*/mail/*' => Http::response('987654', 200, ['Content-Type' => 'application/json'])]);

    (new SendTokenExpiredMail($sender->id, $recipient->id))->handle(app(Esi::class));

    Http::assertSent(function ($request) use ($sender, $recipient) {
        $body = json_encode($request->data());

        return $request->method() === 'POST'
            && str_contains($request->url(), "/characters/{$sender->id}/mail/")
            && str_contains($body, 'Lapsed Pilot')
            && str_contains($body, (string) $recipient->id);
    });
});
