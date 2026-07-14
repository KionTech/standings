<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StandingRequestStatus;
use App\Enums\StandingsSourceType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property StandingsSourceType $subject_type
 * @property int $subject_id
 * @property int $requested_by_character_id
 * @property StandingRequestStatus $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Character $character
 * @property-read array{standing: float, source: string, via_type: string, via_id: int, via_name: string|null}|null $effective_standing Set via EffectiveStandingResolver before serialization.
 */
class StandingRequest extends Model
{
    /** @use HasFactory<\Database\Factories\StandingRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'requested_by_character_id',
        'status',
    ];

    /**
     * The character that submitted the request.
     *
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'requested_by_character_id');
    }

    /**
     * The name of the entity the standing is requested for, resolved from the
     * requesting character's loaded affiliations.
     */
    public function subjectName(): ?string
    {
        return match ($this->subject_type) {
            StandingsSourceType::Character => $this->character->name,
            StandingsSourceType::Corporation => $this->character->corporation?->name,
            StandingsSourceType::Alliance => $this->character->alliance?->name,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_type' => StandingsSourceType::class,
            'status' => StandingRequestStatus::class,
        ];
    }
}
