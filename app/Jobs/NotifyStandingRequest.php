<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StandingsSourceType;
use App\Models\DiscordSetting;
use App\Models\StandingRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class NotifyStandingRequest implements ShouldQueue
{
    use Queueable;

    public function __construct(public StandingRequest $standingRequest) {}

    public function handle(): void
    {
        $webhook = DiscordSetting::webhookUrl();

        if (! $webhook) {
            return;
        }

        $settings = DiscordSetting::current();

        $standingRequest = $this->standingRequest->load([
            'character:id,name,corporation_id,alliance_id',
            'character.corporation:id,name',
            'character.alliance:id,name',
        ]);

        $subjectName = $standingRequest->subjectName() ?? (string) $standingRequest->subject_id;
        $ping = $settings->role_id ? sprintf('<@&%s> ', $settings->role_id) : '';

        $payload = [
            'content' => sprintf('%sA %s requested standings.', $ping, $standingRequest->subject_type->value),
            'embeds' => [[
                'title' => $subjectName,
                // The name in a code block so it can be copied with one click in Discord.
                'description' => sprintf("Requested by %s. Review it in the administration page.\n```\n%s\n```", $standingRequest->character->name, $subjectName),
                'url' => route('admin.index'),
                'thumbnail' => ['url' => $this->subjectImageUrl()],
                'fields' => [
                    ['name' => 'Type', 'value' => $standingRequest->subject_type->label(), 'inline' => true],
                    ['name' => 'Requested by', 'value' => $standingRequest->character->name, 'inline' => true],
                ],
            ]],
        ];

        if ($settings->role_id) {
            $payload['allowed_mentions'] = ['roles' => [$settings->role_id]];
        }

        Http::post($webhook, $payload);
    }

    /**
     * The EVE image server URL for the requested entity's logo or portrait.
     */
    private function subjectImageUrl(): string
    {
        $id = $this->standingRequest->subject_id;

        return match ($this->standingRequest->subject_type) {
            StandingsSourceType::Character => sprintf('https://images.evetech.net/characters/%d/portrait?size=128', $id),
            StandingsSourceType::Corporation => sprintf('https://images.evetech.net/corporations/%d/logo?size=128', $id),
            StandingsSourceType::Alliance => sprintf('https://images.evetech.net/alliances/%d/logo?size=128', $id),
        };
    }
}
