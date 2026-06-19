<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StandingRequestStatus;
use App\Enums\StandingsSourceType;
use App\Models\Character;
use App\Models\StandingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandingRequest>
 */
class StandingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => StandingsSourceType::Character,
            'requested_by_character_id' => Character::factory(),
            'subject_id' => fn (array $attributes): int => $attributes['requested_by_character_id'],
            'status' => StandingRequestStatus::Pending,
        ];
    }
}
