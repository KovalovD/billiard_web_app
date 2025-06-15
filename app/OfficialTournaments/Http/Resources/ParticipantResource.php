<?php

namespace App\OfficialTournaments\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\OfficialTournaments\Models\OfficialParticipant;
use Illuminate\Http\Resources\Json\JsonResource;

/**@mixin OfficialParticipant */
class ParticipantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'stage_id'        => $this->stage_id,
            'user'            => new UserResource($this->whenLoaded('user')),
            'team'            => new TeamResource($this->whenLoaded('team')),
            'seed'            => $this->seed,
            'rating_snapshot' => $this->rating_snapshot,
            'display_name'    => $this->getDisplayName(),
            'is_bye'          => $this->isBye(),
            'stats'           => $this->when($request->has('include_stats'), function () {
                return $this->getMatchStats();
            }),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
