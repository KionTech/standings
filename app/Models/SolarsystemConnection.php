<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarsystemConnection extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'from_stargate_id',
        'from_solarsystem_id',
        'from_constellation_id',
        'from_region_id',
        'to_stargate_id',
        'to_solarsystem_id',
        'to_constellation_id',
        'to_region_id',
        'is_regional',
    ];

    /**
     * @return BelongsTo<Solarsystem,$this>
     */
    public function fromSolarsystem(): BelongsTo
    {
        return $this->belongsTo(Solarsystem::class, 'from_solarsystem_id');
    }

    /**
     * @return BelongsTo<Solarsystem,$this>
     */
    public function toSolarsystem(): BelongsTo
    {
        return $this->belongsTo(Solarsystem::class, 'to_solarsystem_id');
    }

    /**
     * @return BelongsTo<Stargate,$this>
     */
    public function fromStargate(): BelongsTo
    {
        return $this->belongsTo(Stargate::class, 'from_stargate_id');
    }

    /**
     * @return BelongsTo<Stargate,$this>
     */
    public function toStargate(): BelongsTo
    {
        return $this->belongsTo(Stargate::class, 'to_stargate_id');
    }

    /**
     * @return BelongsTo<Constellation,$this>
     */
    public function fromConstellation(): BelongsTo
    {
        return $this->belongsTo(Constellation::class, 'from_constellation_id');
    }

    /**
     * @return BelongsTo<Constellation,$this>
     */
    public function toConstellation(): BelongsTo
    {
        return $this->belongsTo(Constellation::class, 'to_constellation_id');
    }

    /**
     * @return BelongsTo<Region,$this>
     */
    public function fromRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'from_region_id');
    }

    /**
     * @return BelongsTo<Region,$this>
     */
    public function toRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'to_region_id');
    }

    protected function casts(): array
    {
        return [
            'is_regional' => 'boolean',
        ];
    }
}
