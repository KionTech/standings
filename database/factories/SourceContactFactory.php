<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SourceContact;
use Illuminate\Database\Eloquent\Factories\Factory;
use NicolasKion\Esi\Enums\ContactType;

/**
 * @extends Factory<SourceContact>
 */
class SourceContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => fake()->unique()->numberBetween(90_000_000, 2_200_000_000),
            'contact_type' => fake()->randomElement(ContactType::cases()),
            'standing' => fake()->randomElement([-10, -5, 0, 5, 10]),
        ];
    }
}
