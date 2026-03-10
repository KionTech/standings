<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solarsystem extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'constellation_id',
        'region_id',
        'security',
        'pos_x',
        'pos_y',
        'pos_z',
        'type',
        'has_jove_observatory',
    ];

    /**
     * @return BelongsTo<Region,$this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo<Constellation,$this>
     */
    public function constellation(): BelongsTo
    {
        return $this->belongsTo(Constellation::class);
    }

    /**
     * @return HasMany<Celestial,$this>
     */
    public function celestials(): HasMany
    {
        return $this->hasMany(Celestial::class, 'solarsystem_id');
    }

    /**
     * @return HasMany<Station,$this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'solarsystem_id');
    }
}
