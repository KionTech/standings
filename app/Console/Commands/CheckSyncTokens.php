<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTokenExpiredMail;
use App\Models\Character;
use App\Models\StandingsSource;
use App\Services\StandingsSourceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use NicolasKion\Esi\Enums\EsiScope;

#[Signature('standings:check-tokens')]
#[Description('In-game mail synced characters whose write-contacts token has expired so they re-authenticate.')]
class CheckSyncTokens extends Command
{
    public function handle(StandingsSourceService $service): int
    {
        $source = StandingsSource::current();

        if (! $source instanceof StandingsSource) {
            $this->components->info('No standings source is configured.');

            return self::SUCCESS;
        }

        $sender = $service->reader($source);

        if (! $sender instanceof Character || ! $sender->hasEsiTokenWithScope(EsiScope::SendMail)) {
            $this->components->warn('The source character cannot send mail; skipping token check.');

            return self::SUCCESS;
        }

        $queued = 0;

        Character::query()
            ->where('should_sync', true)
            ->whereDoesntHave('esiTokens.esiScopes', fn ($query) => $query->where('name', EsiScope::WriteCharacterContacts))
            ->each(function (Character $character) use ($source, $sender, &$queued): void {
                // Characters that inherit the standings don't need a personal token.
                if ($source->coversCharacter($character)) {
                    return;
                }

                // Mail each lapsed character at most once every three days.
                if (! Cache::add('standings:token-mail:'.$character->id, true, now()->addDays(3))) {
                    return;
                }

                SendTokenExpiredMail::dispatch($sender->id, $character->id);
                $queued++;
            });

        $this->components->info(sprintf('Queued %d token-expiry mail(s).', $queued));

        return self::SUCCESS;
    }
}
