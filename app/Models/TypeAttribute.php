<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeAttribute extends Model
{
    protected $fillable = [
        'id',
        'type_id',
        'attribute_id',
        'value',
    ];

    /**
     * @return BelongsTo<Type,$this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * @return BelongsTo<Attribute,$this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }
}
