<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icon extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'file',
        'description',
    ];

    /**
     * @return HasMany<Type,$this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(Type::class, 'icon_id');
    }

    /**
     * @return HasMany<Attribute,$this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'icon_id');
    }

    /**
     * @return HasMany<MarketGroup,$this>
     */
    public function marketGroups(): HasMany
    {
        return $this->hasMany(MarketGroup::class, 'icon_id');
    }

    /**
     * @return HasMany<MetaGroup,$this>
     */
    public function metaGroups(): HasMany
    {
        return $this->hasMany(MetaGroup::class, 'icon_id');
    }
}
