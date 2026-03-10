<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $character_id
 * @property string $access_token
 * @property string $token_type
 * @property string $refresh_token
 * @property string $character_owner_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Character $character
 * @property-read EloquentCollection<int, EsiScope> $esiScopes
 */
class EsiToken extends Model implements \NicolasKion\Esi\Interfaces\EsiToken
{
    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsToMany<EsiScope, $this>
     */
    public function esiScopes(): BelongsToMany
    {
        return $this->belongsToMany(EsiScope::class, 'esi_token_scope');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }

    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
