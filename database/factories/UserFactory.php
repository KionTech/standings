<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'remember_token' => Str::random(10),
            'setup_completed_at' => now(),
        ];
    }

    /**
     * A user who has not been through the first-login setup wizard yet.
     */
    public function withoutSetup(): static
    {
        return $this->state(fn (array $attributes): array => [
            'setup_completed_at' => null,
        ]);
    }
}
