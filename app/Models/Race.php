<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'short_description',
        'icon_id',
    ];

    /**
     * @return HasMany<Type,$this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(Type::class, 'race_id');
    }

    /**
     * @return HasMany<Character,$this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'race_id');
    }
}
