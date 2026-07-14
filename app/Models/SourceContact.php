<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StandingsSourceType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use NicolasKion\Esi\Enums\ContactType;

/**
 * @property int $id
 * @property int $contact_id
 * @property ContactType $contact_type
 * @property float $standing
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read string|null $name
 * @property-read Character|Corporation|Alliance|Faction|null $entity
 * @property-read array{contact_type: string, contact_id: int, name: string|null}|null $redundant_via Set by controllers before serialization when a parent entity carries the same standing.
 */
class SourceContact extends Model
{
    /** @use HasFactory<\Database\Factories\SourceContactFactory> */
    use HasFactory;

    /**
     * Standings are considered equal within this tolerance.
     */
    public const float STANDING_EPSILON = 0.01;

    protected $fillable = [
        'contact_id',
        'contact_type',
        'standing',
    ];

    /**
     * The "type:id" key entities are looked up by across the app.
     */
    public static function keyFor(ContactType|StandingsSourceType $type, int $id): string
    {
        return $type->value.':'.$id;
    }

    /**
     * The locally known entity this contact points at, resolved through the
     * character/corporation/alliance/faction morph map.
     *
     * @return MorphTo<Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'contact_type', 'contact_id');
    }

    /**
     * This contact's "type:id" lookup key.
     */
    public function key(): string
    {
        return self::keyFor($this->contact_type, $this->contact_id);
    }

    /**
     * The contact's display name, resolved from the locally stored entity.
     */
    public function getNameAttribute(): ?string
    {
        return $this->entity?->name;
    }

    /**
     * Whether this contact's standing makes the given contact's standing
     * redundant: an equal standing always does, and a stronger standing in the
     * same direction does too (a +10 parent covers a +5 child, a -10 parent
     * covers a -5 child). A weaker or opposite parent standing means the other
     * contact is a deliberate override.
     */
    public function coversStanding(self $other): bool
    {
        if (abs($this->standing - $other->standing) < self::STANDING_EPSILON) {
            return true;
        }

        if ($other->standing > 0) {
            return $this->standing > $other->standing;
        }

        if ($other->standing < 0) {
            return $this->standing < $other->standing;
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_id' => 'integer',
            'contact_type' => ContactType::class,
            'standing' => 'float',
        ];
    }
}
