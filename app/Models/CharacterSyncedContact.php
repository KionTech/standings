<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $character_id
 * @property int $contact_id
 * @property float $standing
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Character $character
 */
class CharacterSyncedContact extends Model
{
    /** @use HasFactory<\Database\Factories\CharacterSyncedContactFactory> */
    use HasFactory;

    protected $fillable = [
        'character_id',
        'contact_id',
        'standing',
    ];

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'character_id' => 'integer',
            'contact_id' => 'integer',
            'standing' => 'float',
        ];
    }
}
