<?php

namespace App\OfficialTournaments\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\Leagues\Http\Resources\ClubResource;
use App\OfficialTournaments\Models\OfficialTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfficialTeam */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'seed'               => $this->seed,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            'members_count'      => $this->members_count,
            'participants_count' => $this->participants_count,
            'team_members_count' => $this->team_members_count,

            'tournament_id' => $this->tournament_id,
            'club_id'       => $this->club_id,

            'club'         => new ClubResource($this->whenLoaded('club')),
            'members'      => UserResource::collection($this->whenLoaded('members')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'tournament'   => new TournamentResource($this->whenLoaded('tournament')),
        ];
    }
}
