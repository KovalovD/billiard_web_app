<?php

namespace App\Leagues\Http\Controllers;

use App\Core\Models\Game;
use App\Leagues\DataTransferObjects\PutLeagueDTO;
use App\Leagues\Http\Requests\PutLeagueRequest;
use App\Leagues\Http\Resources\LeagueResource;
use App\Leagues\Http\Resources\MatchGameResource;
use App\Leagues\Http\Resources\RatingResource;
use App\Leagues\Models\League;
use App\Leagues\Services\LeaguesService;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


/**
 * @group Leagues
 */
readonly class LeaguesController
{
    public function __construct(
        private LeaguesService $leaguesService,
        private RatingService $ratingService,
    ) {
    }

    /**
     * Get a list of leagues
     */
    public function index(): AnonymousResourceCollection
    {
        return LeagueResource::collection($this->leaguesService->index());
    }

    /**
     * Get league by id
     */
    public function show(League $league): LeagueResource
    {
        return new LeagueResource($league);
    }

    /**
     * Update league by id
     * @authenticated
     */
    public function update(PutLeagueRequest $request, League $league): LeagueResource
    {
        return new LeagueResource(
            $this->leaguesService->update(
                PutLeagueDTO::fromRequest($request),
                $league,
            ),
        );
    }

    /**
     * Create a new league
     * @authenticated
     */
    public function store(PutLeagueRequest $request): LeagueResource
    {
        return new LeagueResource(
            $this->leaguesService->store(
                PutLeagueDTO::fromRequest($request),
            ),
        );
    }

    /**
     * Delete league by id
     * @authenticated
     */
    public function destroy(League $league): true
    {
        $this->leaguesService->destroy($league);

        return true;
    }

    /**
     * Get a list of players in a league
     */
    public function players(League $league): AnonymousResourceCollection
    {
        return RatingResource::collection(
            $this->ratingService->getRatingsWithUsers($league),
        );
    }

    /**
     * Get a list of games in a league
     */
    public function games(League $league): AnonymousResourceCollection
    {
        return MatchGameResource::collection(
            $this->leaguesService->games($league),
        );
    }

    /**
     * Load leagues and challenges for logged user
     */
    public function myLeaguesAndChallenges(): JsonResponse
    {
        return response()->json(
            $this->leaguesService->myLeaguesAndChallenges(Auth::user()),
        );
    }

    public function availableGames(): JsonResponse
    {
        return response()->json(
            Game::all(),
        );
    }

    public function loadUserRating(League $league): RatingResource|false
    {
        $userRating = Auth::user()
            ->activeRatings()
            ->with('matchesAsSecondPlayer', 'matchesAsFirstPlayer')
            ->where('league_id', $league->id)
            ->first()
        ;


        if (!$userRating) {
            return false;
        }

        $lastFinishedMatch = $userRating->matchesAsSecondPlayer
            ->merge($userRating->matchesAsFirstPlayer)
            ->where('status', GameStatus::COMPLETED)
            ->first()
        ;


        $lastPlayerRatingId = $lastFinishedMatch?->first_rating_id === $userRating->id
            ? $lastFinishedMatch?->second_rating_id
            : $lastFinishedMatch?->first_rating_id;

        $userRating->setLastPlayerRatingId($lastPlayerRatingId);
        return new RatingResource($userRating);
    }
}
