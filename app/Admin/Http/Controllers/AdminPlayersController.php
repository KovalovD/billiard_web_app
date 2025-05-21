<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\AddPlayerRequest;
use App\Admin\Services\AdminPlayersService;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Core\Http\Resources\UserResource;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Http\JsonResponse;
use Throwable;

readonly class AdminPlayersController
{
    public function __construct(
        private AdminPlayersService $playersService,
    ) {
    }

    /**
     * Add a new player to a league with automatic registration
     * @admin
     * @throws Throwable
     */
    public function addPlayerToLeague(AddPlayerRequest $request, League $league): JsonResponse
    {
        $result = $this->playersService->addPlayerToLeague(
            $league,
            RegisterDTO::fromRequest($request),
        );

        return response()->json([
            'success' => $result['success'],
            'user'    => new UserResource($result['user']),
            'message' => $result['message'],
        ]);
    }

    /**
     * Add a new player to a multiplayer game with automatic registration
     * @admin
     * @throws Throwable
     */
    public function addPlayerToGame(
        AddPlayerRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        $result = $this->playersService->addPlayerToGame(
            $league,
            $multiplayerGame,
            RegisterDTO::fromRequest($request),
        );

        return response()->json([
            'success' => $result['success'],
            'user'    => new UserResource($result['user']),
            'message' => $result['message'],
        ]);
    }
}
