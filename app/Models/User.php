<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property int|null $main_character_id
 * @property string $remember_token
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read EloquentCollection<int, Character> $characters
 * @property-read Character|null $mainCharacter
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const string SESSION_ACTIVE_CHARACTER_ID = 'active_character_id';

    protected $hidden = [
        'remember_token',
    ];

    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * @return HasMany<Character, $this>
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function mainCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'main_character_id');
    }

    public function getActiveCharacter(): Character
    {
        $active_character_id = Session::get(self::SESSION_ACTIVE_CHARACTER_ID);

        if ($active_character_id !== null && $character = $this->characters->find($active_character_id)) {
            return $character;
        }

        $character = $this->characters->first();

        if (! $character) {
            auth()->logout();
            abort(403, 'No characters found. Please log in again.');
        }

        Session::put(self::SESSION_ACTIVE_CHARACTER_ID, $character->id);

        return $character;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setActiveCharacter(int|Character|null $character): ?Character
    {
        if ($character === null) {
            Session::forget(self::SESSION_ACTIVE_CHARACTER_ID);

            return null;
        }

        $active_character = $character instanceof Character ? $character : $this->characters()->find($character);

        if (! $active_character) {
            throw new InvalidArgumentException('Character does not belong to this user!');
        }

        Session::put(self::SESSION_ACTIVE_CHARACTER_ID, $active_character->id);

        return $active_character;
    }

    /**
     * @return array<int>
     */
    public function getCharacterIds(): array
    {
        return once(fn () => $this->characters->pluck('id')->toArray());
    }

    /**
     * Whether this user owns one of the configured admin characters.
     */
    public function isStandingsAdmin(): bool
    {
        $admin_character_ids = config('services.eveonline.admin_character_ids', []);

        return array_intersect($admin_character_ids, $this->getCharacterIds()) !== [];
    }

}
