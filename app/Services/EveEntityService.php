<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use NicolasKion\Esi\DTO\CharacterAffiliation;
use NicolasKion\Esi\Esi;

/**
 * Creates and refreshes the locally known EVE entities (characters,
 * corporations, alliances) from ESI's public endpoints.
 */
readonly class EveEntityService
{
    public function __construct(private Esi $esi) {}

    /**
     * Make sure a character affiliation's parent entities exist locally.
     */
    public function ensureAffiliationsExist(CharacterAffiliation $affiliation): void
    {
        if ($affiliation->alliance_id) {
            $this->ensureAlliance($affiliation->alliance_id);
        }

        $this->ensureCorporation($affiliation->corporation_id);
    }

    /**
     * Refresh the locally stored entities behind the given ids: characters get
     * their affiliations (and names) synced in bulk, and alliances and
     * corporations are created when first seen. Pass $refresh_corporations to
     * also re-fetch known corporations so their alliance membership stays
     * current. Returns the number of characters whose affiliation was stored.
     *
     * @param  int[]  $character_ids
     * @param  int[]  $corporation_ids
     * @param  int[]  $alliance_ids
     * @param  array<int, string>  $names  Known character names, stored alongside the affiliation.
     */
    public function refreshEntities(
        array $character_ids,
        array $corporation_ids,
        array $alliance_ids,
        array $names = [],
        bool $refresh_corporations = false,
    ): int {
        foreach ($corporation_ids as $id) {
            if ($refresh_corporations) {
                $this->refreshCorporation($id);
            } else {
                $this->ensureCorporation($id);
            }
        }

        foreach ($alliance_ids as $id) {
            $this->ensureAlliance($id);
        }

        return $this->syncCharacterAffiliations($character_ids, $names);
    }

    /**
     * Resolve the current corporation/alliance of the given characters via ESI's
     * bulk affiliation endpoint and store them locally, creating parent entities
     * and previously unknown characters along the way. Returns the number of
     * characters whose affiliation was stored.
     *
     * @param  int[]  $ids
     * @param  array<int, string>  $names  Known character names, stored alongside the affiliation.
     */
    public function syncCharacterAffiliations(array $ids, array $names = []): int
    {
        $synced = 0;

        foreach (array_chunk(array_values(array_unique($ids)), 1000) as $chunk) {
            $result = $this->esi->getAffiliations($chunk);

            if ($result->failed()) {
                continue;
            }

            $named = [];
            $unnamed = [];

            foreach ($result->data as $affiliation) {
                $this->ensureAffiliationsExist($affiliation);

                $row = [
                    'id' => $affiliation->character_id,
                    'corporation_id' => $affiliation->corporation_id,
                    'alliance_id' => $affiliation->alliance_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (isset($names[$affiliation->character_id])) {
                    $named[] = [...$row, 'name' => $names[$affiliation->character_id]];
                } else {
                    $unnamed[] = $row;
                }

                $synced++;
            }

            // Two batches so characters without a resolved name keep the one
            // they already have locally.
            Character::query()->upsert($named, ['id'], ['name', 'corporation_id', 'alliance_id', 'updated_at']);
            Character::query()->upsert($unnamed, ['id'], ['corporation_id', 'alliance_id', 'updated_at']);
        }

        return $synced;
    }

    /**
     * Make sure the alliance exists locally, fetching its public details from
     * ESI the first time we see it. Only non-foreign-key columns are stored.
     */
    public function ensureAlliance(int $id): void
    {
        $alliance = Alliance::query()->firstOrNew(['id' => $id]);

        if ($alliance->exists && $alliance->name !== null) {
            return;
        }

        $alliance->id = $id;

        $result = $this->esi->getAlliance($id);

        if ($result->wasSuccessful()) {
            $alliance->fill([
                'name' => $result->data->name,
                'ticker' => $result->data->ticker,
                'date_founded' => $result->data->date_founded,
            ]);
        }

        $alliance->save();
    }

    /**
     * Make sure the corporation exists locally, fetching its public details from
     * ESI the first time we see it. Only non-foreign-key columns are stored.
     */
    public function ensureCorporation(int $id): void
    {
        $corporation = Corporation::query()->firstOrNew(['id' => $id]);

        if ($corporation->exists && $corporation->name !== null) {
            return;
        }

        $corporation->id = $id;

        $this->fillCorporationFromEsi($corporation);
    }

    /**
     * Re-fetch a corporation's public details even when it is already known
     * locally, keeping its alliance membership current.
     */
    public function refreshCorporation(int $id): void
    {
        $corporation = Corporation::query()->firstOrNew(['id' => $id]);

        $corporation->id = $id;

        $this->fillCorporationFromEsi($corporation);
    }

    private function fillCorporationFromEsi(Corporation $corporation): void
    {
        $result = $this->esi->getCorporation((int) $corporation->id);

        if ($result->wasSuccessful()) {
            $alliance_id = $result->data->alliance_id ?: null;

            if ($alliance_id) {
                $this->ensureAlliance($alliance_id);
            }

            $corporation->fill([
                'name' => $result->data->name,
                'ticker' => $result->data->ticker,
                'member_count' => $result->data->member_count,
                'tax_rate' => $result->data->tax_rate,
                'war_eligible' => $result->data->war_eligible,
                'date_founded' => $result->data->date_founded,
                'description' => $result->data->description,
                'url' => $result->data->url,
                'shares' => $result->data->shares,
                'alliance_id' => $alliance_id,
            ]);
        }

        $corporation->save();
    }
}
