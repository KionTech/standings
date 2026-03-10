<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EsiScope;
use Illuminate\Database\Seeder;

class EsiScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $default_scopes = [
            \NicolasKion\Esi\Enums\EsiScope::PublicData,
        ];

        foreach (\NicolasKion\Esi\Enums\EsiScope::cases() as $scope) {
            EsiScope::query()->updateOrCreate(
                ['name' => $scope],
                ['is_default' => in_array($scope, $default_scopes)],
            );
        }
    }
}
