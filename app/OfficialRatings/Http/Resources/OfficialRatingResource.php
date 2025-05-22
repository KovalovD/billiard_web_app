<?php

namespace App\OfficialRatings\Http\Resources;

use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Http\Resources\TournamentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfficialRating */
class OfficialRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'description'        => $this->description,
            'is_active'          => $this->is_active,
            'initial_rating'     => $this->initial_rating,
            'calculation_method' => $this->calculation_method,
            'rating_rules'       => $this->rating_rules,
            'players_count'      => $this->players_count,
            'tournaments_count'  => $this->tournaments_count,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,

            // Relations
            'game'               => $this->whenLoaded('game', function () {
                return [
                    'id'   => $this->game->id,
                    'name' => $this->game->name,
                    'type' => $this->game->type,
                ];
            }),
            'players'            => OfficialRatingPlayerResource::collection($this->whenLoaded('players')),
            'tournaments'        => TournamentResource::collection($this->whenLoaded('tournaments')),
            'top_players'        => $this->when($request->get('include_top_players'), function () {
                return OfficialRatingPlayerResource::collection($this->getTopPlayers());
            }),
        ];
    }
}
