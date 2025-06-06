<?php

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
            'id'                 => $this->id,
            'tournament_id'      => $this->tournament_id,
            'user_id'            => $this->user_id,
            'position'           => $this->position,
            'rating_points'      => $this->rating_points,
            'prize_amount'       => $this->prize_amount,
            'bonus_amount'       => $this->bonus_amount,
            'achievement_amount' => $this->achievement_amount,
            'total_amount'       => $this->total_amount,
            'status'             => $this->status,
            'status_display'     => $this->status_display,
            'is_winner'          => $this->isWinner(),
            'is_in_top_three'    => $this->isInTopThree(),
            'registered_at'      => $this->registered_at,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            'is_confirmed'       => $this->isConfirmed(),
            'is_pending'         => $this->isPending(),
            'is_rejected'        => $this->isRejected(),
            'applied_at'         => $this->applied_at,
            'confirmed_at'       => $this->confirmed_at,
            'rejected_at'        => $this->rejected_at,

            // Relations
            'user'               => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'tournament'         => $this->when(
                $this->relationLoaded('tournament') && !$request->routeIs('tournaments.show.page'),
                function () {
                    return [
                        'id'         => $this->tournament->id,
                        'name'       => $this->tournament->name,
                        'start_date' => $this->tournament->start_date?->format('Y-m-d'),
                        'status'     => $this->tournament->status,
                    ];
                },
            ),
        ];
    }
}
