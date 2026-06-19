<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Character;
use App\Models\CharacterSyncedContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterSyncedContact>
 */
class CharacterSyncedContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'contact_id' => fake()->unique()->numberBetween(90_000_000, 2_200_000_000),
            'standing' => fake()->randomElement([-10, -5, 0, 5, 10]),
        ];
    }
}
