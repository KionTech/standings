<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * @return BelongsToMany<StationOperation,$this>
     */
    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(
            StationOperation::class,
            'operation_services',
            'service_id',
            'station_operation_id'
        );
    }
}
