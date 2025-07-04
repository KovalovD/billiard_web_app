<?php
// app/Tournaments/Http/Resources/TournamentMatchResource.php

namespace App\Tournaments\Http\Resources;

use App\Core\Http\Resources\ClubTableResource;
use App\Core\Http\Resources\UserResource;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentMatch */
class TournamentMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'tournament_id'        => $this->tournament_id,
            'match_code'           => $this->match_code,
            'stage'                => $this->stage,
            'stage_display'        => $this->stage?->name,
            'round'                => $this->round,
            'round_display'        => $this->round?->name,
            'bracket_position'     => $this->bracket_position,
            'bracket_side'         => $this->bracket_side,
            'bracket_side_display' => $this->bracket_side?->name,
            'player1_id'           => $this->player1_id,
            'player2_id'           => $this->player2_id,
            'winner_id'            => $this->winner_id,
            'player1_score'        => $this->player1_score,
            'player2_score'        => $this->player2_score,
            'races_to'             => $this->races_to,
            'previous_match1_id'   => $this->previous_match1_id,
            'previous_match2_id'   => $this->previous_match2_id,
            'next_match_id'        => $this->next_match_id,
            'loser_next_match_id'  => $this->loser_next_match_id,
            'club_table_id'        => $this->club_table_id,
            'stream_url'           => $this->stream_url,
            'status'               => $this->status,
            'status_display' => $this->status->displayValue(),
            'scheduled_at'         => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'started_at'           => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at'         => $this->completed_at?->format('Y-m-d H:i:s'),
            'admin_notes'          => $this->admin_notes,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,

            // Relations
            'player1'              => $this->whenLoaded('player1', fn() => new UserResource($this->player1)),
            'player2'              => $this->whenLoaded('player2', fn() => new UserResource($this->player2)),
            'winner'               => $this->whenLoaded('winner', fn() => new UserResource($this->winner)),
            'club_table'           => $this->whenLoaded('clubTable', fn() => new ClubTableResource($this->clubTable)),
            'previous_match1'      => $this->whenLoaded('previousMatch1',
                fn() => new TournamentMatchResource($this->previousMatch1)),
            'previous_match2'      => $this->whenLoaded('previousMatch2',
                fn() => new TournamentMatchResource($this->previousMatch2)),
            'tournament'           => $this->whenLoaded('tournament', function () {
                return [
                    'id'       => $this->tournament->id,
                    'name'     => $this->tournament->name,
                    'races_to' => $this->tournament->races_to,
                ];
            }),
        ];
    }
}
