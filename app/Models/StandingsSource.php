<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StandingsSourceType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property StandingsSourceType $type
 * @property int $entity_id
 * @property CarbonImmutable|null $last_synced_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class StandingsSource extends Model
{
    protected $fillable = [
        'type',
        'entity_id',
        'last_synced_at',
    ];

    /**
     * The configured standings source, if one has been selected.
     */
    public static function current(): ?self
    {
        return self::query()->first();
    }

    /**
     * Whether a character already inherits this source's standings in-game (because
     * it is a member of the source corporation or alliance), making a personal sync
     * redundant. Personal (character) sources are never inherited.
     */
    public function coversCharacter(Character $character): bool
    {
        return match ($this->type) {
            StandingsSourceType::Corporation => $character->corporation_id === $this->entity_id,
            StandingsSourceType::Alliance => $character->alliance_id === $this->entity_id,
            StandingsSourceType::Character => false,
        };
    }

    /**
     * Whether the source has no standing toward a given entity yet (so it could be
     * requested). The source entity itself never needs a standing.
     */
    public function requiresStandingForEntity(StandingsSourceType $type, int $id): bool
    {
        if ($type === $this->type && $id === $this->entity_id) {
            return false;
        }

        return ! SourceContact::query()
            ->where('contact_type', $type->value)
            ->where('contact_id', $id)
            ->exists();
    }

    /**
     * The display name of the source entity, resolved from the local tables.
     */
    public function entityName(): ?string
    {
        return match ($this->type) {
            StandingsSourceType::Character => Character::query()->whereKey($this->entity_id)->value('name'),
            StandingsSourceType::Corporation => Corporation::query()->whereKey($this->entity_id)->value('name'),
            StandingsSourceType::Alliance => Alliance::query()->whereKey($this->entity_id)->value('name'),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StandingsSourceType::class,
            'entity_id' => 'integer',
            'last_synced_at' => 'immutable_datetime',
        ];
    }
}
