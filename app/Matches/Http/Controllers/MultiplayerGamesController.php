<?php

namespace App\Matches\Http\Controllers;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\Matches\Http\Requests\JoinMultiplayerGameRequest;
use App\Matches\Http\Requests\PerformGameActionRequest;
use App\Matches\Http\Resources\MultiplayerGameResource;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

readonly class MultiplayerGamesController
{

    public function __construct(private MultiplayerGameService $service)
    {
    }

    // Get all multiplayer games for a league
    public function index(League $league): JsonResponse
    {
        return response()->json(MultiplayerGameResource::collection($this->service->getAll($league)));
    }

    // Create a new multiplayer game (admin only)
    public function store(CreateMultiplayerGameRequest $request, League $league): JsonResponse
    {
        return response()->json(
            new MultiplayerGameResource($this->service->create($league, $request->validated())),
        );
    }

    // Get a specific multiplayer game
    public function show(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $multiplayerGame->load(['players.user', 'game']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
    }

    // Join a multiplayer game
    public function join(
        JoinMultiplayerGameRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(
            new MultiplayerGameResource(
                $this->service->join($league, $multiplayerGame, $user),
            ),
        );
    }

    // Leave a game (if it's in registration status)
    public function leave(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->leave($multiplayerGame, $user),
        ));
    }

    // Start the game (admin only)
    public function start(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(new MultiplayerGameResource(
            $this->service->start($multiplayerGame),
        ));
    }

    // Cancel a game (admin only, if it's in registration status)
    public function cancel(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $this->service->cancel($multiplayerGame);

        return response()->json(['message' => 'Game cancelled successfully.']);
    }

    // Set a user as game moderator (can perform actions for all players)
    public function setModerator(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->setModerator($multiplayerGame, $request->user_id, $user),
        ));
    }

    // Perform game actions (update lives, use cards)

    /**
     * @throws Throwable
     */
    public function performAction(
        PerformGameActionRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->performAction(
                $user,
                $multiplayerGame,
                $request->validated('action'),
                $request->validated('target_user_id'),
                $request->validated('card_type'),
                $request->validated('acting_user_id'),
                $request->validated('handicap_action'),
            ),
        ));
    }

    public function finish(League $league, MultiplayerGame $multiplayerGame, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $officialRatingId = $request->input('official_rating_id');
            $this->service->finishGame($multiplayerGame, $user, $officialRatingId);

            return response()->json([
                'message' => 'Game finished successfully.',
                'game'    => new MultiplayerGameResource($multiplayerGame->fresh(['players.user', 'game'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get financial summary for a game
     */
    public function getFinancialSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(
            $this->service->getFinancialSummary($multiplayerGame),
        );
    }

    /**
     * Get rating summary for a game
     */
    public function getRatingSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(
            $this->service->getRatingSummary($multiplayerGame),
        );
    }
}
