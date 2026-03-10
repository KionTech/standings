<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Graphic extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sof_faction_name',
        'file',
        'sof_hull_name',
        'sof_race_name',
        'description',
    ];

    /**
     * @return HasMany<Type,$this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(Type::class, 'graphic_id');
    }
}
