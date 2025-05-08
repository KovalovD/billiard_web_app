<?php

namespace App\Matches\Services;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGameLog;
use App\Matches\Models\MultiplayerGamePlayer;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class MultiplayerGameService
{
    /**
     * Get all multiplayer games for a league
     */
    public function getAll(League $league): Collection
    {
        return MultiplayerGame::where('league_id', $league->id)
            ->with(['players.user', 'game'])
            ->orderBy('created_at', 'desc')
            ->get()
        ;
    }

    /**
     * Join a multiplayer game
     */
    public function join(League $league, MultiplayerGame $game, User $user): MultiplayerGame
    {
        if (!$game->canJoin($user)) {
            throw new RuntimeException('Cannot join this game.');
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
            throw new RuntimeException('You must be an active player in this league to join.');
        }

        // Create player record
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
        ]);

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Create a new multiplayer game
     */
    public function create(League $league, array $data): MultiplayerGame
    {
        // Ensure the game is a multiplayer type
        $game = $league->game;
        if (!$game->is_multiplayer) {
            throw new RuntimeException('This league does not support multiplayer games');
        }

        return MultiplayerGame::create([
            'league_id'              => $league->id,
            'game_id'                => $game->id,
            'name'                   => $data['name'],
            'max_players'            => $data['max_players'] ?? null,
            'registration_ends_at'   => $data['registration_ends_at'] ?? null,
            'status'                 => 'registration',
            'allow_player_targeting' => $data['allow_player_targeting'] ?? false,
            'entrance_fee'           => $data['entrance_fee'] ?? 300,
            'first_place_percent'    => $data['first_place_percent'] ?? 60,
            'second_place_percent'   => $data['second_place_percent'] ?? 20,
            'grand_final_percent'    => $data['grand_final_percent'] ?? 20,
            'penalty_fee'            => $data['penalty_fee'] ?? 50,
        ]);
    }

    /**
     * Leave a multiplayer game
     */
    public function leave(MultiplayerGame $game, User $user): MultiplayerGame
    {
        if ($game->status !== 'registration') {
            throw new RuntimeException('Cannot leave a game that has already started.');
        }

        $player = $game->players()->where('user_id', $user->id)->first();

        if (!$player) {
            throw new RuntimeException('You are not a player in this game.');
        }

        $player->delete();

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Start the game
     */
    public function start(MultiplayerGame $game): MultiplayerGame
    {
        $success = $this->internalStartGame($game);

        if (!$success) {
            throw new RuntimeException('Unable to start the game.');
        }

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Internal method for starting a game
     */
    private function internalStartGame(MultiplayerGame $game): bool
    {
        if ($game->status !== 'registration') {
            return false;
        }

        // Assign random turn orders
        $players = $game->players()->get();
        $playerCount = $players->count();

        if ($playerCount < 2) {
            return false;
        }

        $turnOrders = range(1, $playerCount);
        shuffle($turnOrders);

        // Set initial lives based on player count
        if ($playerCount <= 5) {
            $initialLives = 6;
        } elseif ($playerCount <= 10) {
            $initialLives = 5;
        } elseif ($playerCount <= 14) {
            $initialLives = 4;
        } else {
            $initialLives = 3;
        }

        // Update each player
        foreach ($players as $index => $player) {
            $player->update([
                'turn_order' => $turnOrders[$index],
                'lives'      => $initialLives,
                'cards'      => ['skip_turn' => true, 'pass_turn' => true, 'hand_shot' => true],
            ]);
        }

        // Set the first player in turn order as default moderator if none is set
        $moderatorUserId = $game->moderator_user_id;
        if (!$moderatorUserId) {
            $firstPlayer = $players->sortBy('turn_order')->first();
            if ($firstPlayer) {
                $moderatorUserId = $firstPlayer->user_id;
            }
        }

        $game->update([
            'status'                 => 'in_progress',
            'started_at'             => now(),
            'initial_lives'          => $initialLives,
            'moderator_user_id'      => $moderatorUserId,
            'allow_player_targeting' => $game->allow_player_targeting ?? false,
        ]);

        return true;
    }

    /**
     * Cancel a game
     */
    public function cancel(MultiplayerGame $game): void
    {
        if ($game->status !== 'registration') {
            throw new RuntimeException('Cannot cancel a game that has already started.');
        }

        $game->delete();
    }

    /**
     * Set a user as game moderator
     */
    public function setModerator(MultiplayerGame $game, int $userId, User $currentUser): MultiplayerGame
    {
        // Only admins or the current moderator can set/change moderator
        if (!$currentUser->is_admin && $currentUser->id !== $game->moderator_user_id) {
            throw new RuntimeException('Only admins or the current moderator can change the moderator.');
        }

        // Check if the user is a player in this game
        $playerExists = $game->players()->where('user_id', $userId)->exists();
        if (!$playerExists && !$currentUser->is_admin) {
            throw new RuntimeException('The moderator must be a player in the game.');
        }

        $game->update([
            'moderator_user_id' => $userId,
        ]);

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Perform game actions (update lives, use cards)
     * @throws Throwable
     */
    public function performAction(
        User $user,
        MultiplayerGame $game,
        string $action,
        ?int $targetUserId = null,
        ?int $actingUserId = null,
        ?string $cardType = null,
    ): MultiplayerGame {
        // Only active games can have actions
        if ($game->status !== 'in_progress') {
            throw new RuntimeException('Game is not in progress.');
        }

        // For admin or moderator operations, allow controlling any player
        if ($actingUserId && ($user->is_admin || $user->id === $game->moderator_user_id)) {
            $actingPlayer = $game->players()->where('user_id', $actingUserId)->first();
            if (!$actingPlayer || $actingPlayer->eliminated_at) {
                throw new RuntimeException('Acting player not found or eliminated.');
            }
        } else {
            // For normal user operations, they can only control themselves
            $actingPlayer = $game->players()->where('user_id', $user->id)->first();
            if (!$actingPlayer || $actingPlayer->eliminated_at) {
                throw new RuntimeException('You are not an active player in this game.');
            }
        }

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'increment_lives':
                    $this->handleIncrementLives($game, $actingPlayer, $targetUserId);
                    break;
                case 'decrement_lives':
                    $this->handleDecrementLives($game, $actingPlayer, $targetUserId);
                    break;
                case 'use_card':
                    if (!$cardType) {
                        throw new RuntimeException('Card type is required.');
                    }
                    $this->handleUseCard($game, $actingPlayer, $cardType, $targetUserId);
                    break;
                case 'record_turn':
                    $this->handleRecordTurn($game, $actingPlayer);
                    break;
                default:
                    DB::rollBack();
                    throw new RuntimeException('Invalid action.');
            }

            DB::commit();
            $game->load(['players.user']);
            return $game;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle incrementing lives
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

        $this->incrementPlayerLives($targetPlayer);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'increment_lives',
            'action_data'         => [
                'target_user_id' => $targetPlayer->user_id,
                'new_lives'      => $targetPlayer->lives,
            ],
            'created_at'          => now(),
        ]);
    }

    /**
     * Increment player lives
     */
    public function incrementPlayerLives(MultiplayerGamePlayer $player): void
    {
        $player->increment('lives');
    }

    /**
     * Handle decrementing lives
     */
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
        $this->decrementPlayerLives($targetPlayer);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
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

    /**
     * Decrement player lives and check for elimination
     */
    public function decrementPlayerLives(MultiplayerGamePlayer $player): void
    {
        $player->decrement('lives');

        if ($player->lives <= 0) {
            $this->eliminatePlayer($player->multiplayerGame, $player);
        }
    }

    /**
     * Eliminate a player from the game
     */
    public function eliminatePlayer(MultiplayerGame $game, MultiplayerGamePlayer $player): void
    {
        // Set finish position as the current active players count
        $player->update([
            'eliminated_at'   => now(),
            'finish_position' => $game->activePlayers()->count(),
        ]);

        // Check if game should be completed (only one active player left)
        $activePlayersCount = $game->activePlayers()->count();
        if ($activePlayersCount === 1) {
            // Set the winner (last remaining player)
            $winner = $game->activePlayers()->first();
            if ($winner) {
                $winner->update([
                    'finish_position' => 1,
                    'eliminated_at'   => now(),
                ]);
            }

            // Mark game as completed
            $game->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate prizes and rating points
            $this->calculatePrizes($game);
            $this->calculateRatingPoints($game);
        }
    }

    /**
     * Calculate prize pool for a game
     */
    public function calculatePrizes(MultiplayerGame $game): void
    {
        $totalPlayers = $game->players()->count();
        if ($totalPlayers < 2) {
            return;
        }

        // Calculate total prize pool
        $totalPrizePool = $totalPlayers * $game->entrance_fee;

        // Calculate prize amounts
        $firstPlacePrize = (int) ($totalPrizePool * ($game->first_place_percent / 100));
        $secondPlacePrize = (int) ($totalPrizePool * ($game->second_place_percent / 100));
        $grandFinalFund = $totalPrizePool - $firstPlacePrize - $secondPlacePrize;

        // Save prize pool info
        $game->prize_pool = [
            'total'            => $totalPrizePool,
            'first_place'      => $firstPlacePrize,
            'second_place'     => $secondPlacePrize,
            'grand_final_fund' => $grandFinalFund,
            'players_count'    => $totalPlayers,
        ];
        $game->save();

        // Assign prizes to players
        $winner = $game->players()->where('finish_position', 1)->first();
        $secondPlace = $game->players()->where('finish_position', 2)->first();

        if ($winner) {
            $winner->prize_amount = $firstPlacePrize;
            $winner->save();
        }

        if ($secondPlace) {
            $secondPlace->prize_amount = $secondPlacePrize;
            $secondPlace->save();
        }

        // Calculate penalty fees
        $penaltyCount = (int) floor($totalPlayers / 2);
        $penaltyPlayers = $game
            ->players()
            ->orderByDesc('finish_position')
            ->limit($penaltyCount)
            ->get()
        ;

        foreach ($penaltyPlayers as $player) {
            $player->penalty_paid = true;
            $player->save();
        }
    }

    /**
     * Calculate rating points for all players based on their finish position
     */
    public function calculateRatingPoints(MultiplayerGame $game): void
    {
        $totalPlayers = $game->players()->count();

        // Assign rating points: last position gets 1 point, and each position above gets +1 point
        $players = $game->players()->get();

        // Find the maximum finish position (to handle missing sequential positions)
        $maxPosition = $players->max('finish_position') ?? $totalPlayers;

        foreach ($players as $player) {
            if ($player->finish_position) {
                // Calculate rating points (position from bottom)
                // Last place gets 1 point, second-to-last gets 2, etc.
                $player->rating_points = $maxPosition - $player->finish_position + 1;
            } else {
                // Players without a finish position (e.g., they left before the game ended)
                // get no points
                $player->rating_points = 0;
            }
            $player->save();
        }
    }

    /**
     * Handle using a card
     */
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
        $this->usePlayerCard($actingPlayer, $cardType);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'use_card',
            'action_data'         => [
                'card_type'      => $cardType,
                'target_user_id' => $targetUserId,
            ],
            'created_at'          => now(),
        ]);
    }

    /**
     * Use a player's card
     */
    public function usePlayerCard(MultiplayerGamePlayer $player, string $cardType): void
    {
        if (!$player->hasCard($cardType)) {
            throw new RuntimeException('Player does not have this card.');
        }

        $cards = $player->cards ?? [];
        $cards[$cardType] = false;
        $player->update(['cards' => $cards]);
    }

    /**
     * Handle recording a turn
     */
    private function handleRecordTurn(MultiplayerGame $game, MultiplayerGamePlayer $actingPlayer): void
    {
        // Record that this player has taken their turn
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'turn',
            'created_at'          => now(),
        ]);
    }

    /**
     * Finish the game and apply the rating changes to league ratings
     */
    public function finishGame(MultiplayerGame $game, User $user): void
    {
        // Check if user is admin or moderator
        if (!$user->is_admin && $user->id !== $game->moderator_user_id) {
            throw new RuntimeException('Only admins or the moderator can finish the game.');
        }

        // Game must be in completed status
        if ($game->status !== 'completed') {
            throw new RuntimeException('Game must be completed to finish it.');
        }

        // Apply rating points to league ratings
        $this->applyRatingPointsToLeague($game);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $user->id,
            'action_type'         => 'finish_game',
            'created_at'          => now(),
        ]);

        $game->update(['status' => 'finished']);
    }

    /**
     * Apply rating points from the game to league ratings
     */
    public function applyRatingPointsToLeague(MultiplayerGame $game): void
    {
        // Get the rating service
        $ratingService = app(RatingService::class);

        // Apply rating points using the rating service
        $ratingService->applyRatingPointsForMultiplayerGame($game);
    }

    /**
     * Get financial summary for a game
     */
    public function getFinancialSummary(MultiplayerGame $game): array
    {
        if ($game->status !== 'completed') {
            throw new RuntimeException('Financial summary is only available for completed games.');
        }

        // Make sure prize pool is calculated
        if (!$game->prize_pool) {
            $this->calculatePrizes($game);
            $game->refresh();
        }

        return [
            'entrance_fee'          => $game->entrance_fee,
            'total_players'         => $game->players()->count(),
            'total_prize_pool'      => $game->prize_pool['total'] ?? 0,
            'first_place_prize'     => $game->prize_pool['first_place'] ?? 0,
            'second_place_prize'    => $game->prize_pool['second_place'] ?? 0,
            'grand_final_fund'      => $game->prize_pool['grand_final_fund'] ?? 0,
            'penalty_fee'           => $game->penalty_fee,
            'penalty_players_count' => $game->players()->where('penalty_paid', true)->count(),
            'time_fund_total'       => $game->players()->where('penalty_paid', true)->count() * $game->penalty_fee,
        ];
    }

    /**
     * Get rating summary for a game
     */
    public function getRatingSummary(MultiplayerGame $game): array
    {
        if ($game->status !== 'completed') {
            throw new RuntimeException('Rating summary is only available for completed games.');
        }

        // Make sure rating points are calculated
        if (!$game->players()->where('rating_points', '>', 0)->exists()) {
            $this->calculateRatingPoints($game);
            $game->refresh();
        }

        $playerRatings = $game
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

        return [
            'players'       => $playerRatings,
            'total_players' => $game->players()->count(),
        ];
    }
}
