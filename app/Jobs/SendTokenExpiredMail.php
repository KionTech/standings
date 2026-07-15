<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Character;
use App\Support\EveSso;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NicolasKion\Esi\DTO\EveMailRecipient;
use NicolasKion\Esi\Enums\EsiScope;
use NicolasKion\Esi\Enums\RecipientType;
use NicolasKion\Esi\Esi;

class SendTokenExpiredMail implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $senderId, public int $recipientId) {}

    public function handle(Esi $esi): void
    {
        $sender = Character::query()->find($this->senderId);
        $recipient = Character::query()->find($this->recipientId);

        if ($sender === null || $recipient === null || ! $sender->hasEsiTokenWithScope(EsiScope::SendMail)) {
            return;
        }

        $body = sprintf(
            "Hello %s,\n\nYour contact permissions for Bluebook have lapsed, so your standings can't be synced. Please grant them again here:\n%s\n\nso the service won't get interrupted.",
            $recipient->name,
            EveSso::grantScopesUrl('services.eveonline.sync_scopes'),
        );

        $esi->sendMail(
            $sender,
            [new EveMailRecipient($recipient->id, RecipientType::Character)],
            'Bluebook — re-authentication required',
            $body,
        );
    }
}
