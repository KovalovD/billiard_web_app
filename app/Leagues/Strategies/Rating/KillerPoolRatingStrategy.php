<?php

namespace App\Leagues\Strategies\Rating;

use App\Leagues\Models\Rating;

class KillerPoolRatingStrategy implements RatingStrategy
{
    /**
     * Calculate ratings based on KillerPool strategy
     *
     * @param  array<Rating>  $ratings
     * @param  int  $winnerId
     * @param  array  $winnersRules  - This is a game-players array with points
     * @param  array  $losersRules
     * @return array
     */
    public function calculate(
        array $ratings,
        int $winnerId = 0,
        array $winnersRules = [],
        array $losersRules = [],
    ): array {
        $result = [];

        foreach ($ratings as $rating) {
            $result[$rating['id']] = $rating['rating'] + $winnersRules[$rating['user_id']] ?? 0;
        }

        return $result;
    }
}
