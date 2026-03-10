<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeEffect extends Model
{
    public $incrementing = false;

    protected $table = 'type_effects';

    protected $fillable = [
        'id',
        'type_id',
        'effect_id',
        'is_default',
    ];
}
