<?php

namespace App\Tournaments\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\Leagues\Http\Resources\ClubResource;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentMatch */
class TournamentMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tournament_id'       => $this->tournament_id,
            'match_type'          => $this->match_type,
            'match_type_display'  => ucfirst(str_replace('_', ' ', $this->match_type)),
            'round_number'        => $this->round_number,
            'match_number'        => $this->match_number,
            'bracket_type'        => $this->bracket_type,
            'group_id'            => $this->group_id,

            // Participants
            'participant_1_id'    => $this->participant_1_id,
            'participant_1_type'  => $this->participant_1_type,
            'participant_1_name'  => $this->getParticipantName(1),
            'participant_2_id'    => $this->participant_2_id,
            'participant_2_type'  => $this->participant_2_type,
            'participant_2_name'  => $this->getParticipantName(2),

            // Match status and results
            'status'              => $this->status,
            'status_display'      => $this->getStatusDisplay(),
            'scores'              => $this->scores,
            'participant_1_score' => $this->participant_1_score,
            'participant_2_score' => $this->participant_2_score,
            'winner_id'           => $this->winner_id,
            'winner_type'         => $this->winner_type,
            'winner_name'         => $this->winner_id ? $this->getParticipantName(
                $this->winner_id === $this->participant_1_id ? 1 : 2,
            ) : null,

            // Scheduling
            'scheduled_at'        => $this->scheduled_at,
            'started_at'          => $this->started_at,
            'completed_at'        => $this->completed_at,
            'table_number'        => $this->table_number,
            'referee'             => $this->referee,
            'notes'               => $this->notes,
            'match_data'          => $this->match_data,

            // Computed properties
            'display_name'        => $this->getDisplayName(),
            'is_completed'        => $this->isCompleted(),
            'is_in_progress'      => $this->isInProgress(),
            'is_pending'          => $this->isPending(),
            'result_summary'      => $this->getResultSummary(),

            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // Relations
            'group'         => $this->whenLoaded('group', fn() => new TournamentGroupResource($this->group)),
            'club'          => $this->whenLoaded('club', fn() => new ClubResource($this->club)),
            'participant_1' => $this->when(
                $this->participant_1_type === 'player' && $this->relationLoaded('participant1'),
                fn() => new TournamentPlayerResource($this->participant1),
            ),
            'participant_2' => $this->when(
                $this->participant_2_type === 'player' && $this->relationLoaded('participant2'),
                fn() => new TournamentPlayerResource($this->participant2),
            ),
            'team_1'        => $this->when(
                $this->participant_1_type === 'team' && $this->relationLoaded('participant1'),
                fn() => new TournamentTeamResource($this->participant1),
            ),
            'team_2'        => $this->when(
                $this->participant_2_type === 'team' && $this->relationLoaded('participant2'),
                fn() => new TournamentTeamResource($this->participant2),
            ),
        ];
    }
}
