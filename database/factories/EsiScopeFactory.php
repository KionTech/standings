<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EsiScope;
use Illuminate\Database\Eloquent\Factories\Factory;
use NicolasKion\Esi\Enums\EsiScope as EsiScopeEnum;

/**
 * @extends Factory<EsiScope>
 */
class EsiScopeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(EsiScopeEnum::cases()),
            'is_default' => false,
        ];
    }

    public function named(EsiScopeEnum $scope): static
    {
        return $this->state(fn (): array => [
            'name' => $scope,
        ]);
    }
}
