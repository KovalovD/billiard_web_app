<?php

namespace App\Leagues\Strategies\Rating;

use App\Leagues\Models\Rating;

interface RatingStrategy
{
    /**
     * @param  array<Rating>  $ratings
     */
    public function calculate(
        array $ratings,
        int $winnerId,
        array $winnersRules,
        array $losersRules,
    ): array;
}
