<?php
// app/Tournaments/Http/Resources/TournamentPlayerResource.php

namespace App\Tournaments\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentPlayer */
class TournamentPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'tournament_id'             => $this->tournament_id,
            'is_winner'          => $this->isWinner(),
            'user_id'                   => $this->user_id,
            'position'                  => $this->position,
            'seed_number'               => $this->seed_number,
            'rating'             => $this->getRating($this->tournament->officialRatings->first()?->players),
            'group_code'                => $this->group_code,
            'group_position'            => $this->group_position,
            'group_wins'                => $this->group_wins,
            'group_losses'              => $this->group_losses,
            'group_games_diff'          => $this->group_games_diff,
            'elimination_round'         => $this->elimination_round,
            'elimination_round_display' => $this->elimination_round?->name,
            'rating_points'             => $this->rating_points,
            'prize_amount'              => (float) $this->prize_amount,
            'bonus_amount'              => (float) $this->bonus_amount,
            'achievement_amount' => (float) $this->achievement_amount,
            'total_amount'              => (float) ($this->prize_amount + $this->bonus_amount + $this->achievement_amount),
            'status'             => $this->status?->value,
            'status_display'     => $this->status?->value,
            'registered_at'             => $this->registered_at?->format('Y-m-d H:i:s'),
            'applied_at'                => $this->applied_at?->format('Y-m-d H:i:s'),
            'confirmed_at'              => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'rejected_at'               => $this->rejected_at?->format('Y-m-d H:i:s'),
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
            'is_confirmed'       => $this->isConfirmed(),
            'is_pending'         => $this->isPending(),
            'is_rejected'        => $this->isRejected(),

            // Relations
            'user'                      => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'tournament'                => $this->when(
                $this->relationLoaded('tournament') && !$request->routeIs('tournaments.show.page'),
                function () {
                    return [
                        'id'         => $this->tournament->id,
                        'name'       => $this->tournament->name,
                        'start_date' => $this->tournament->start_date?->format('Y-m-d H:i:s'),
                        'status'     => $this->tournament->status,
                        'stage'      => $this->tournament->stage,
                    ];
                },
            ),
        ];
    }
}
