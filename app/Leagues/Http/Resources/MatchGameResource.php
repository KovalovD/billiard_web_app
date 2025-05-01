<?php

namespace App\Leagues\Http\Resources;

use App\Core\Http\Resources\UserResource;
use App\Matches\Models\MatchGame;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MatchGame */
class MatchGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,

            'league_id' => $this->league_id,
            'stream_url' => $this->stream_url,
            'details'    => $this->details,

            'first_rating_id'   => $this->first_rating_id,
            'second_rating_id'  => $this->second_rating_id,
            'first_user_score'  => $this->first_user_score,
            'second_user_score' => $this->second_user_score,
            'winner_rating_id'  => $this->winner_rating_id,
            'loser_rating_id'   => $this->loser_rating_id,
            'result_confirmed' => $this->result_confirmed,

            'rating_change_for_winner'  => $this->rating_change_for_winner,
            'rating_change_for_loser'   => $this->rating_change_for_loser,
            'first_rating_before_game'  => $this->first_rating_before_game,
            'second_rating_before_game' => $this->second_rating_before_game,

            'invitation_sent_at'        => $this->invitation_sent_at,
            'invitation_available_till' => $this->invitation_available_till,
            'invitation_accepted_at'    => $this->invitation_accepted_at,
            'finished_at'               => $this->finished_at,
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,

            'club'   => new ClubResource($this->whenLoaded('club')),
            'league' => new LeagueResource($this->whenLoaded('league')),

            'firstPlayer'  => [
                'user' => new UserResource($this->firstRating?->user),
                'rating' => new RatingResource($this->whenLoaded('firstRating')),
            ],
            'secondPlayer' => [
                'user' => new UserResource($this->secondRating?->user),
                'rating' => new RatingResource($this->whenLoaded('secondRating')),
            ],
        ];
    }
}
