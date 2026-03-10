<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bloodline extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'race_id',
        'ship_type_id',
        'willpower',
        'perception',
        'charisma',
        'intelligence',
        'memory',
    ];

    /**
     * @return BelongsTo<Race,$this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * @return BelongsTo<Type,$this>
     */
    public function shipType(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * @return HasMany<Character,$this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'bloodline_id');
    }
}
