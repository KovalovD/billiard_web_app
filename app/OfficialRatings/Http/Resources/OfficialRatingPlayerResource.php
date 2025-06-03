<?php

namespace App\OfficialRatings\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OfficialRatingPlayer */
class OfficialRatingPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'division' => match (true) {
                $this->position <= 8 => 'Elite',
                $this->position > 8 && $this->position <= 16 => 'S',
                $this->position > 16 && $this->position <= 24 => 'A',
                $this->position > 24 && $this->position <= 64 => 'B',
                $this->position > 64 => 'C',
            },
            'official_rating_id' => $this->official_rating_id,
            'user_id'            => $this->user_id,
            'rating_points'      => $this->rating_points,
            'position'           => $this->position,
            'tournaments_played' => $this->tournaments_played,
            'tournaments_won'    => $this->tournaments_won,
            'win_rate'           => $this->win_rate,
            'last_tournament_at' => $this->last_tournament_at,
            'is_active'          => $this->is_active,
            'is_top_player'      => $this->isTopPlayer(),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,

            // Relations
            'user'               => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'official_rating'    => $this->when(
                $this->relationLoaded('officialRating') && !$request->routeIs('official-ratings.show'),
                function () {
                    return [
                        'id'   => $this->officialRating->id,
                        'name' => $this->officialRating->name,
                        'game' => [
                            'name' => $this->officialRating->game->name,
                            'type' => $this->officialRating->game->type,
                        ],
                    ];
                },
            ),
        ];
    }
}
