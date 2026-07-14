<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\SourceContact;
use App\Services\EveEntityService;
use App\Support\EveDowntime;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use NicolasKion\Esi\Enums\ContactType;

#[Signature('characters:sync-affiliations')]
#[Description('Refresh the corporation/alliance affiliations of user characters and source contacts so redundancy hints stay accurate.')]
class SyncCharacterAffiliations extends Command
{
    public function handle(EveEntityService $entities): int
    {
        // ESI is unavailable during EVE's daily downtime; skip the run.
        if (EveDowntime::isActive()) {
            $this->components->info('Skipping affiliation sync during EVE downtime.');

            return self::SUCCESS;
        }

        $contact_ids = SourceContact::query()
            ->get(['contact_id', 'contact_type'])
            ->groupBy(fn (SourceContact $contact): string => $contact->contact_type->value)
            ->map(fn ($contacts) => $contacts->pluck('contact_id'));

        $character_ids = Character::query()
            ->whereNotNull('user_id')
            ->pluck('id')
            ->merge($contact_ids->get(ContactType::Character->value, collect()))
            ->unique()
            ->values();

        $corporation_ids = $contact_ids->get(ContactType::Corporation->value, collect());

        $synced = $entities->refreshEntities(
            $character_ids->all(),
            $corporation_ids->all(),
            $contact_ids->get(ContactType::Alliance->value, collect())->all(),
            refresh_corporations: true,
        );

        $this->components->info(sprintf(
            'Refreshed affiliations for %d character(s) and %d corporation(s).',
            $synced,
            $corporation_ids->count(),
        ));

        return self::SUCCESS;
    }
}
