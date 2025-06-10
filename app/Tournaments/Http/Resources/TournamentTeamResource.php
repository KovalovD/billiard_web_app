<?php

namespace App\Tournaments\Http\Resources;

use App\Tournaments\Models\TournamentTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentTeam */
class TournamentTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tournament_id'       => $this->tournament_id,
            'name'                => $this->name,
            'short_name'          => $this->short_name,
            'display_name'        => $this->getDisplayName(),
            'seed'                => $this->seed,
            'group_id'            => $this->group_id,
            'bracket_position'    => $this->bracket_position,
            'is_active'           => $this->is_active,
            'roster_data'         => $this->roster_data,
            'is_ready_to_compete' => $this->isReadyToCompete(),
            'has_minimum_players' => $this->hasMinimumPlayers(),
            'has_captain'         => $this->hasCaptain(),
            'roster_summary'      => $this->getRosterSummary(),
            'statistics'          => $this->getStats(),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,

            // Relations
            'group'               => $this->whenLoaded('group', fn() => new TournamentGroupResource($this->group)),
            'players'             => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'captain'             => $this->whenLoaded('players', function () {
                $captain = $this->players->where('team_role', 'captain')->first();
                return $captain ? new TournamentPlayerResource($captain) : null;
            }),
        ];
    }
}
