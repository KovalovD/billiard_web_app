<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\AddPlayerRequest;
use App\Admin\Services\AdminPlayersService;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Core\Http\Resources\UserResource;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

readonly class AdminPlayersController
{
    public function __construct(
        private AdminPlayersService $playersService,
    ) {
    }

    /**
     * Add existing player to a league
     * @admin
     * @throws Throwable
     */
    public function addExistingPlayer(Request $request, League $league): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        $result = $this->playersService->addPlayerToLeague($league, new RegisterDTO([
            'firstname' => $user->firstname,
            'lastname'  => $user->lastname,
            'email'     => $user->email,
            'phone'     => $user->phone,
            'password'  => 'existing_user',
        ]));

        return response()->json([
            'success' => $result['success'],
            'user'    => new UserResource($result['user']),
            'message' => $result['message'],
        ]);
    }

    /**
     * Add a new player to a league with automatic registration
     * @admin
     * @throws Throwable
     */
    public function addNewPlayer(AddPlayerRequest $request, League $league): JsonResponse
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
     * Add existing player to a multiplayer game
     * @admin
     * @throws Throwable
     */
    public function addExistingPlayerToGame(
        Request $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        $result = $this->playersService->addPlayerToGame(
            $league,
            $multiplayerGame,
            new RegisterDTO([
                'firstname' => $user->firstname,
                'lastname'  => $user->lastname,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'password'  => 'existing_user',
            ]),
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
    public function addNewPlayerToGame(
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
