<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Constellation extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'region_id',
        'type',
    ];

    /**
     * @return BelongsTo<Region,$this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return HasMany<Solarsystem,$this>
     */
    public function solarsystems(): HasMany
    {
        return $this->hasMany(Solarsystem::class, 'constellation_id');
    }

    /**
     * @return HasMany<Station,$this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'constellation_id');
    }

    /**
     * @return HasMany<Celestial,$this>
     */
    public function celestials(): HasMany
    {
        return $this->hasMany(Celestial::class, 'constellation_id');
    }
}
