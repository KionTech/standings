<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StandingsSource
 */
class StandingsSourceSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'entity_id' => $this->entity_id,
            'entity_name' => $this->entityName(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
