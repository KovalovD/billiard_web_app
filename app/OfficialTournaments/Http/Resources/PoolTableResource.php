<?php

namespace App\OfficialTournaments\Http\Resources;

use App\OfficialTournaments\Models\OfficialPoolTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfficialPoolTable */
class PoolTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'cloth_speed'          => $this->cloth_speed,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,

            'tournament_id' => $this->tournament_id,

            'matches'    => MatchResource::collection($this->whenLoaded('matches')),
            'tournament' => new TournamentResource($this->whenLoaded('tournament')),
        ];
    }
}
