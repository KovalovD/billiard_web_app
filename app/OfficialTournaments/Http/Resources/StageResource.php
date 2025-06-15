<?php

namespace App\OfficialTournaments\Http\Resources;

use App\OfficialTournaments\Models\OfficialStage;
use Illuminate\Http\Resources\Json\JsonResource;

/**@mixin OfficialStage */
class StageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'tournament_id'      => $this->tournament_id,
            'type'               => $this->type,
            'number'             => $this->number,
            'settings'           => $this->settings,
            'participants_count' => $this->whenCounted('participants'),
            'matches_count'      => $this->whenCounted('matches'),
            'participants'       => ParticipantResource::collection($this->whenLoaded('participants')),
            'matches'            => MatchResource::collection($this->whenLoaded('matches')),
            'is_complete'        => $this->when($this->relationLoaded('matches'), function () {
                return $this->matches->every(fn($match) => $match->status === 'finished');
            }),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
