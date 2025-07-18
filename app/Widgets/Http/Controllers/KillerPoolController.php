<?php

namespace App\Widgets\Http\Controllers;

use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGamePlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KillerPoolController
{
    /**
     * Get current game data for streaming widget
     */
    public function getCurrentGame(Request $request, League $league, MultiplayerGame $game): JsonResponse
    {
        // Load the game with all necessary relationships
        $game->load(['players.user', 'game', 'officialRating']);

        // Get active players
        $activePlayers = $game
            ->players()
            ->whereNull('eliminated_at')
            ->with('user')
            ->orderBy('turn_order')
            ->get()
        ;

        // Find current turn player
        $currentTurnPlayer = $activePlayers->firstWhere('user_id', $game->current_player_id);

        // Get eliminated players for context
        $eliminatedPlayers = $game
            ->players()
            ->whereNotNull('eliminated_at')
            ->with('user')
            ->orderBy('finish_position')
            ->get()
        ;

        // Prepare widget data
        $widgetData = [
            'game'               => [
                'id'            => $game->id,
                'name'          => $game->name,
                'status'        => $game->status,
                'initial_lives' => $game->initial_lives,
                'league_name'   => $league->name,
                'game_type'     => $game->game->name ?? null,
            ],
            'current_player'     => $currentTurnPlayer ? [
                'id'                    => $currentTurnPlayer->id,
                'division'              => $game->getDivisionForUser($currentTurnPlayer),
                'user'                  => [
                    'id'        => $currentTurnPlayer->user->id,
                    'firstname' => $currentTurnPlayer->user->firstname,
                    'lastname'  => $currentTurnPlayer->user->lastname,
                    'full_name' => $currentTurnPlayer->user->firstname.' '.$currentTurnPlayer->user->lastname,
                ],
                'lives'                 => $currentTurnPlayer->lives,
                'turn_order'            => $currentTurnPlayer->turn_order,
                'cards'                 => $currentTurnPlayer->cards ?? [],
                'available_cards_count' => $this->countAvailableCards($currentTurnPlayer->cards ?? []),
            ] : null,
            'stats'              => [
                'active_players'     => $activePlayers->count(),
                'total_players'      => $game->players()->count(),
                'eliminated_players' => $eliminatedPlayers->count(),
            ],
            'eliminated_players' => $eliminatedPlayers->map(function ($player) {
                return [
                    'user'            => [
                        'firstname' => $player->user->firstname,
                        'lastname'  => $player->user->lastname,
                        'full_name' => $player->user->firstname.' '.$player->user->lastname,
                    ],
                    'finish_position' => $player->finish_position,
                    'eliminated_at'   => $player->eliminated_at?->format('Y-m-d H:i:s'),
                ];
            })->take(5), // Last 5 eliminated players
            'next_players'       => $this->getNextPlayers($activePlayers, $game->current_player_id, $game),
            'timestamp'          => now()->timestamp,
        ];

        return response()->json($widgetData);
    }

    /**
     * Count available cards for a player
     */
    private function countAvailableCards(array $cards): int
    {
        return collect($cards)->filter(fn($available) => $available === true)->count();
    }

    /**
     * Get next 2-3 players in turn order
     */
    private function getNextPlayers(Collection $activePlayers, int $currentPlayerId, $game): array
    {
        if ($activePlayers->isEmpty()) {
            return [];
        }

        // Find current player index
        $currentIndex = $activePlayers->search(function (MultiplayerGamePlayer $player) use ($currentPlayerId) {
            return $player->user_id === $currentPlayerId;
        });

        if ($currentIndex === false) {
            return [];
        }

        $nextPlayers = [];
        $totalPlayers = $activePlayers->count();

        // Get next 2-3 players
        for ($i = 1; $i <= min(3, $totalPlayers - 1); $i++) {
            $nextIndex = ($currentIndex + $i) % $totalPlayers;
            /** @var MultiplayerGamePlayer $nextPlayer */
            $nextPlayer = $activePlayers[$nextIndex];

            $nextPlayers[] = [
                'division'   => $game->getDivisionForUser($nextPlayer),
                'user'       => [
                    'firstname' => $nextPlayer->user->firstname,
                    'lastname'  => $nextPlayer->user->lastname,
                    'full_name' => $nextPlayer->user->firstname.' '.$nextPlayer->user->lastname,
                ],
                'lives'      => $nextPlayer->lives,
                'turn_order' => $nextPlayer->turn_order,
            ];
        }

        return $nextPlayers;
    }

    /**
     * Get minimal game status for polling
     */
    public function getGameStatus(League $league, MultiplayerGame $game): JsonResponse
    {
        return response()->json([
            'status'               => $game->status,
            'current_player_id'    => $game->current_player_id,
            'updated_at'           => $game->updated_at->timestamp,
            'active_players_count' => $game->players()->whereNull('eliminated_at')->count(),
        ]);
    }
}
