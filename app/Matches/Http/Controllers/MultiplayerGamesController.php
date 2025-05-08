<?php

namespace App\Matches\Http\Controllers;

use App\Leagues\Models\League;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
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
    public function start(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $success = $multiplayerGame->startGame();

        if (!$success) {
            return response()->json(['error' => 'Unable to start the game.'], 400);
        }

        $multiplayerGame->load(['players.user']);
        return response()->json(new MultiplayerGameResource($multiplayerGame));
    }

    // Perform game actions (update lives, use cards)
    public function performAction(
        PerformGameActionRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        $user = Auth::user();
        $action = $request->action;
        $targetUserId = $request->target_user_id;

        // Only active games can have actions
        if ($multiplayerGame->status !== 'in_progress') {
            return response()->json(['error' => 'Game is not in progress.'], 400);
        }

        // Get the player records
        $player = $multiplayerGame->players()->where('user_id', $user->id)->first();

        if (!$player || $player->eliminated_at) {
            return response()->json(['error' => 'You are not an active player in this game.'], 400);
        }

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'increment_lives':
                    $this->handleIncrementLives($multiplayerGame, $player, $targetUserId);
                    break;
                case 'decrement_lives':
                    $this->handleDecrementLives($multiplayerGame, $player, $targetUserId);
                    break;
                case 'use_card':
                    $cardType = $request->card_type;
                    $this->handleUseCard($multiplayerGame, $player, $cardType, $targetUserId);
                    break;
                case 'record_turn':
                    $this->handleRecordTurn($multiplayerGame, $player);
                    break;
                default:
                    DB::rollBack();
                    return response()->json(['error' => 'Invalid action.'], 400);
            }

            DB::commit();
            $multiplayerGame->load(['players.user']);
            return response()->json(new MultiplayerGameResource($multiplayerGame));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function handleIncrementLives(
        MultiplayerGame $game,
        MultiplayerGamePlayer $player,
        ?int $targetUserId,
    ): void {
        // Admin can increment anyone's lives, players only themselves
        if (!Auth::user()->is_admin && $player->user_id !== $targetUserId) {
            throw new Exception('You can only increment your own lives.');
        }

        $targetPlayer = $targetUserId
            ? $game->players()->where('user_id', $targetUserId)->first()
            : $player;

        if (!$targetPlayer) {
            throw new Exception('Target player not found.');
        }

        $targetPlayer->incrementLives();

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => Auth::id(),
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
        MultiplayerGamePlayer $player,
        ?int $targetUserId,
    ): void {
        // Admin can decrement anyone's lives, players only themselves
        if (!Auth::user()->is_admin && $player->user_id !== $targetUserId) {
            throw new Exception('You can only decrement your own lives.');
        }

        $targetPlayer = $targetUserId
            ? $game->players()->where('user_id', $targetUserId)->first()
            : $player;

        if (!$targetPlayer) {
            throw new Exception('Target player not found.');
        }

        $oldLives = $targetPlayer->lives;
        $targetPlayer->decrementLives();

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => Auth::id(),
            'action_type'         => 'decrement_lives',
            'action_data'         => [
                'target_user_id' => $targetPlayer->user_id,
                'old_lives'      => $oldLives,
                'new_lives'      => $targetPlayer->lives,
                'eliminated'     => $targetPlayer->lives <= 0,
            ],
            'created_at'          => now(),
        ]);
    }

    private function handleUseCard(
        MultiplayerGame $game,
        MultiplayerGamePlayer $player,
        string $cardType,
        ?int $targetUserId,
    ): void {
        if (!in_array($cardType, ['skip_turn', 'pass_turn', 'hand_shot'])) {
            throw new Exception('Invalid card type.');
        }

        if (!$player->hasCard($cardType)) {
            throw new Exception('You do not have this card.');
        }

        $targetPlayer = null;
        if ($cardType === 'pass_turn' && !$targetUserId) {
            throw new Exception('Target player is required for pass turn card.');
        }

        if ($targetUserId) {
            $targetPlayer = $game->players()->where('user_id', $targetUserId)->first();
            if (!$targetPlayer || $targetPlayer->eliminated_at) {
                throw new Exception('Target player not found or eliminated.');
            }
        }

        // Use the card
        $player->useCard($cardType);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $player->user_id,
            'action_type'         => 'use_card',
            'action_data'         => [
                'card_type'      => $cardType,
                'target_user_id' => $targetUserId,
            ],
            'created_at'          => now(),
        ]);
    }

    private function handleRecordTurn(MultiplayerGame $game, MultiplayerGamePlayer $player): void
    {
        // Record that this player has taken their turn
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $player->user_id,
            'action_type'         => 'turn',
            'created_at'          => now(),
        ]);
    }

    // Leave a game (if it's in registration status)
    public function leave(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
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
    public function cancel(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        if ($multiplayerGame->status !== 'registration') {
            return response()->json(['error' => 'Cannot cancel a game that has already started.'], 400);
        }

        $multiplayerGame->delete();

        return response()->json(['message' => 'Game cancelled successfully.']);
    }
}
