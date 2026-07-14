<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NicolasKion\Esi\Enums\EsiScope;

/**
 * @mixin \App\Models\Character
 */
class CharacterSyncResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_main' => $this->id === $request->user()?->main_character_id,
            'should_sync' => $this->should_sync,
            'has_write_scope' => $this->hasEsiTokenWithScope(EsiScope::WriteCharacterContacts),
            'inherits_source' => (bool) $this->inherits_source,
            'synced_contacts_count' => $this->synced_contacts_count,
            'corporation' => $this->whenLoaded('corporation', fn () => $this->corporation ? (new EntitySummaryResource($this->corporation))->resolve($request) : null),
            'alliance' => $this->whenLoaded('alliance', fn () => $this->alliance ? (new EntitySummaryResource($this->alliance))->resolve($request) : null),
        ];
    }
}
