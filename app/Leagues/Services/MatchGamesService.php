<?php

namespace App\Leagues\Services;

use App\Core\Models\User;
use App\Leagues\DataTransferObjects\SendGameDTO;
use App\Leagues\DataTransferObjects\SendResultDTO;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

readonly class MatchGamesService
{
    public function __construct(private RatingService $ratingService)
    {
    }

    public function send(SendGameDTO $sendGameDTO): bool
    {
        // Get both players' ratings
        $senderRating = $this->ratingService->getActiveRatingForUserLeague(
            $sendGameDTO->sender,
            $sendGameDTO->league,
        );
        $receiverRating = $this->ratingService->getActiveRatingForUserLeague(
            $sendGameDTO->receiver,
            $sendGameDTO->league,
        );

        // Check if both players are active and different
        if (
            !$senderRating
            || !$receiverRating
            || $senderRating->id === $receiverRating->id
        ) {
            return false;
        }

        // Check if both players are available (no ongoing matches)
        if (
            !$this->checkAvailability($receiverRating, $sendGameDTO->league)
            || !$this->checkAvailability($senderRating, $sendGameDTO->league)
        ) {
            return false;
        }

        // Check if players are within ±10 positions of each other
        if (!$this->isWithinChallengeRange($senderRating, $receiverRating)) {
            return false;
        }

        // Check if last match wasn't with the same opponent
        if ($this->wasLastMatchWithSameOpponent($senderRating, $receiverRating, $sendGameDTO->league)) {
            return false;
        }

        // Create the match
        MatchGame::create([
            'game_id'                   => $sendGameDTO->league->game_id,
            'league_id'                 => $sendGameDTO->league->id,
            'first_rating_id'           => $senderRating->id,
            'second_rating_id'          => $receiverRating->id,
            'first_rating_before_game'  => $senderRating->rating,
            'second_rating_before_game' => $receiverRating->rating,
            'stream_url'                => $sendGameDTO->stream_url,
            'details'                   => $sendGameDTO->details,
            'club_id'                   => $sendGameDTO->club_id,
            'status'                    => GameStatus::IN_PROGRESS,
            'invitation_sent_at'        => now(),
            'invitation_available_till' => now()->addDays($sendGameDTO->league->invite_days_expire),
        ]);

        return true;
    }

    /**
     * Check if the sender's last match was with the same opponent
     */
    private function wasLastMatchWithSameOpponent(Rating $senderRating, Rating $receiverRating, League $league): bool
    {
        $lastMatch = MatchGame::where('league_id', $league->id)
            ->where('status', GameStatus::COMPLETED)
            ->where(function ($query) use ($senderRating) {
                $query
                    ->where('first_rating_id', $senderRating->id)
                    ->orWhere('second_rating_id', $senderRating->id)
                ;
            })
            ->orderBy('finished_at', 'desc')
            ->first()
        ;

        if (!$lastMatch) {
            return false;
        }

        // Check if the last match was with the intended receiver
        return ($lastMatch->first_rating_id === $receiverRating->id && $lastMatch->second_rating_id === $senderRating->id)
            || ($lastMatch->first_rating_id === $senderRating->id && $lastMatch->second_rating_id === $receiverRating->id);
    }

    /**
     * Check if the two players are within challenge range (±10 positions)
     */
    private function isWithinChallengeRange(Rating $senderRating, Rating $receiverRating): bool
    {
        $positionDifference = abs($senderRating->position - $receiverRating->position);
        return $positionDifference <= 10;
    }

    private function checkAvailability(Rating $rating, League $league): bool
    {
        return !MatchGame::query()
            ->where(static function (Builder $query) use ($rating) {
                $query->where('first_rating_id', $rating->id);
                $query->orWhere('second_rating_id', $rating->id);
            })
            ->where('league_id', $league->id)
            ->whereIn('status', GameStatus::notAllowedToInviteStatuses())
            ->exists()
        ;
    }

    public function accept(User $user, MatchGame $matchGame): bool
    {
        if (
            $user->id === $matchGame->firstRating->user_id
            || $matchGame->status !== GameStatus::PENDING
            || !$this->haveAccessToGame($user, $matchGame)
        ) {
            return false;
        }

        $matchGame->update([
            'status'                 => GameStatus::IN_PROGRESS,
            'invitation_accepted_at' => now(),
        ]);

        return true;
    }

    private function haveAccessToGame(User $user, MatchGame $matchGame): bool
    {
        $userRating = $this->ratingService->getActiveRatingForUserLeague($user, $matchGame->league);

        $isNotExpire = $matchGame->invitation_available_till === null || $matchGame->invitation_available_till->gt(now());
        $isParticipate = $userRating && in_array($userRating->id,
                [$matchGame->first_rating_id, $matchGame->second_rating_id], true);

        return $isNotExpire && $isParticipate;
    }

    /**
     * @throws Throwable
     */
    public function decline(User $user, MatchGame $matchGame): bool
    {
        if (
            $user->id === $matchGame->firstRating->user_id
            || !$this->haveAccessToGame($user, $matchGame)
        ) {
            return false;
        }

        $firstRating = $matchGame->firstRating;
        $winnerRatingInt = $firstRating->rating;
        $secondRating = $matchGame->secondRating;
        $loserRatingInt = $secondRating->rating;

        $newRatings = $this->ratingService->updateRatings($matchGame, $matchGame->firstRating->user_id);

        if (!$matchGame->game->is_multiplayer) {
            $matchGame->update([
                'status'                   => GameStatus::COMPLETED,
                'first_user_score'         => $matchGame->league->max_score,
                'finished_at'              => now(),
                'winner_rating_id'         => $matchGame->first_rating_id,
                'loser_rating_id'          => $matchGame->second_rating_id,
                'rating_change_for_winner' => $newRatings[$firstRating->id] - $winnerRatingInt,
                'rating_change_for_loser'  => $newRatings[$secondRating->id] - $loserRatingInt,
            ]);
        }

        return true;
    }

    /**
     * Handle sending match results with confirmation system
     * @throws Throwable
     */
    public function sendResult(User $user, SendResultDTO $resultDTO): bool
    {
        // Basic validations - no ties allowed, user must have access, status must be valid
        if (
            $resultDTO->first_user_score === $resultDTO->second_user_score
            || !$this->haveAccessToGame($user, $resultDTO->matchGame)
            || !in_array($resultDTO->matchGame->status, [GameStatus::IN_PROGRESS, GameStatus::MUST_BE_CONFIRMED], true)
        ) {
            return false;
        }

        // Determine which player is submitting the result
        $userRating = $this->ratingService->getActiveRatingForUserLeague($user, $resultDTO->matchGame->league);
        if (!$userRating) {
            return false;
        }

        // Determine the winner and loser based on submitted scores
        $firstPlayerWins = $resultDTO->first_user_score > $resultDTO->second_user_score;
        $winnerRatingId = $firstPlayerWins ? $resultDTO->matchGame->first_rating_id : $resultDTO->matchGame->second_rating_id;
        $loserRatingId = $firstPlayerWins ? $resultDTO->matchGame->second_rating_id : $resultDTO->matchGame->first_rating_id;

        // Create a result signature for comparison (format: "first_score-second_score")
        $resultSignature = "$resultDTO->first_user_score-$resultDTO->second_user_score";

        // First submission or disagreement case - store this user's result
        if (
            $resultDTO->matchGame->status === GameStatus::IN_PROGRESS ||
            $resultDTO->matchGame->result_confirmed === null
        ) {
            // First player submitting result
            $resultDTO->matchGame->update([
                'status'            => GameStatus::MUST_BE_CONFIRMED,
                'first_user_score'  => $resultDTO->first_user_score,
                'second_user_score' => $resultDTO->second_user_score,
                'winner_rating_id'  => $winnerRatingId,
                'loser_rating_id'   => $loserRatingId,
                'result_confirmed'  => [
                    [
                        'key'   => $userRating->id,
                        'score' => $resultSignature,
                    ],
                ],
            ]);

            return true;
        }

        // Check if this is the second player submitting and if results match
        $confirmedResults = $resultDTO->matchGame->result_confirmed;

        // Get the other player's rating ID
        $otherRatingId = $userRating->id === $resultDTO->matchGame->first_rating_id
            ? $resultDTO->matchGame->second_rating_id
            : $resultDTO->matchGame->first_rating_id;

        // Find if other player has already submitted a result
        $otherPlayerResult = null;

        foreach ($confirmedResults as $confirmation) {
            if ($confirmation['key'] === $otherRatingId) {
                $otherPlayerResult = $confirmation['score'];
                break;
            }
        }

        // If other player already submitted a result
        if ($otherPlayerResult !== null) {
            // Results match - complete the match
            if ($otherPlayerResult === $resultSignature) {
                // Get the ratings to calculate changes
                $winnerRating = $winnerRatingId === $resultDTO->matchGame->first_rating_id
                    ? $resultDTO->matchGame->firstRating
                    : $resultDTO->matchGame->secondRating;

                $loserRating = $loserRatingId === $resultDTO->matchGame->first_rating_id
                    ? $resultDTO->matchGame->firstRating
                    : $resultDTO->matchGame->secondRating;

                // Calculate rating changes
                $winnerRatingBefore = $winnerRating->rating;
                $loserRatingBefore = $loserRating->rating;
                $newRatings = $this->ratingService->updateRatings($resultDTO->matchGame, $winnerRating->user_id);

                // Complete the match with agreed results
                $resultDTO->matchGame->update([
                    'status'                   => GameStatus::COMPLETED,
                    'finished_at'              => now(),
                    'result_confirmed'         => [
                        [
                            'key'   => $otherRatingId,
                            'score' => $resultSignature,
                        ],
                        [
                            'key'   => $userRating->id,
                            'score' => $resultSignature,
                        ],
                    ],
                    'rating_change_for_winner' => $newRatings[$winnerRating->id] - $winnerRatingBefore,
                    'rating_change_for_loser'  => $newRatings[$loserRating->id] - $loserRatingBefore,
                ]);

                return true;
            }
            // Results don't match - store this player's result, overwriting previous one

            $confirmedResults = [
                [
                    'key'   => $userRating->id,
                    'score' => $resultSignature,
                ],
            ];

            $resultDTO->matchGame->update([
                'status'            => GameStatus::MUST_BE_CONFIRMED,
                'first_user_score'  => $resultDTO->first_user_score,
                'second_user_score' => $resultDTO->second_user_score,
                'winner_rating_id'  => $winnerRatingId,
                'loser_rating_id'   => $loserRatingId,
                'result_confirmed'  => $confirmedResults,
            ]);

            return true;
        }

        // Add this player's result to existing confirmations
        $confirmedResults[] = [
            'key'   => $userRating->id,
            'score' => $resultSignature,
        ];

        $resultDTO->matchGame->update([
            'result_confirmed' => $confirmedResults,
        ]);

        return true;
    }
}
