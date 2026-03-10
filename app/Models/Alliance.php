<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alliance extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'ticker',
        'creator_id',
        'creator_corporation_id',
        'faction_id',
        'date_founded',
    ];

    /**
     * @return BelongsTo<Character,$this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return BelongsTo<Corporation,$this>
     */
    public function creatorCorporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    /**
     * @return BelongsTo<Faction,$this>
     */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /**
     * @return HasMany<Corporation,$this>
     */
    public function corporations(): HasMany
    {
        return $this->hasMany(Corporation::class, 'alliance_id');
    }

    /**
     * @return HasMany<Character,$this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'alliance_id');
    }

    protected function casts(): array
    {
        return [
            'date_founded' => 'datetime',
        ];
    }
}
