<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StandingRequestStatus;
use App\Enums\StandingsSourceType;
use App\Models\Character;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use Illuminate\Support\Facades\DB;
use NicolasKion\Esi\Esi;

class StandingsSourceService
{
    public function __construct(private readonly Esi $esi) {}

    /**
     * Re-fetch the configured source's contacts and store them as the canonical
     * standings. Returns null on failure, true when the standings changed, and
     * false when they were already up to date.
     */
    public function refresh(): ?bool
    {
        $source = StandingsSource::current();

        if (! $source instanceof StandingsSource) {
            return null;
        }

        $reader = $this->reader($source);

        if (! $reader instanceof Character) {
            return null;
        }

        $result = match ($source->type) {
            StandingsSourceType::Character => $this->esi->getCharacterContacts($reader),
            StandingsSourceType::Corporation => $this->esi->getCorporationContacts($reader, $source->entity_id),
            StandingsSourceType::Alliance => $this->esi->getAllianceContacts($reader, $source->entity_id),
        };

        if ($result->failed()) {
            return null;
        }

        $changed = $this->storeContacts($result->data);

        $source->update(['last_synced_at' => now()]);

        return $changed;
    }

    /**
     * An admin character that can read the source's contacts: it holds the required
     * scope and (for corp/alliance sources) is a member of that entity.
     */
    public function reader(StandingsSource $source): ?Character
    {
        $query = Character::query()
            ->whereIn('id', config('services.eveonline.admin_character_ids', []))
            ->whereHas('esiTokens.esiScopes', fn ($q) => $q->where('name', $source->type->requiredScope()));

        return match ($source->type) {
            StandingsSourceType::Character => $query->whereKey($source->entity_id)->first(),
            StandingsSourceType::Corporation => $query->where('corporation_id', $source->entity_id)->first(),
            StandingsSourceType::Alliance => $query->where('alliance_id', $source->entity_id)->first(),
        };
    }

    /**
     * Store the fetched contacts as the canonical standings. Returns whether the
     * standings (contact ids + values) actually changed. When unchanged, no work
     * is done — including skipping the name-resolution request — to save calls.
     *
     * @param  \NicolasKion\Esi\DTO\Contact[]  $contacts
     */
    private function storeContacts(array $contacts): bool
    {
        $incoming = [];

        foreach ($contacts as $contact) {
            $incoming[$contact->contact_id] = round((float) $contact->standing, 2);
        }

        $existing = SourceContact::query()->pluck('standing', 'contact_id')
            ->map(static fn ($standing): float => round((float) $standing, 2))
            ->all();

        if (! $this->contactsDiffer($existing, $incoming)) {
            return false;
        }

        $contact_ids = array_keys($incoming);
        $names = $this->resolveNames($contact_ids);

        $rows = [];

        foreach ($contacts as $contact) {
            $rows[] = [
                'contact_id' => $contact->contact_id,
                'contact_type' => $contact->contact_type->value,
                'name' => $names[$contact->contact_id] ?? null,
                'standing' => $contact->standing,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($contact_ids, $rows): void {
            SourceContact::query()
                ->whereNotIn('contact_id', $contact_ids === [] ? [0] : $contact_ids)
                ->delete();

            if ($rows !== []) {
                SourceContact::query()->upsert($rows, ['contact_id'], ['contact_type', 'name', 'standing', 'updated_at']);
            }
        });

        $this->markFulfilledRequestsDone();

        return true;
    }

    /**
     * Auto-complete pending standing requests whose subject now has a direct,
     * positive (blue) standing. A standing inherited only through a parent corp
     * or alliance never auto-closes a request — that is left to an admin.
     */
    private function markFulfilledRequestsDone(): void
    {
        $pending = StandingRequest::query()
            ->where('status', StandingRequestStatus::Pending)
            ->get(['id', 'subject_type', 'subject_id']);

        if ($pending->isEmpty()) {
            return;
        }

        $blueContacts = SourceContact::query()
            ->where('standing', '>', 0)
            ->get(['contact_id', 'contact_type'])
            ->keyBy(fn (SourceContact $contact): string => $contact->contact_type->value.':'.$contact->contact_id);

        $fulfilledIds = $pending
            ->filter(fn (StandingRequest $request): bool => $blueContacts->has($request->subject_type->value.':'.$request->subject_id))
            ->pluck('id');

        if ($fulfilledIds->isNotEmpty()) {
            StandingRequest::query()->whereKey($fulfilledIds)->update(['status' => StandingRequestStatus::Done]);
        }
    }

    /**
     * @param  array<int, float>  $existing
     * @param  array<int, float>  $incoming
     */
    private function contactsDiffer(array $existing, array $incoming): bool
    {
        if (count($existing) !== count($incoming)) {
            return true;
        }

        foreach ($incoming as $id => $standing) {
            if (! array_key_exists($id, $existing) || abs($existing[$id] - $standing) >= 0.01) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve entity names for the given ids via ESI's bulk name endpoint.
     *
     * @param  int[]  $ids
     * @return array<int, string> id => name
     */
    private function resolveNames(array $ids): array
    {
        $names = [];

        foreach (array_chunk($ids, 1000) as $chunk) {
            $result = $this->esi->getNames($chunk);

            if ($result->failed()) {
                continue;
            }

            foreach ($result->data as $name) {
                $names[$name->id] = $name->name;
            }
        }

        return $names;
    }
}
