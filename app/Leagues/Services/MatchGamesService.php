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
        $senderRating = $this->ratingService->getActiveRatingForUserLeague(
            $sendGameDTO->sender,
            $sendGameDTO->league,
        );
        $receiverRating = $this->ratingService->getActiveRatingForUserLeague(
            $sendGameDTO->receiver,
            $sendGameDTO->league,
        );

        if (
            $senderRating
            && $receiverRating
            && $senderRating->id !== $receiverRating->id
            && $this->checkAvailability($receiverRating, $sendGameDTO->league)
            && $this->checkAvailability($senderRating, $sendGameDTO->league)
        ) {
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
                'status'                    => GameStatus::PENDING,
                'invitation_sent_at'        => now(),
                'invitation_available_till' => now()->addDays($sendGameDTO->league->invite_days_expire),
            ]);

            return true;
        }

        return false;
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

        $isNotExpire = $matchGame->invitation_available_till !== null || $matchGame->invitation_available_till->gt($matchGame->invitation_sent_at);
        $isParticipate = $userRating && in_array($userRating, $matchGame->getRatings());

        return $isNotExpire && $isParticipate;
    }

    /**
     * @throws Throwable
     */
    public function decline(User $user, MatchGame $matchGame): bool
    {
        if (
            $user->id === $matchGame->firstRating->user_id
            || $matchGame->status !== GameStatus::PENDING
            || !$this->haveAccessToGame($user, $matchGame)
        ) {
            return false;
        }

        $firstRating = $matchGame->firstRating;
        $secondRating = $matchGame->secondRating;

        $newRatings = $this->ratingService->updateRatings($matchGame, $matchGame->firstRating->user_id);

        if (!$matchGame->game->is_multiplayer) {
            $matchGame->update([
                'status'                   => GameStatus::COMPLETED,
                'first_user_score'         => $matchGame->league->max_score,
                'finished_at'              => now(),
                'winner_rating_id'         => $matchGame->first_rating_id,
                'loser_rating_id'          => $matchGame->second_rating_id,
                'rating_change_for_winner' => $newRatings[$firstRating->id] - $firstRating->rating,
                'rating_change_for_loser'  => $newRatings[$secondRating->id] - $secondRating->rating,
            ]);
        }

        return true;
    }

    /**
     * @throws Throwable
     */
    public function sendResult(User $user, SendResultDTO $resultDTO): bool
    {
        if (
            $resultDTO->matchGame->status !== GameStatus::IN_PROGRESS
            || $resultDTO->first_user_score === $resultDTO->second_user_score
            || !$this->haveAccessToGame($user, $resultDTO->matchGame)
        ) {
            return false;
        }

        $winnerRating = $resultDTO->first_user_score > $resultDTO->second_user_score
            ? $resultDTO->matchGame->firstRating
            : $resultDTO->matchGame->secondRating;
        $winnerRatingInt = $winnerRating->rating;

        $loserRating = $winnerRating === $resultDTO->matchGame->firstRating
            ? $resultDTO->matchGame->secondRating
            : $resultDTO->matchGame->firstRating;
        $loserRatingInt = $loserRating->rating;

        $newRatings = $this->ratingService->updateRatings($resultDTO->matchGame, $winnerRating->user_id);

        $league = $resultDTO->matchGame->league;

        if (!$resultDTO->matchGame->game->is_multiplayer) {
            $resultDTO->matchGame->update([
                'status'                   => GameStatus::COMPLETED,
                'first_user_score'         => $league->max_score < $resultDTO->first_user_score ? $league->max_score : $resultDTO->first_user_score,
                'second_user_score'        => $league->max_score < $resultDTO->second_user_score ? $league->max_score : $resultDTO->second_user_score,
                'finished_at'              => now(),
                'winner_rating_id'         => $winnerRating->id,
                'loser_rating_id'          => $loserRating->id,
                'rating_change_for_winner' => $newRatings[$winnerRating->id] - $winnerRatingInt,
                'rating_change_for_loser'  => $newRatings[$loserRating->id] - $loserRatingInt,
            ]);
        }

        return true;
    }
}
