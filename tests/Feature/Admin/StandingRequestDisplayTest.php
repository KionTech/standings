<?php

declare(strict_types=1);

use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('passes the inherited effective standing for each request to the admin console', function () {
    $user = User::factory()->create();
    Corporation::query()->create(['id' => 4000, 'name' => 'Blue Corp']);
    $admin = Character::factory()->for($user)->create(['corporation_id' => 4000]);
    config(['services.eveonline.admin_character_ids' => [$admin->id]]);

    StandingsSource::create(['type' => 'character', 'entity_id' => 999]);
    SourceContact::factory()->create(['contact_type' => 'corporation', 'contact_id' => 4000, 'standing' => 5, 'name' => 'Blue Corp']);

    StandingRequest::factory()->create([
        'subject_type' => 'character',
        'subject_id' => $admin->id,
        'requested_by_character_id' => $admin->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('standingRequests.0.effective_standing.source', 'corporation')
            ->where('standingRequests.0.effective_standing.standing', 5)
            ->where('standingRequests.0.effective_standing.via_name', 'Blue Corp')
        );
});
