<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use NicolasKion\Esi\Enums\EsiScope;
use NicolasKion\Esi\Interfaces\EsiToken as EsiTokenInterface;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property int|null $race_id
 * @property int|null $bloodline_id
 * @property int|null $corporation_id
 * @property int|null $faction_id
 * @property int|null $alliance_id
 * @property float|null $security_status
 * @property string|null $gender
 * @property CarbonImmutable|null $birthday
 * @property string|null $title
 * @property int|null $user_id
 * @property string|null $character_owner_hash
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read EloquentCollection<int, EsiToken> $esiTokens
 * @property-read User|null $user
 */
class Character extends Model implements \NicolasKion\Esi\Interfaces\Character
{
    /** @use HasFactory<\Database\Factories\CharacterFactory> */
    use HasFactory;

    public $incrementing = false;

    /**
     * @param  int[]  $ids
     */
    public static function createFromIds(array $ids): void
    {
        DB::transaction(fn () => self::query()->upsert(
            collect($ids)->map(fn ($id) => ['id' => $id])->toArray(),
            ['id']
        ), 5);
    }

    public function getEsiTokenWithScope(EsiScope $scope): ?EsiTokenInterface
    {
        return $this->esiTokens()
            ->whereHas('esiScopes', static fn ($query) => $query->where('name', $scope))
            ->latest()
            ->first();
    }

    public function hasEsiTokenWithScope(EsiScope $scope): bool
    {
        return $this->esiTokens()
            ->whereHas('esiScopes', static fn ($query) => $query->where('name', $scope))
            ->exists();
    }

    /**
     * @return HasMany<EsiToken, $this>
     */
    public function esiTokens(): HasMany
    {
        return $this->hasMany(EsiToken::class, 'character_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * @return BelongsTo<Bloodline, $this>
     */
    public function bloodline(): BelongsTo
    {
        return $this->belongsTo(Bloodline::class);
    }

    /**
     * @return BelongsTo<Corporation, $this>
     */
    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    /**
     * @return BelongsTo<Faction, $this>
     */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /**
     * @return BelongsTo<Alliance, $this>
     */
    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCorporationId(): int
    {
        return $this->corporation_id;
    }
}
