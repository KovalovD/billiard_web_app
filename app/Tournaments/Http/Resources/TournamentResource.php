<?php

namespace App\Tournaments\Http\Resources;

use App\Leagues\Http\Resources\ClubResource;
use App\Tournaments\Models\Tournament;
use App\User\Http\Resources\CityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tournament */
class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'regulation'           => $this->regulation,
            'details'              => $this->details,
            'status'               => $this->status,
            'status_display'       => $this->status_display,
            'start_date'           => $this->start_date?->format('Y-m-d'),
            'end_date'             => $this->end_date?->format('Y-m-d'),
            'max_participants'     => $this->max_participants,
            'entry_fee'            => $this->entry_fee,
            'prize_pool'           => $this->prize_pool,
            'prize_distribution'   => $this->prize_distribution,
            'organizer'            => $this->organizer,
            'format'               => $this->format,
            'players_count'        => $this->players_count,
            'is_registration_open' => $this->isRegistrationOpen(),
            'is_active'            => $this->isActive(),
            'is_completed'         => $this->isCompleted(),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,

            // Relations
            'game'                 => $this->whenLoaded('game', function () {
                return [
                    'id'   => $this->game->id,
                    'name' => $this->game->name,
                    'type' => $this->game->type,
                ];
            }),
            'city'                 => $this->whenLoaded('city', fn() => new CityResource($this->city)),
            'club'                 => $this->whenLoaded('club', fn() => new ClubResource($this->club)),
            'players'              => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'winner'               => $this->when($this->isCompleted(), function () {
                $winner = $this->getWinner();
                return $winner ? new TournamentPlayerResource($winner) : null;
            }),
            'top_players'          => $this->when($this->isCompleted(), function () {
                return TournamentPlayerResource::collection($this->getTopPlayers());
            }),
        ];
    }
}
