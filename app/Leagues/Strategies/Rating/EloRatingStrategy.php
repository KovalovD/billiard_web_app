<?php

namespace App\Leagues\Strategies\Rating;

use LogicException;

class EloRatingStrategy implements RatingStrategy
{
    public function calculate(
        array $ratings,
        int $winnerId,
        array $winnersRules,
        array $losersRules,
    ): array {
        [$playerRating1, $playerRating2] = $ratings;

        $r1 = $playerRating1->rating;
        $r2 = $playerRating2->rating;

        $delta = abs($r1 - $r2);
        $isPlayer1Winner = $winnerId === $playerRating1->user_id;

        $winner = $isPlayer1Winner ? $playerRating1 : $playerRating2;
        $loser = $isPlayer1Winner ? $playerRating2 : $playerRating1;

        $winnerIsStronger = $winner->rating >= $loser->rating;

        $winnerRule = $this->getRuleByDelta($winnersRules, $delta);
        $loserRule = $this->getRuleByDelta($losersRules, $delta);

        if (!$winnerRule || !$loserRule) {
            throw new LogicException("No rule matched for delta $delta");
        }

        $winnerDelta = $winnerIsStronger ? $winnerRule['strong'] : $winnerRule['weak'];
        $loserDelta = $winnerIsStronger ? $loserRule['strong'] : $loserRule['weak'];

        return [
            $playerRating1->id => $r1 + ($isPlayer1Winner ? $winnerDelta : $loserDelta),
            $playerRating2->id => $r2 + ($isPlayer1Winner ? $loserDelta : $winnerDelta),
        ];
    }

    private function getRuleByDelta(array $rules, int $delta): ?array
    {
        foreach ($rules as $rule) {
            [$min, $max] = $rule['range'];
            if ($delta >= $min && $delta <= $max) {
                return $rule;
            }
        }

        return null;
    }
}
