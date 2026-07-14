<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\User;

function settingsAdmin(): User
{
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [$character->id]]);

    return $user;
}

it('shows the settings page with source options and discord settings', function () {
    $user = settingsAdmin();

    $this->actingAs($user)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Settings')
            ->has('sourceTypes', 3)
            ->has('discordSettings')
            ->has('adminCharacter'));
});

it('forbids non-admins from the settings page', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();
    config(['services.eveonline.admin_character_ids' => [999999]]);

    $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
});
