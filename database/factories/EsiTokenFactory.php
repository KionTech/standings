<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Character;
use App\Models\EsiToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsiToken>
 */
class EsiTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'access_token' => fake()->sha256(),
            'token_type' => 'Bearer',
            'refresh_token' => fake()->sha256(),
            'character_owner_hash' => fake()->sha256(),
            'expires_at' => now()->addHour(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
