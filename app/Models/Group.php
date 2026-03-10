<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'category_id',
        'icon_id',
        'published',
        'use_base_price',
        'anchored',
        'anchorable',
        'fittable_non_singleton',
    ];

    /**
     * @return BelongsTo<Icon,$this>
     */
    public function icon(): BelongsTo
    {
        return $this->belongsTo(Icon::class);
    }

    /**
     * @return BelongsTo<Category,$this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Type,$this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(Type::class, 'group_id');
    }
}
