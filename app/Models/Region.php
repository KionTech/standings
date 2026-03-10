<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'type',
    ];

    /**
     * @return HasMany<Constellation,$this>
     */
    public function constellations(): HasMany
    {
        return $this->hasMany(Constellation::class, 'region_id');
    }

    /**
     * @return HasMany<Solarsystem,$this>
     */
    public function solarsystems(): HasMany
    {
        return $this->hasMany(Solarsystem::class, 'region_id');
    }

    /**
     * @return HasMany<Celestial,$this>
     */
    public function celestials(): HasMany
    {
        return $this->hasMany(Celestial::class, 'region_id');
    }

    /**
     * @return HasMany<Station,$this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'region_id');
    }
}
