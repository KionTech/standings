<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationService extends Model
{
    protected $fillable = [
        'station_operation_id',
        'service_id',
    ];

    /**
     * @return BelongsTo<StationOperation,$this>
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(StationOperation::class, 'station_operation_id');
    }

    /**
     * @return BelongsTo<Service,$this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
