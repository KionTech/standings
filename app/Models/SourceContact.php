<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NicolasKion\Esi\Enums\ContactType;

/**
 * @property int $id
 * @property int $contact_id
 * @property ContactType $contact_type
 * @property string|null $name
 * @property float $standing
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class SourceContact extends Model
{
    /** @use HasFactory<\Database\Factories\SourceContactFactory> */
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'contact_type',
        'name',
        'standing',
    ];

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
