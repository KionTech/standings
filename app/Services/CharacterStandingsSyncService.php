<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterSyncedContact;
use App\Models\SourceContact;
use App\Models\StandingsSource;
use NicolasKion\Esi\Enums\EsiScope;
use NicolasKion\Esi\Esi;

class CharacterStandingsSyncService
{
    /**
     * ESI allows up to 100 contact ids per add/edit call.
     */
    private const int WRITE_CHUNK = 100;

    /**
     * ESI allows up to 20 contact ids per delete call.
     */
    private const int DELETE_CHUNK = 20;

    public function __construct(private readonly Esi $esi) {}

    /**
     * Apply the canonical source standings to a single character: add new
     * contacts, update changed ones, and remove only contacts we previously
     * synced that the source has since dropped. Personal contacts are untouched.
     */
    public function sync(Character $character): void
    {
        if (! $character->should_sync || ! $character->hasEsiTokenWithScope(EsiScope::WriteCharacterContacts)) {
            return;
        }

        // Characters in the source corp/alliance already inherit the standings in-game.
        if (StandingsSource::current()?->coversCharacter($character)) {
            return;
        }

        /** @var array<int, float> $desired */
        $desired = SourceContact::query()->pluck('standing', 'contact_id')
            ->map(static fn ($standing): float => (float) $standing)
            ->all();

        $current = $this->currentContacts($character);
        /** @var array<int, float> $ledger */
        $ledger = $character->syncedContacts()->pluck('standing', 'contact_id')
            ->map(static fn ($standing): float => (float) $standing)
            ->all();

        /** @var array<string, int[]> $toAdd */
        $toAdd = [];
        /** @var array<string, int[]> $toEdit */
        $toEdit = [];

        foreach ($desired as $contact_id => $standing) {
            if (! array_key_exists($contact_id, $current)) {
                $toAdd[(string) $standing][] = $contact_id;
            } elseif (abs($current[$contact_id] - $standing) >= SourceContact::STANDING_EPSILON) {
                $toEdit[(string) $standing][] = $contact_id;
            }
        }

        $toDelete = [];

        foreach ($ledger as $contact_id => $standing) {
            if (! array_key_exists($contact_id, $desired) && array_key_exists($contact_id, $current)) {
                $toDelete[] = $contact_id;
            }
        }

        $this->add($character, $toAdd);
        $this->edit($character, $toEdit);
        $this->delete($character, $toDelete);

        $this->updateLedger($character, $desired);
    }

    /**
     * @return array<int, float> contact_id => standing
     */
    private function currentContacts(Character $character): array
    {
        if (! $character->hasEsiTokenWithScope(EsiScope::ReadCharacterContacts)) {
            return [];
        }

        $result = $this->esi->getCharacterContacts($character);

        if ($result->failed()) {
            return [];
        }

        $contacts = [];

        foreach ($result->data as $contact) {
            $contacts[$contact->contact_id] = (float) $contact->standing;
        }

        return $contacts;
    }

    /**
     * @param  array<string, int[]>  $byStanding
     */
    private function add(Character $character, array $byStanding): void
    {
        foreach ($byStanding as $standing => $contact_ids) {
            foreach (array_chunk($contact_ids, self::WRITE_CHUNK) as $chunk) {
                $this->esi->addCharacterContacts($character, $chunk, (float) $standing);
            }
        }
    }

    /**
     * @param  array<string, int[]>  $byStanding
     */
    private function edit(Character $character, array $byStanding): void
    {
        foreach ($byStanding as $standing => $contact_ids) {
            foreach (array_chunk($contact_ids, self::WRITE_CHUNK) as $chunk) {
                $this->esi->editCharacterContacts($character, $chunk, (float) $standing);
            }
        }
    }

    /**
     * @param  int[]  $contact_ids
     */
    private function delete(Character $character, array $contact_ids): void
    {
        foreach (array_chunk($contact_ids, self::DELETE_CHUNK) as $chunk) {
            $this->esi->deleteCharacterContacts($character, $chunk);
        }
    }

    /**
     * Make the ledger reflect exactly the source set we just applied.
     *
     * @param  array<int, float>  $desired
     */
    private function updateLedger(Character $character, array $desired): void
    {
        $contact_ids = array_keys($desired);

        $character->syncedContacts()
            ->whereNotIn('contact_id', $contact_ids === [] ? [0] : $contact_ids)
            ->delete();

        if ($desired === []) {
            return;
        }

        $rows = [];

        foreach ($desired as $contact_id => $standing) {
            $rows[] = [
                'character_id' => $character->id,
                'contact_id' => $contact_id,
                'standing' => $standing,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CharacterSyncedContact::query()->upsert($rows, ['character_id', 'contact_id'], ['standing', 'updated_at']);
    }
}
