<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $webhook_url
 * @property string|null $role_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class DiscordSetting extends Model
{
    protected $fillable = [
        'webhook_url',
        'role_id',
    ];

    /**
     * The single Discord settings row, creating an empty one if needed.
     */
    public static function current(): self
    {
        return self::query()->firstOrNew();
    }

    /**
     * The configured webhook URL, falling back to the env default.
     */
    public static function webhookUrl(): ?string
    {
        return self::current()->webhook_url ?: config('services.discord.standing_request_webhook');
    }
}
