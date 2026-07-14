<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StandingsSourceType;
use App\Models\Alliance;
use App\Models\Character;
use App\Models\Corporation;
use App\Models\SourceContact;
use App\Models\StandingRequest;
use App\Models\StandingsSource;
use Illuminate\Support\Collection;
use NicolasKion\Esi\Enums\ContactType;

class EffectiveStandingResolver
{
    /**
     * The source contacts keyed by "type:id" for in-memory lookups.
     *
     * @var Collection<string, SourceContact>
     */
    private Collection $contacts;

    private readonly ?StandingsSource $source;

    public function __construct()
    {
        $this->contacts = SourceContact::query()->get()
            ->keyBy(fn (SourceContact $contact): string => $contact->key());

        $this->source = StandingsSource::current();
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
     * The standing that applies to a character: part of the standings source
     * itself, its own contact standing, or the one inherited from its
     * corporation or alliance.
     *
     * @return array{value: float, source: string}|null
     */
    public function standingForCharacter(Character $character): ?array
    {
        if ($this->source?->coversCharacter($character)) {
            return ['value' => 10.0, 'source' => 'source'];
        }

        return $this->firstStanding([
            [ContactType::Character, $character->id],
            [ContactType::Corporation, $character->corporation_id],
            [ContactType::Alliance, $character->alliance_id],
        ]);
    }

    /**
     * The standing that applies to a corporation or alliance: part of the
     * standings source itself, its own contact standing, or (for a
     * corporation) the one inherited from its alliance.
     *
     * @return array{value: float, source: string}|null
     */
    public function standingForEntity(Corporation|Alliance $entity): ?array
    {
        if ($this->sourceCoversEntity($entity)) {
            return ['value' => 10.0, 'source' => 'source'];
        }

        return $this->firstStanding($entity instanceof Corporation
            ? [[ContactType::Corporation, $entity->id], [ContactType::Alliance, $entity->alliance_id]]
            : [[ContactType::Alliance, $entity->id]]);
    }

    /**
     * Whether the entity is the standings source or belongs to it.
     */
    private function sourceCoversEntity(Corporation|Alliance $entity): bool
    {
        if (! $this->source instanceof StandingsSource) {
            return false;
        }

        if ($entity instanceof Corporation) {
            return ($this->source->type === StandingsSourceType::Corporation && $this->source->entity_id === $entity->id)
                || ($this->source->type === StandingsSourceType::Alliance && $this->source->entity_id === $entity->alliance_id);
        }

        return $this->source->type === StandingsSourceType::Alliance && $this->source->entity_id === $entity->id;
    }

    /**
     * The first contact found along the chain, most specific entity first,
     * with the entity type the standing is set on.
     *
     * @param  list<array{0: ContactType, 1: int|null}>  $chain
     * @return array{value: float, source: string}|null
     */
    private function firstStanding(array $chain): ?array
    {
        foreach ($chain as [$type, $id]) {
            if ($id === null) {
                continue;
            }

            $contact = $this->contacts->get(SourceContact::keyFor($type, $id));

            if ($contact instanceof SourceContact) {
                return [
                    'value' => $contact->standing,
                    'source' => $type->value,
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
