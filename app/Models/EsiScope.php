<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property \NicolasKion\Esi\Enums\EsiScope $name
 * @property bool $is_default
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read EloquentCollection<int, EsiToken> $esiTokens
 */
class EsiScope extends Model
{
    /** @use HasFactory<\Database\Factories\EsiScopeFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<EsiToken, $this>
     */
    public function esiTokens(): BelongsToMany
    {
        return $this->belongsToMany(EsiToken::class, 'esi_token_scope');
    }

    protected function casts(): array
    {
        return [
            'name' => \NicolasKion\Esi\Enums\EsiScope::class,
            'is_default' => 'boolean',
        ];
    }
}
