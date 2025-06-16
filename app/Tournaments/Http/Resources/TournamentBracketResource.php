<?php
// app/Tournaments/Http/Resources/TournamentBracketResource.php

namespace App\Tournaments\Http\Resources;

use App\Tournaments\Models\TournamentBracket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentBracket */
class TournamentBracketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'tournament_id'        => $this->tournament_id,
            'bracket_type'         => $this->bracket_type,
            'bracket_type_display' => $this->bracket_type?->name,
            'total_rounds'         => $this->total_rounds,
            'players_count'        => $this->players_count,
            'bracket_structure'    => $this->bracket_structure,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,

            // Relations
            'matches'              => TournamentMatchResource::collection($this->whenLoaded('matches')),
            'tournament'           => $this->whenLoaded('tournament', function () {
                return [
                    'id'              => $this->tournament->id,
                    'name'            => $this->tournament->name,
                    'tournament_type' => $this->tournament->tournament_type,
                ];
            }),
        ];
    }
}
