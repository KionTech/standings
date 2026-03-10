<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StationOperation extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * @return BelongsToMany<Service,$this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'operation_services',
            'station_operation_id',
            'service_id'
        );
    }

    /**
     * @return HasMany<Station,$this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'operation_id');
    }
}
