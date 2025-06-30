<?php

namespace App\Players\Http\Controllers;

use App\Core\Models\User;
use App\Players\Http\Requests\PlayerIndexRequest;
use App\Players\Http\Resources\PlayerDetailResource;
use App\Players\Http\Resources\PlayerResource;
use App\Players\Services\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Players
 */
readonly class PlayerController
{
    public function __construct(
        private PlayerService $playerService,
    ) {
    }

    /**
     * Get list of players with filtering and minimal stats
     */
    public function index(PlayerIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $players = $this->playerService->getPlayersWithStats($filters);

        return PlayerResource::collection($players);
    }

    /**
     * Get detailed player information with comprehensive statistics
     */
    public function show(User $player): PlayerDetailResource
    {
        $player->load([
            'homeCity.country',
            'homeClub.city.country',
        ]);

        return new PlayerDetailResource($player);
    }

    /**
     * Get head-to-head statistics between two players
     */
    public function headToHead(User $player1, User $player2): JsonResponse
    {
        if ($player1->id === $player2->id) {
            return response()->json([
                'error' => 'Cannot compare player with themselves',
            ], 400);
        }

        $stats = $this->playerService->getHeadToHeadStats($player1, $player2);

        return response()->json($stats);
    }
}
