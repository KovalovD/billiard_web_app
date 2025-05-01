<?php

namespace App\Matches\Http\Controllers;

use App\Core\Models\User;
use App\Leagues\DataTransferObjects\SendGameDTO;
use App\Leagues\DataTransferObjects\SendResultDTO;
use App\Leagues\Http\Requests\SendGameRequest;
use App\Leagues\Http\Requests\SendResultRequest;
use App\Leagues\Models\League;
use App\Leagues\Services\MatchGamesService;
use App\Matches\Models\MatchGame;
use Auth;
use Throwable;

/**
 * @group MatchGames
 */
readonly class MatchGamesController
{
    public function __construct(
        private MatchGamesService $matchGamesService,
    ) {
    }

    /**
     * Send a challenge to the player
     * @authenticated
     */
    public function sendMatchGame(League $league, User $user, SendGameRequest $request): bool
    {
        $array = array_merge([
            'sender'   => Auth::user(),
            'receiver' => $user,
            'league'   => $league,
        ], $request->validated());

        return $this->matchGamesService->send(SendGameDTO::fromArray($array));
    }

    /**
     * Accept the challenge
     * @authenticated
     */
    public function acceptMatch(League $league, MatchGame $matchGame): bool
    {
        return $this->matchGamesService->accept(Auth::user(), $matchGame);
    }

    /**
     * Decline the challenge
     * @authenticated
     * @throws Throwable
     */
    public function declineMatch(League $league, MatchGame $matchGame): bool
    {
        return $this->matchGamesService->decline(Auth::user(), $matchGame);
    }

    /**
     * Send Result of a game match
     * @authenticated
     * @throws Throwable
     */
    public function sendResult(League $league, MatchGame $matchGame, SendResultRequest $request): bool
    {
        return $this->matchGamesService->sendResult(Auth::user(), SendResultDTO::fromArray([
            'first_user_score'  => $request->validated('first_user_score'),
            'second_user_score' => $request->validated('second_user_score'),
            'matchGame'         => $matchGame,
        ]));
    }
}
