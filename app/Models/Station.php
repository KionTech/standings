<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Station extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'solarsystem_id',
        'constellation_id',
        'region_id',
        'parent_id',
        'type_id',
        'group_id',
        'operation_id',
        'owner_id',
    ];

    /**
     * @return BelongsTo<Solarsystem,$this>
     */
    public function solarsystem(): BelongsTo
    {
        return $this->belongsTo(Solarsystem::class);
    }

    /**
     * @return BelongsTo<Constellation,$this>
     */
    public function constellation(): BelongsTo
    {
        return $this->belongsTo(Constellation::class);
    }

    /**
     * @return BelongsTo<Celestial,$this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Celestial::class);
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
     * @return BelongsTo<Region,$this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return BelongsTo<StationOperation,$this>
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(StationOperation::class, 'operation_id');
    }

    /**
     * @return BelongsTo<Corporation,$this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Corporation::class, 'owner_id');
    }
}
