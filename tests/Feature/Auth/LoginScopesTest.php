<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;
use NicolasKion\Esi\Enums\EsiScope;

it('asks for no scopes at a normal login', function () {
    $response = $this->get(route('login'));

    $response->assertRedirect();

    $location = urldecode((string) $response->headers->get('Location'));

    expect($location)->not->toContain('esi-');
});

it('shares a sync grant link asking only for the character contact scopes', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.sync_scopes_url', fn (string $url): bool => str_contains($url, 'add_to_account=1')
                && str_contains($url, 'esi-characters.read_contacts.v1')
                && str_contains($url, 'esi-characters.write_contacts.v1')
                && ! str_contains($url, 'esi-corporations.read_contacts.v1')
                && ! str_contains($url, 'esi-alliances.read_contacts.v1')
                && ! str_contains($url, 'esi-mail.send_mail.v1')));
});

it('shares the admin grant link only with admins', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.admin_scopes_url', fn (string $url): bool => str_contains($url, 'add_to_account=1')
                && str_contains($url, 'esi-corporations.read_contacts.v1')
                && str_contains($url, 'esi-alliances.read_contacts.v1')
                && str_contains($url, 'esi-mail.send_mail.v1')));

    config(['services.eveonline.admin_character_ids' => []]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.admin_scopes_url', null));
});

it('asks for exactly the scopes a grant link carries', function () {
    $grantUrl = route('login', [
        'add_to_account' => 1,
        'scopes' => 'esi-mail.send_mail.v1,esi-characters.write_contacts.v1',
    ]);

    $location = urldecode((string) $this->get($grantUrl)->assertRedirect()->headers->get('Location'));

    expect($location)->toContain('esi-mail.send_mail.v1')
        ->and($location)->toContain('esi-characters.write_contacts.v1')
        ->and($location)->not->toContain('esi-corporations.read_contacts.v1');
});

it('asks for the mail scope through the admin grant link', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user);

    $this->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('grantMailScopeUrl', fn (string $url): bool => str_contains($url, 'add_to_account=1')
                && str_contains($url, 'esi-mail.send_mail.v1')
                && str_contains($url, 'esi-characters.write_contacts.v1')));
});

it('shows whether the admin character can send expiry mail', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    $this->actingAs($user);

    $this->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('adminCharacter.has_mail_scope', false));

    grantScopes($character, EsiScope::SendMail);

    $this->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('adminCharacter.has_mail_scope', true));
});
