<?php

namespace App\Matches\Http\Controllers;

use App\Admin\Http\Requests\AddPlayerRequest;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Services\AuthService;
use App\Core\Http\Resources\UserResource;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Services\RatingService;
use App\Matches\Http\Resources\MultiplayerGameResource;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

readonly class AdminMultiplayerGamesController
{
    public function __construct(private MultiplayerGameService $service)
    {
    }

    /**
     * Add existing player to multiplayer game
     * @admin
     */
    public function addExistingPlayer(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $user = User::find($validated['user_id']);
            $updatedGame = $this->service->join($league, $multiplayerGame, $user);

            return response()->json([
                'success' => true,
                'game'    => new MultiplayerGameResource($updatedGame),
                'message' => 'Player added to game successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add new player to multiplayer game with registration
     * @admin
     */
    public function addNewPlayer(
        AddPlayerRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        try {
            $registerDTO = RegisterDTO::fromRequest($request);

            // Get or create user
            $existingUser = User::where('email', $registerDTO->email)->first();

            if (!$existingUser) {
                $authService = app(AuthService::class);
                $result = $authService->register($registerDTO, false);
                $user = $result['user'];
            } else {
                $user = $existingUser;
            }

            // Ensure user is in the league first
            $ratingService = app(RatingService::class);
            $rating = $ratingService->getActiveRatingForUserLeague($user, $league);

            if (!$rating) {
                $success = $ratingService->addPlayer($league, $user);
                if ($success) {
                    $rating = $ratingService->getActiveRatingForUserLeague($user, $league);
                    if ($rating) {
                        $rating->update(['is_confirmed' => true]);
                        $ratingService->rearrangePositions($league->id);
                    }
                }
            } elseif (!$rating->is_confirmed) {
                $rating->update(['is_confirmed' => true]);
                $ratingService->rearrangePositions($league->id);
            }

            // Add to multiplayer game
            $updatedGame = $this->service->join($league, $multiplayerGame, $user);
        } catch (Exception|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'user'    => new UserResource($user),
            'game'    => new MultiplayerGameResource($updatedGame),
            'message' => 'Player added to game successfully',
        ]);
    }
}
