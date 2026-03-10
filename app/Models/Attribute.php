<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'display_name',
        'description',
        'default_value',
        'unit_id',
        'icon_id',
        'high_is_good',
        'published',
        'stackable',
    ];

    /**
     * @return HasMany<TypeAttribute,$this>
     */
    public function typeAttributes(): HasMany
    {
        return $this->hasMany(TypeAttribute::class, 'attribute_id');
    }

    /**
     * @return BelongsTo<Unit,$this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Icon,$this>
     */
    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }
}
