<?php

namespace App\Leagues\Http\Resources;

use App\Leagues\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin League */
class LeagueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                             => $this->id,
            'name'                           => $this->name,
            'picture'                        => $this->picture,
            'details'                        => $this->details,
            'has_rating'                     => $this->has_rating,
            'started_at'                     => $this->started_at,
            'finished_at'                    => $this->finished_at,
            'start_rating'                   => $this->start_rating,
            'rating_change_for_winners_rule' => $this->rating_change_for_winners_rule,
            'rating_change_for_losers_rule'  => $this->rating_change_for_losers_rule,
            'created_at'                     => $this->created_at,
            'updated_at'                     => $this->updated_at,
            'matches_count'                  => $this->matches_count,
            'max_players' => $this->max_players,
            'max_score'   => $this->max_score,

            'game' => $this->game->name,
        ];
    }
}
