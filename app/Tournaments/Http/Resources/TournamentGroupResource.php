<?php

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
            'id'                         => $this->id,
            'tournament_id'              => $this->tournament_id,
            'name'                       => $this->name,
            'display_name'               => $this->display_name,
            'group_number'               => $this->group_number,
            'max_participants'           => $this->max_participants,
            'advance_count'              => $this->advance_count,
            'is_completed'               => $this->is_completed,
            'current_participants_count' => $this->whenLoaded('players',
                fn() => $this->players->count(),
                fn() => $this->whenLoaded('teams', fn() => $this->teams->count(), 0),
            ),
            'standings'                  => $this->when($this->is_completed || $this->standings_cache,
                fn() => $this->getStandings(),
            ),
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,

            // Relations
            'players'                    => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'teams'                      => TournamentTeamResource::collection($this->whenLoaded('teams')),
            'matches'                    => TournamentMatchResource::collection($this->whenLoaded('matches')),
        ];
    }
}
