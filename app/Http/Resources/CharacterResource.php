<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Character
 */
class CharacterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'corporation_id' => $this->corporation_id,
            'alliance_id' => $this->alliance_id,
            'corporation' => $this->whenLoaded('corporation', fn () => $this->corporation ? (new EntitySummaryResource($this->corporation))->resolve($request) : null),
            'alliance' => $this->whenLoaded('alliance', fn () => $this->alliance ? (new EntitySummaryResource($this->alliance))->resolve($request) : null),
        ];
    }
}
