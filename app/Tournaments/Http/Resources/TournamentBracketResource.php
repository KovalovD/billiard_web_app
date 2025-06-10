<?php

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
            'id'                    => $this->id,
            'tournament_id'         => $this->tournament_id,
            'bracket_type'          => $this->bracket_type,
            'bracket_type_display'  => ucfirst(str_replace('_', ' ', $this->bracket_type)),
            'total_rounds'          => $this->total_rounds,
            'total_participants'    => $this->total_participants,
            'current_round'         => $this->current_round,
            'is_active'             => $this->is_active,
            'is_completed'          => $this->is_completed,
            'bracket_structure'     => $this->bracket_structure,
            'participant_positions' => $this->participant_positions,
            'advancement_rules'     => $this->advancement_rules,
            'champion'              => $this->when($this->is_completed, fn() => $this->getChampion()),
            'status'                => $this->getStatus(),
            'started_at'            => $this->started_at,
            'completed_at'          => $this->completed_at,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,

            // Relations
            'matches'               => TournamentMatchResource::collection($this->whenLoaded('matches')),
        ];
    }
}
