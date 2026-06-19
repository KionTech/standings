<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DiscordSetting;
use App\Models\StandingsSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class NotifySourceUnreadable implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $webhook = DiscordSetting::webhookUrl();

        if (! $webhook) {
            return;
        }

        $settings = DiscordSetting::current();
        $source = StandingsSource::current();
        $name = $source?->entityName() ?? ($source ? (string) $source->entity_id : 'the source');
        $ping = $settings->role_id ? sprintf('<@&%s> ', $settings->role_id) : '';
        $login = route('login');

        $payload = [
            'content' => sprintf('%s:warning: The standings source could not be read.', $ping),
            'embeds' => [[
                'title' => 'Standings source unreadable',
                'description' => sprintf(
                    "Couldn't read **%s**'s contacts. An admin character's ESI token may be missing or lacking permissions.\n\n**[Click here to re-authenticate](%s)**",
                    $name,
                    $login,
                ),
                'url' => $login,
            ]],
        ];

        if ($settings->role_id) {
            $payload['allowed_mentions'] = ['roles' => [$settings->role_id]];
        }

        Http::post($webhook, $payload);
    }
}
