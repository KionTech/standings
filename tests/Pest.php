<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Give a character an ESI token carrying the given scopes.
 */
function grantScopes(App\Models\Character $character, NicolasKion\Esi\Enums\EsiScope ...$scopes): App\Models\EsiToken
{
    $token = App\Models\EsiToken::factory()->for($character)->create();

    foreach ($scopes as $scope) {
        $model = App\Models\EsiScope::query()->firstOrCreate(['name' => $scope]);
        $token->esiScopes()->attach($model);
    }

    return $token;
}

/**
 * A minimal ESI public-corporation payload for Http::fake.
 *
 * @return array<string, mixed>
 */
function esiCorporationPayload(string $name, ?int $alliance_id = null): array
{
    return [
        'name' => $name,
        'ticker' => 'CORP',
        'ceo_id' => 1,
        'creator_id' => 1,
        'date_founded' => '2010-01-01T00:00:00Z',
        'description' => '',
        'member_count' => 10,
        'tax_rate' => 0.1,
        'url' => '',
        'alliance_id' => $alliance_id,
    ];
}

/**
 * A minimal ESI public-alliance payload for Http::fake.
 *
 * @return array<string, mixed>
 */
function esiAlliancePayload(string $name): array
{
    return [
        'name' => $name,
        'ticker' => 'ALLY',
        'creator_corporation_id' => 1,
        'creator_id' => 1,
        'date_founded' => '2010-01-01T00:00:00Z',
    ];
}

/**
 * Http::fake entries resolving one character contact's affiliation plus the
 * public details of its corporation, to spread into a test's Http::fake array.
 *
 * @return array<string, mixed>
 */
function esiAffiliationFakes(int $character_id, int $corporation_id, ?int $alliance_id = null, string $corporation_name = 'Member Corp'): array
{
    $affiliation = ['character_id' => $character_id, 'corporation_id' => $corporation_id];

    if ($alliance_id !== null) {
        $affiliation['alliance_id'] = $alliance_id;
    }

    return [
        'esi.evetech.net/characters/affiliation/*' => Illuminate\Support\Facades\Http::response([$affiliation], 200),
        "esi.evetech.net/corporations/{$corporation_id}/*" => Illuminate\Support\Facades\Http::response(esiCorporationPayload($corporation_name, $alliance_id), 200),
    ];
}
