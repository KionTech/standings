<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(90_000_000, 2_200_000_000),
            'name' => fake()->name(),
            'user_id' => User::factory(),
            'character_owner_hash' => fake()->sha256(),
        ];
    }
}
