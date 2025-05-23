<?php

namespace App\Leagues\Http\Resources;

use App\Leagues\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Rating */
class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'player'                => [
                'id'   => $this->user->id,
                'name' => $this->user->full_name,
            ],
            'rating'                => $this->rating,
            'position'              => $this->position,
            'is_active'             => $this->is_active,
            'is_confirmed'          => $this->is_confirmed,
            'league_id'             => $this->league_id,
            'user_id'               => $this->user_id,
            'hasOngoingMatches'     => $this->ongoingMatches()->count() > 0,
            'matches_count'         => $this->matches()->count(),
            'wins_count'            => $this->wins()->count(),
            'losses_count'          => $this->loses()->count(),
            'league'                => $this->whenLoaded('league', function () {
                return new LeagueResource($this->league);
            }),
            'created_at'            => $this->created_at,
            'last_player_rating_id' => $this->getLastPlayerRatingId(),
        ];
    }
}
