<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SourceContact
 */
class StandingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'contact_id' => $this->contact_id,
            'contact_type' => $this->contact_type->value,
            'name' => $this->name,
            'standing' => $this->standing,
            'redundant_via' => $this->redundant_via,
        ];
    }
}
