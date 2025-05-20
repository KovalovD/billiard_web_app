<?php

namespace App\Leagues\Strategies\Rating;

class KillerPoolRatingStrategy implements RatingStrategy
{
    /**
     * Calculate ratings based on KillerPool strategy
     *
     * @param  array  $ratings
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
            if (!empty($winnersRules[$rating['user_id']])) {
                $result[$rating['id']] = $rating['rating'] + $winnersRules[$rating['user_id']] ?? 0;
            } else {
                $result[$rating['id']] = $rating['rating'];
            }
        }

        return $result;
    }
}
