<?php

namespace App\Matches\Http\Controllers;

use App\Leagues\Models\League;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\Matches\Http\Requests\FinishGameRequest;
use App\Matches\Http\Requests\JoinMultiplayerGameRequest;
use App\Matches\Http\Requests\PerformGameActionRequest;
use App\Matches\Http\Resources\MultiplayerGameResource;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGameLog;
use App\Matches\Models\MultiplayerGamePlayer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MultiplayerGamesController
{
    // Get all multiplayer games for a league
    public function index(League $league): JsonResponse
    {
        $games = MultiplayerGame::where('league_id', $league->id)
            ->with(['players.user', 'game'])
            ->orderBy('created_at', 'desc')
            ->get()
        ;

        return response()->json(MultiplayerGameResource::collection($games));
    }

    // Create a new multiplayer game (admin only)
    public function store(CreateMultiplayerGameRequest $request, League $league): JsonResponse
    {
        // Ensure the game is a multiplayer type
        $game = $league->game;
        if (!$game->is_multiplayer) {
            return response()->json(['error' => 'This league does not support multiplayer games'], 400);
        }

        $multiplayerGame = MultiplayerGame::create([
            'league_id'            => $league->id,
            'game_id'              => $game->id,
            'name'                 => $request->name,
            'max_players'          => $request->max_players,
            'registration_ends_at' => $request->registration_ends_at,
            'status'               => 'registration',
        ]);

        return response()->json(new MultiplayerGameResource($multiplayerGame));
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
        $user = Auth::user();

        if (!$multiplayerGame->canJoin($user)) {
            return response()->json(['error' => 'Cannot join this game.'], 400);
        }

        // Check if user is in the league
        $isInLeague = $user
            ->activeRatings()
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->where('is_confirmed', true)
            ->exists()
        ;

        if (!$isInLeague) {
            return response()->json(['error' => 'You must be an active player in this league to join.'], 400);
        }

        // Create player record
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
        ]);

        $multiplayerGame->load(['players.user']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
    }

    // Start the game (admin only)
    public function start(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $success = $multiplayerGame->startGame();

        if (!$success) {
            return response()->json(['error' => 'Unable to start the game.'], 400);
        }

        $multiplayerGame->load(['players.user']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
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
        $user = Auth::user();
        $action = $request->action;
        $targetUserId = $request->target_user_id;
        $actingUserId = $request->acting_user_id ?? null;

        // Only active games can have actions
        if ($multiplayerGame->status !== 'in_progress') {
            return response()->json(['error' => 'Game is not in progress.'], 400);
        }

        // For admin or moderator operations, allow controlling any player
        if ($actingUserId && (Auth::user()->is_admin || $user->id === $multiplayerGame->moderator_user_id)) {
            $actingPlayer = $multiplayerGame->players()->where('user_id', $actingUserId)->first();
            if (!$actingPlayer || $actingPlayer->eliminated_at) {
                return response()->json(['error' => 'Acting player not found or eliminated.'], 400);
            }
        } else {
            // For normal user operations, they can only control themselves
            $actingPlayer = $multiplayerGame->players()->where('user_id', $user->id)->first();
            if (!$actingPlayer || $actingPlayer->eliminated_at) {
                return response()->json(['error' => 'You are not an active player in this game.'], 400);
            }
        }

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'increment_lives':
                    $this->handleIncrementLives($multiplayerGame, $actingPlayer, $targetUserId);
                    break;
                case 'decrement_lives':
                    $this->handleDecrementLives($multiplayerGame, $actingPlayer, $targetUserId);
                    break;
                case 'use_card':
                    $cardType = $request->card_type;
                    $this->handleUseCard($multiplayerGame, $actingPlayer, $cardType, $targetUserId);
                    break;
                case 'record_turn':
                    $this->handleRecordTurn($multiplayerGame, $actingPlayer);
                    break;
                default:
                    DB::rollBack();
                    return response()->json(['error' => 'Invalid action.'], 400);
            }

            DB::commit();
            $multiplayerGame->load(['players.user']);
            return response()->json(new MultiplayerGameResource($multiplayerGame));
        } catch (Exception|Throwable $e) {
        }
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 400);
    }

    /**
     * @throws Exception
     */
    private function handleIncrementLives(
        MultiplayerGame $game,
        MultiplayerGamePlayer $actingPlayer,
        ?int $targetUserId,
    ): void {
        // Admin/moderator can increment anyone's lives
        // Regular players only themselves or if targeting is explicitly allowed
        if (
            $actingPlayer->user_id !== $targetUserId
            && !$game->allow_player_targeting
            && !Auth::user()->is_admin
            && Auth::id() !== $game->moderator_user_id
        ) {
            throw new RuntimeException('You can only increment your own lives.');
        }

        $targetPlayer = $targetUserId
            ? $game->players()->where('user_id', $targetUserId)->first()
            : $actingPlayer;

        if (!$targetPlayer) {
            throw new RuntimeException('Target player not found.');
        }

        $targetPlayer->incrementLives();

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id' => $actingPlayer->user_id,
            'action_type'         => 'increment_lives',
            'action_data'         => [
                'target_user_id' => $targetPlayer->user_id,
                'new_lives'      => $targetPlayer->lives,
            ],
            'created_at'          => now(),
        ]);
    }

    private function handleDecrementLives(
        MultiplayerGame $game,
        MultiplayerGamePlayer $actingPlayer,
        ?int $targetUserId,
    ): void {
        // Admin/moderator can decrement anyone's lives
        // Regular players only themselves or if targeting is explicitly allowed
        if (
            $actingPlayer->user_id !== $targetUserId
            && !$game->allow_player_targeting
            && !Auth::user()->is_admin
            && Auth::id() !== $game->moderator_user_id
        ) {
            throw new RuntimeException('You can only decrement your own lives.');
        }

        $targetPlayer = $targetUserId
            ? $game->players()->where('user_id', $targetUserId)->first()
            : $actingPlayer;

        if (!$targetPlayer) {
            throw new RuntimeException('Target player not found.');
        }

        $oldLives = $targetPlayer->lives;
        $targetPlayer->decrementLives();

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id' => $actingPlayer->user_id,
            'action_type'         => 'decrement_lives',
            'action_data'         => [
                'target_user_id' => $targetPlayer->user_id,
                'old_lives'      => $oldLives,
                'new_lives'      => $targetPlayer->lives,
                'eliminated'     => $targetPlayer->lives <= 0,
            ],
            'created_at'          => now(),
        ]);

        // Check if the game status changed to completed during decrement operation
        // If so, reload the game
        if ($game->status !== 'completed' && $game->fresh()->status === 'completed') {
            $game->refresh();
        }
    }

    private function handleUseCard(
        MultiplayerGame $game,
        MultiplayerGamePlayer $actingPlayer,
        string $cardType,
        ?int $targetUserId,
    ): void {
        if (!in_array($cardType, ['skip_turn', 'pass_turn', 'hand_shot'])) {
            throw new RuntimeException('Invalid card type.');
        }

        if (!$actingPlayer->hasCard($cardType)) {
            throw new RuntimeException('Player does not have this card.');
        }

        if ($cardType === 'pass_turn' && !$targetUserId) {
            throw new RuntimeException('Target player is required for pass turn card.');
        }

        if ($targetUserId) {
            $targetPlayer = $game->players()->where('user_id', $targetUserId)->first();
            if (!$targetPlayer || $targetPlayer->eliminated_at) {
                throw new RuntimeException('Target player not found or eliminated.');
            }
        }

        // Use the card
        $actingPlayer->useCard($cardType);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id' => $actingPlayer->user_id,
            'action_type'         => 'use_card',
            'action_data'         => [
                'card_type'      => $cardType,
                'target_user_id' => $targetUserId,
            ],
            'created_at'          => now(),
        ]);
    }

    private function handleRecordTurn(MultiplayerGame $game, MultiplayerGamePlayer $actingPlayer): void
    {
        // Record that this player has taken their turn
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id' => $actingPlayer->user_id,
            'action_type'         => 'turn',
            'created_at'          => now(),
        ]);
    }

    // Leave a game (if it's in registration status)
    public function leave(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $user = Auth::user();

        if ($multiplayerGame->status !== 'registration') {
            return response()->json(['error' => 'Cannot leave a game that has already started.'], 400);
        }

        $player = $multiplayerGame->players()->where('user_id', $user->id)->first();

        if (!$player) {
            return response()->json(['error' => 'You are not a player in this game.'], 400);
        }

        $player->delete();

        $multiplayerGame->load(['players.user']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
    }

    // Cancel a game (admin only, if it's in registration status)
    public function cancel(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        if ($multiplayerGame->status !== 'registration') {
            return response()->json(['error' => 'Cannot cancel a game that has already started.'], 400);
        }

        $multiplayerGame->delete();

        return response()->json(['message' => 'Game cancelled successfully.']);
    }

    /**
     * @throws Throwable
     */
    public function finishGame(
        FinishGameRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        if ($multiplayerGame->status !== 'in_progress') {
            return response()->json(['error' => 'Can only finish games that are in progress.'], 400);
        }

        try {
            DB::beginTransaction();

            // Process player positions
            $positions = $request->positions;
            $activePlayers = $multiplayerGame->activePlayers()->get();

            foreach ($positions as $position) {
                $player = $activePlayers->firstWhere('id', $position['player_id']);
                if (!$player) {
                    throw new RuntimeException("Player with ID {$position['player_id']} not found or already eliminated.");
                }

                $player->update([
                    'finish_position' => $position['position'],
                    'eliminated_at'   => $position['position'] > 1 ? now() : null,
                ]);
            }

            // Update game status
            $multiplayerGame->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate prizes and rating points
            $multiplayerGame->calculatePrizes();
            $multiplayerGame->calculateRatingPoints();

            // Log the finish action
            MultiplayerGameLog::create([
                'multiplayer_game_id' => $multiplayerGame->id,
                'user_id'             => Auth::id(),
                'action_type'         => 'finish_game',
                'action_data'         => [
                    'positions' => $positions,
                ],
                'created_at'          => now(),
            ]);

            DB::commit();

            $multiplayerGame->load(['players.user']);
            return response()->json(new MultiplayerGameResource($multiplayerGame));
        } catch (Exception|Throwable $e) {
        }
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 400);
    }

    // Set a user as game moderator (can perform actions for all players)
    public function setModerator(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        // Only admins or the current moderator can set/change moderator
        if (!Auth::user()->is_admin && Auth::id() !== $multiplayerGame->moderator_user_id) {
            return response()->json(['error' => 'Only admins or the current moderator can change the moderator.'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if the user is a player in this game
        $playerExists = $multiplayerGame->players()->where('user_id', $request->user_id)->exists();
        if (!$playerExists && !Auth::user()->is_admin) {
            return response()->json(['error' => 'The moderator must be a player in the game.'], 400);
        }

        $multiplayerGame->update([
            'moderator_user_id' => $request->user_id,
        ]);

        $multiplayerGame->load(['players.user']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
    }

    /**
     * Get financial summary for a game
     */
    public function getFinancialSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        if ($multiplayerGame->status !== 'completed') {
            return response()->json([
                'message' => 'Financial summary is only available for completed games.',
            ], 400);
        }

        // Make sure prize pool is calculated
        if (!$multiplayerGame->prize_pool) {
            $multiplayerGame->calculatePrizes();
        }

        return response()->json([
            'entrance_fee'          => $multiplayerGame->entrance_fee,
            'total_players'         => $multiplayerGame->players()->count(),
            'total_prize_pool'      => $multiplayerGame->prize_pool['total'] ?? 0,
            'first_place_prize'     => $multiplayerGame->prize_pool['first_place'] ?? 0,
            'second_place_prize'    => $multiplayerGame->prize_pool['second_place'] ?? 0,
            'grand_final_fund'      => $multiplayerGame->prize_pool['grand_final_fund'] ?? 0,
            'penalty_fee'           => $multiplayerGame->penalty_fee,
            'penalty_players_count' => $multiplayerGame->players()->where('penalty_paid', true)->count(),
            'time_fund_total'       => $multiplayerGame->players()->where('penalty_paid',
                    true)->count() * $multiplayerGame->penalty_fee,
        ]);
    }

    /**
     * Get rating summary for a game
     */
    public function getRatingSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        if ($multiplayerGame->status !== 'completed') {
            return response()->json([
                'message' => 'Rating summary is only available for completed games.',
            ], 400);
        }

        // Make sure rating points are calculated
        if (!$multiplayerGame->players()->where('rating_points', '>', 0)->exists()) {
            $multiplayerGame->calculateRatingPoints();
        }

        $playerRatings = $multiplayerGame
            ->players()
            ->whereNotNull('finish_position')
            ->with('user')
            ->orderBy('finish_position')
            ->get()
            ->map(function ($player) {
                return [
                    'player_id'       => $player->id,
                    'user'            => [
                        'id'        => $player->user->id,
                        'firstname' => $player->user->firstname,
                        'lastname'  => $player->user->lastname,
                    ],
                    'finish_position' => $player->finish_position,
                    'rating_points'   => $player->rating_points,
                ];
            })
        ;

        return response()->json([
            'players'       => $playerRatings,
            'total_players' => $multiplayerGame->players()->count(),
        ]);
    }
}
