<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'display_name',
        'description',
    ];

    /**
     * @return HasMany<Attribute, $this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'unit_id');
    }
}
