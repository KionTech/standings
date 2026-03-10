<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Celestial extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'name',
        'solarsystem_id',
        'constellation_id',
        'region_id',
        'parent_id',
        'type_id',
        'group_id',
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
     * @return BelongsTo<Type,$this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * @return BelongsTo<Group,$this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<Celestial,$this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return HasMany<Station,$this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'parent_id');
    }
}
