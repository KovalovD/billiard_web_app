<?php

namespace App\Leagues\Http\Resources;

use App\Leagues\Models\League;
use App\Leagues\Services\LeaguesService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin League */
class LeagueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $leaguesService = app(LeaguesService::class);

        return [
            'id'                             => $this->id,
            'slug'                         => $this->slug,
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
            'matches_count'                => $this->matches_count + $this->multiplayer_games_count,
            'invite_days_expire'           => $this->invite_days_expire,
            'max_players'                  => $this->max_players,
            'max_score'                    => $this->max_score,
            'active_players'               => $this->active_ratings_count,

            // Game info - optimized to avoid redundancy
            'game_id'                      => $this->game_id,
            'game'                         => $this->whenLoaded('game', function () {
                return $this->game->name;
            }),
            'game_type'                    => $this->whenLoaded('game', function () {
                return $this->game->type;
            }),
            'game_multiplayer'             => $this->whenLoaded('game', function () {
                return $this->game->is_multiplayer;
            }),
            // Grand Final Fund accumulated (only for multiplayer games)
            'grand_final_fund_accumulated' => $this->when(
                $this->game && $this->game->is_multiplayer,
                fn() => $leaguesService->calculateAccumulatedGrandFinalFund($this->resource),
            ),
        ];
    }
}
