<?php
// app/Tournaments/Http/Resources/TournamentGroupResource.php

namespace App\Tournaments\Http\Resources;

use App\Tournaments\Models\TournamentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentGroup */
class TournamentGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'tournament_id' => $this->tournament_id,
            'group_code'    => $this->group_code,
            'group_size'    => $this->group_size,
            'advance_count' => $this->advance_count,
            'is_completed'  => $this->is_completed,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // Relations
            'players'       => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'matches'       => TournamentMatchResource::collection($this->whenLoaded('matches')),
            'tournament'    => $this->whenLoaded('tournament', function () {
                return [
                    'id'   => $this->tournament->id,
                    'name' => $this->tournament->name,
                ];
            }),
        ];
    }
}
