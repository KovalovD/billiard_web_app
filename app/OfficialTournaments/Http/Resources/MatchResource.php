<?php

namespace App\OfficialTournaments\Http\Resources;

use App\OfficialTournaments\Models\OfficialMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfficialMatch */
class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'round'                   => $this->round,
            'bracket'                 => $this->bracket,
            'scheduled_at'            => $this->scheduled_at,
            'status'                  => $this->status,
            'metadata'                => $this->metadata,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,

            'stage_id' => $this->stage_id,
            'table_id' => $this->table_id,

            'stage' => new StageResource($this->whenLoaded('stage')),
        ];
    }
}
