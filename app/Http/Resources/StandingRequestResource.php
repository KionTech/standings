<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StandingRequest
 */
class StandingRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
            'subject' => [
                'type' => $this->subject_type->value,
                'id' => $this->subject_id,
                'name' => $this->subjectName(),
            ],
            'requested_by' => $this->character->name,
            'effective_standing' => $this->effective_standing,
        ];
    }
}
