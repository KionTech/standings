<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StandingsSourceType;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use Illuminate\Support\Collection;

class EffectiveStandingResolver
{
    /**
     * The source contacts keyed by "type:id" for in-memory lookups.
     *
     * @var Collection<string, SourceContact>
     */
    private Collection $contacts;

    public function __construct()
    {
        $this->contacts = SourceContact::query()->get()
            ->keyBy(fn (SourceContact $contact): string => $contact->key());
    }

    /**
     * Resolve the standing that applies to a request's subject, walking up the
     * character → corporation → alliance hierarchy of the requesting character.
     * A "direct" source means the entity itself has a standing; otherwise it is
     * inherited from a parent. Returns null when neither the entity nor any
     * parent has a standing.
     *
     * @return array{standing: float, source: string, via_type: string, via_id: int, via_name: string|null}|null
     */
    public function resolve(StandingRequest $request): ?array
    {
        foreach ($this->chainFor($request) as [$source, $type, $id]) {
            $contact = $this->contacts->get(SourceContact::keyFor($type, $id));

            if ($contact instanceof SourceContact) {
                return [
                    'standing' => (float) $contact->standing,
                    'source' => $source,
                    'via_type' => $type->value,
                    'via_id' => $id,
                    'via_name' => $contact->name,
                ];
            }
        }

        return null;
    }

    /**
     * The ordered (source, type, id) tuples to test for a request: the entity
     * itself first, then its parents drawn from the requesting character.
     *
     * @return list<array{0: string, 1: StandingsSourceType, 2: int}>
     */
    private function chainFor(StandingRequest $request): array
    {
        $character = $request->character;

        $chain = [['direct', $request->subject_type, $request->subject_id]];

        if ($request->subject_type === StandingsSourceType::Character && $character->corporation_id !== null) {
            $chain[] = ['corporation', StandingsSourceType::Corporation, $character->corporation_id];
        }

        if ($request->subject_type !== StandingsSourceType::Alliance && $character->alliance_id !== null) {
            $chain[] = ['alliance', StandingsSourceType::Alliance, $character->alliance_id];
        }

        return $chain;
    }
}
