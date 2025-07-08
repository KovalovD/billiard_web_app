<?php

namespace App\Matches\Services;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGameLog;
use App\Matches\Models\MultiplayerGamePlayer;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\TournamentPlayer;
use App\Tournaments\Services\TournamentService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
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
     * Remove a player from a multiplayer game (admin only)
     */
    public function removePlayer(MultiplayerGame $game, User $user): MultiplayerGame
    {
        if ($game->status !== 'registration') {
            throw new RuntimeException('Cannot remove players from a game that has already started.');
        }

        $player = $game->players()->where('user_id', $user->id)->first();

        if (!$player) {
            throw new RuntimeException('Player is not in this game.');
        }

        $player->delete();

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Join a multiplayer game (only during registration phase)
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

        // Create player record with total_paid set to entrance fee
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
            'total_paid' => $game->entrance_fee,
        ]);

        // Update current prize pool
        $this->updatePrizePool($game);

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Add player during active game (admin only)
     * Admin decides if it's a new player or rebuy
     */
    public function addPlayerDuringGame(
        League $league,
        MultiplayerGame $game,
        User $user,
        int $fee,
        bool $isNewPlayer,
        User $admin,
    ): MultiplayerGame {
        // Check admin permissions
        if (!$admin->is_admin) {
            throw new RuntimeException('Only admins can add players during active games.');
        }

        if (!$game->allow_rebuy) {
            throw new RuntimeException('This game does not allow players to join during play.');
        }

        if ($game->status !== 'in_progress') {
            throw new RuntimeException('Players can only be added during active games.');
        }

        $existingPlayer = $game->players()->where('user_id', $user->id)->first();

        DB::transaction(function () use ($existingPlayer, $game, $user, $fee, $isNewPlayer) {
            if ($existingPlayer && !$isNewPlayer) {
                // Handle as rebuy
                if ($existingPlayer->rebuy_count >= $game->rebuy_rounds) {
                    throw new RuntimeException('Maximum rebuy limit reached for this player.');
                }

                if (!$existingPlayer->eliminated_at) {
                    throw new RuntimeException('Player is still active in the game.');
                }

                // Rebuy existing player
                $existingPlayer->update([
                    'lives'           => $game->initial_lives,
                    'eliminated_at'   => null,
                    'finish_position' => null,
                    'rebuy_count'     => $existingPlayer->rebuy_count + 1,
                    'total_paid'      => $existingPlayer->total_paid + $fee,
                    'is_rebuy'        => true,
                    'last_rebuy_at'   => now(),
                    'cards'           => $this->getInitialCards($game, $existingPlayer),
                ]);

                // Assign random turn order
                $this->assignRandomTurnOrder($game, $existingPlayer);

                // Add rebuy to history
                $this->addRebuyToHistory($game, $user, $fee, $existingPlayer->rebuy_count, 'rebuy');

                // Update rounds played
                $currentRound = $this->getCurrentRound($game);
                $existingPlayer->update(['rounds_played' => $currentRound]);

            } else {
                // Handle as new player joining
                if ($existingPlayer) {
                    throw new RuntimeException('Player already exists in this game. Mark as rebuy instead.');
                }

                $player = MultiplayerGamePlayer::create([
                    'multiplayer_game_id' => $game->id,
                    'user_id'             => $user->id,
                    'lives'               => $game->initial_lives,
                    'joined_at'           => now(),
                    'total_paid'          => $fee,
                    'is_rebuy'            => false,
                    'rebuy_count'         => 0,
                    'rounds_played'       => $this->getCurrentRound($game),
                    'cards'               => $this->getInitialCards($game, null),
                    'game_stats'          => [
                        'shots_taken'  => 0,
                        'balls_potted' => 0,
                        'lives_gained' => 0,
                        'lives_lost'   => 0,
                        'cards_used'   => 0,
                        'turns_played' => 0,
                    ],
                ]);

                // Assign random turn order
                $this->assignRandomTurnOrder($game, $player);

                // Add to history as new player
                $this->addRebuyToHistory($game, $user, $fee, 0, 'new_player');
            }

            // Add lives to all active players if configured
            if ($game->lives_per_new_player > 0) {
                $this->addLivesToAllPlayers($game, $game->lives_per_new_player, $user->id);
            }
        });

        // Update current prize pool
        $this->updatePrizePool($game);

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $admin->id,
            'action_type'         => $isNewPlayer ? 'add_new_player' : 'rebuy_player',
            'action_data'         => [
                'target_user_id'       => $user->id,
                'fee'                  => $fee,
                'lives_per_new_player' => $game->lives_per_new_player,
                'is_new_player'        => $isNewPlayer,
            ],
            'created_at'          => now(),
        ]);

        $game->load(['players.user']);
        return $game;
    }

    /**
     * Get current round based on rebuy history
     */
    private function getCurrentRound(MultiplayerGame $game): int
    {
        $rebuyHistory = collect($game->rebuy_history ?? []);
        $uniqueRounds = $rebuyHistory->pluck('round')->unique()->count();
        return max(1, $uniqueRounds);
    }

    /**
     * Get initial cards for a player
     */
    private function getInitialCards(MultiplayerGame $game, ?MultiplayerGamePlayer $player): array
    {
        $cards = ['skip_turn' => true, 'pass_turn' => true, 'hand_shot' => true];

        if ($player) {
            $division = $game->getDivisionForUser($player);
            if (in_array($division, ['B', 'C'])) {
                $cards['handicap'] = true;
            }
        }

        return $cards;
    }

    /**
     * Assign random turn order to a player
     * @throws RandomException
     */
    private function assignRandomTurnOrder(MultiplayerGame $game, MultiplayerGamePlayer $player): void
    {
        $activePlayers = $game
            ->activePlayers()
            ->where('id', '!=', $player->id)
            ->orderBy('turn_order')
            ->get()
        ;

        if ($activePlayers->isEmpty()) {
            $player->update(['turn_order' => 1]);
            return;
        }

        // Get all existing turn orders
        $existingOrders = $activePlayers->pluck('turn_order')->toArray();

        // Find a random position
        $minOrder = min($existingOrders);
        $maxOrder = max($existingOrders);

        // Generate a random position between existing players
        $randomPosition = random_int($minOrder, $maxOrder + 1);

        // Shift other players if needed
        $game
            ->activePlayers()
            ->where('turn_order', '>=', $randomPosition)
            ->where('id', '!=', $player->id)
            ->increment('turn_order')
        ;

        $player->update(['turn_order' => $randomPosition]);
    }

    /**
     * Add rebuy/new player to game history
     */
    private function addRebuyToHistory(MultiplayerGame $game, User $user, int $amount, int $round, string $type): void
    {
        $history = $game->rebuy_history ?? [];

        $history[] = [
            'user_id'   => $user->id,
            'user_name' => $user->firstname.' '.$user->lastname,
            'amount'    => $amount,
            'timestamp' => now()->toIso8601String(),
            'round'     => $round,
            'type'      => $type, // 'rebuy' or 'new_player'
        ];

        $game->update(['rebuy_history' => $history]);
    }

    /**
     * Add lives to all active players
     */
    private function addLivesToAllPlayers(MultiplayerGame $game, int $livesToAdd, int $excludeUserId): void
    {
        $game
            ->activePlayers()
            ->where('user_id', '!=', $excludeUserId)
            ->increment('lives', $livesToAdd)
        ;

        // Log the action
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $excludeUserId,
            'action_type'         => 'lives_added_to_all',
            'action_data'         => [
                'lives_added' => $livesToAdd,
                'reason'      => 'new_player_joined',
            ],
            'created_at'          => now(),
        ]);
    }

    /**
     * Update current prize pool
     */
    private function updatePrizePool(MultiplayerGame $game): void
    {
        $totalPaid = $game->players()->sum('total_paid');
        $game->update(['current_prize_pool' => $totalPaid]);
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

        // Validate official rating if provided
        if (!empty($data['official_rating_id'])) {
            $officialRating = OfficialRating::find($data['official_rating_id']);
            if (!$officialRating || !$officialRating->is_active) {
                throw new RuntimeException('Selected official rating is not available');
            }

            // Check if rating matches the game type
            if ($officialRating->game_type !== $game->type) {
                throw new RuntimeException('Official rating does not match the game type');
            }
        }

        return MultiplayerGame::create([
            'league_id'                => $league->id,
            'game_id'                  => $game->id,
            'official_rating_id'       => $data['official_rating_id'] ?? null,
            'name'                     => $data['name'],
            'max_players'              => $data['max_players'] ?? null,
            'registration_ends_at'     => $data['registration_ends_at'] ?? null,
            'status'                   => 'registration',
            'allow_player_targeting'   => $data['allow_player_targeting'] ?? false,
            'entrance_fee'             => $data['entrance_fee'] ?? 300,
            'first_place_percent'      => $data['first_place_percent'] ?? 60,
            'second_place_percent'     => $data['second_place_percent'] ?? 20,
            'grand_final_percent'      => $data['grand_final_percent'] ?? 20,
            'penalty_fee'              => $data['penalty_fee'] ?? 50,
            'allow_rebuy'              => $data['allow_rebuy'] ?? false,
            'rebuy_rounds'             => $data['rebuy_rounds'] ?? null,
            'lives_per_new_player'     => $data['lives_per_new_player'] ?? 0,
            'enable_penalties'         => $data['enable_penalties'] ?? false,
            'penalty_rounds_threshold' => $data['penalty_rounds_threshold'] ?? null,
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

        // Update current prize pool
        $this->updatePrizePool($game);

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
        } else {
            $initialLives = 4;
        }

        // Update each player
        foreach ($players as $index => $player) {
            $player->update([
                'turn_order'    => $turnOrders[$index],
                'lives'         => $initialLives,
                'eliminated_at' => null,
                'total_paid'    => $game->entrance_fee,
                'cards'         => $this->getInitialCards($game, $player),
                'rounds_played' => 1, // Starting round 1
                'game_stats'    => [
                    'shots_taken'  => 0,
                    'balls_potted' => 0,
                    'lives_gained' => 0,
                    'lives_lost'   => 0,
                    'cards_used'   => 0,
                    'turns_played' => 0,
                ],
            ]);
        }

        // Set the first player in turn order as default moderator if none is set
        $moderatorUserId = $game->moderator_user_id;
        $firstPlayer = $players->sortBy('turn_order')->first();
        if (!$moderatorUserId && $firstPlayer) {
            $moderatorUserId = $firstPlayer->user_id;
        }

        // Set the first player (lowest turn order) as the current player
        $secondPlayer = $players->sortBy('turn_order')->skip(1)->first();

        $game->update([
            'status'                 => 'in_progress',
            'started_at'             => now(),
            'initial_lives'          => $initialLives,
            'moderator_user_id'      => $moderatorUserId,
            'allow_player_targeting' => $game->allow_player_targeting ?? false,
            'current_player_id' => $firstPlayer->user_id ?? null,
            'next_turn_order'   => $secondPlayer->turn_order ?? null,
        ]);

        // Update current prize pool
        $this->updatePrizePool($game);

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
        ?string $cardType = null,
        ?int $actingUserId = null,
        ?string $handicapAction = null,
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
                    $this->handleUseCard($game, $actingPlayer, $cardType, $targetUserId, $handicapAction);
                    break;
                case 'record_turn':
                    $this->handleRecordTurn($game, $actingPlayer);
                    break;
                case 'set_turn':
                    $this->handleSetTurn($game, $actingPlayer, $targetUserId);
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

        // Update game stats - incrementing lives means player potted the 8-ball
        $this->updatePlayerStats($targetPlayer, 'lives_gained');
        $this->updatePlayerStats($actingPlayer, 'balls_potted'); // Potted 8-ball
        $this->updatePlayerStats($actingPlayer, 'shots_taken'); // Also count as a shot

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

        if ($targetUserId === $game->current_player_id) {
            $this->moveToNextPlayer($game);
        }

        $game->refresh();
    }

    /**
     * Update player statistics
     */
    private function updatePlayerStats(MultiplayerGamePlayer $player, string $stat): void
    {
        $player->refresh();

        $stats = $player->game_stats ?? [
            'shots_taken'  => 0,
            'balls_potted' => 0,
            'lives_gained' => 0,
            'lives_lost'   => 0,
            'cards_used'   => 0,
            'turns_played' => 0,
        ];

        $stats[$stat] = ($stats[$stat] ?? 0) + 1;
        $player->update(['game_stats' => $stats]);
    }

    /**
     * Increment player lives
     */
    public function incrementPlayerLives(MultiplayerGamePlayer $player): void
    {
        $player->increment('lives');
    }

    /**
     * Move to the next player in turn order
     */
    private function moveToNextPlayer(MultiplayerGame $game): void
    {
        // Get active players sorted by turn order
        $activePlayers = $game->activePlayers()->orderBy('turn_order')->get();
        if ($activePlayers->isEmpty()) {
            return;
        }

        // Find current next turn order
        $nextTurnOrder = $game->next_turn_order;

        // Find the player with this turn order
        $nextPlayer = $activePlayers->first(function ($player) use ($nextTurnOrder) {
            return $player->turn_order === $nextTurnOrder;
        });

        // If no player found with next turn order, use the first player
        if (!$nextPlayer) {
            $nextPlayer = $activePlayers->first();
        }

        // Find the player after the next player (becomes the new next player)
        $nextPlayerIndex = $activePlayers->search(function ($player) use ($nextPlayer) {
            return $player->id === $nextPlayer->id;
        });

        $newNextPlayerIndex = ($nextPlayerIndex + 1) % $activePlayers->count();
        $newNextPlayer = $activePlayers[$newNextPlayerIndex];

        // Update the game with new current and next player
        $game->update([
            'current_player_id' => $nextPlayer->user_id,
            'next_turn_order'   => $newNextPlayer->turn_order,
        ]);
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

        // Update game stats
        $this->updatePlayerStats($targetPlayer, 'lives_lost');

        // Only count as shot if the acting player is decrementing their own lives (missed shot)
        // Not when it's from a take_life card (which is handled separately)
        if ($actingPlayer->user_id === $targetPlayer->user_id) {
            $this->updatePlayerStats($actingPlayer, 'shots_taken');
        }

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

        if ($targetUserId === $game->current_player_id) {
            $this->moveToNextPlayer($game);
        }

        $game->refresh();
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
        // Just mark as eliminated, don't set finish position yet
        $player->update([
            'eliminated_at' => now(),
        ]);

        // Check if game should be completed (only one active player left)
        $activePlayersCount = $game->activePlayers()->count();
        if ($activePlayersCount === 1) {
            // Set the winner (last remaining player)
            $winner = $game->activePlayers()->first();
            if ($winner) {
                $winner->update([
                    'eliminated_at' => now()->addSecond(),
                ]);
            }

            // Mark game as completed
            $game->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // Recalculate all finish positions based on elimination time
            $this->recalculateFinishPositions($game);

            // Calculate prizes and rating points
            $this->calculatePrizes($game);
            $this->calculateRatingPoints($game);
            $this->applyPenalties($game);
        }
    }

    /**
     * Recalculate finish positions based on elimination times
     */
    private function recalculateFinishPositions(MultiplayerGame $game): void
    {
        // Get all players ordered by elimination time (latest first = better position)
        $players = $game
            ->players()
            ->whereNotNull('eliminated_at')
            ->orderBy('eliminated_at', 'desc')
            ->get()
        ;

        $position = 1;
        foreach ($players as $player) {
            $player->update(['finish_position' => $position]);
            $position++;
        }

        // Handle any players who weren't eliminated (shouldn't happen, but just in case)
        $nonEliminatedPlayers = $game
            ->players()
            ->whereNull('eliminated_at')
            ->get()
        ;

        foreach ($nonEliminatedPlayers as $player) {
            $player->update(['finish_position' => $position]);
            $position++;
        }
    }

    /**
     * Apply penalties to players who didn't play enough rounds
     */
    private function applyPenalties(MultiplayerGame $game): void
    {
        if (!$game->enable_penalties || !$game->penalty_rounds_threshold) {
            return;
        }

        $minRoundsRequired = (int) ceil($game->penalty_rounds_threshold / 2);

        $game
            ->players()
            ->where('rounds_played', '<', $minRoundsRequired)
            ->where('rebuy_count', '<', $game->rebuy_rounds)
            ->update(['penalty_paid' => true])
        ;
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

        // Use current prize pool instead of calculated from entrance fee
        $totalPrizePool = $game->current_prize_pool;

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

        $game->players()->update(
            [
                'prize_amount' => 0,
            ],
        );

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

    private function handleUseCard(
        MultiplayerGame $game,
        MultiplayerGamePlayer $actingPlayer,
        string $cardType,
        ?int $targetUserId,
        ?string $handicapAction = null,
    ): void {
        // Verify this is the current player's turn (unless admin/moderator)
        if ($game->current_player_id !== $actingPlayer->user_id &&
            !Auth::user()->is_admin &&
            Auth::id() !== $game->moderator_user_id) {
            throw new RuntimeException('It is not your turn.');
        }

        if (!in_array($cardType, ['skip_turn', 'pass_turn', 'hand_shot', 'handicap'])) {
            throw new RuntimeException('Invalid card type.');
        }

        if (!$actingPlayer->hasCard($cardType)) {
            throw new RuntimeException('Player does not have this card.');
        }

        // Handle handicap card special logic
        if ($cardType === 'handicap') {
            if (!$handicapAction) {
                throw new RuntimeException('Handicap action is required for handicap card.');
            }

            if (!in_array($handicapAction, ['skip_turn', 'take_life'])) {
                throw new RuntimeException('Invalid handicap action.');
            }

            $this->handleHandicapCard($game, $actingPlayer, $handicapAction, $targetUserId);
            return;
        }

        // Handle card-specific requirements for non-handicap cards
        $targetPlayer = null;
        $currentPlayerTurnOrder = $game->players()->where('user_id', $actingPlayer->user_id)->first()?->turn_order;

        if ($cardType === 'pass_turn') {
            if (!$targetUserId) {
                throw new RuntimeException('Target player is required for pass turn card.');
            }

            $targetPlayer = $game->players()->where('user_id', $targetUserId)->first();
            if (!$targetPlayer || $targetPlayer->eliminated_at) {
                throw new RuntimeException('Target player not found or eliminated.');
            }
        }

        // Use the card
        $this->usePlayerCard($actingPlayer, $cardType);

        // Update game stats
        $this->updatePlayerStats($actingPlayer, 'cards_used');

        // Log the card usage
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

        // Apply the card effect based on card type
        switch ($cardType) {
            case 'skip_turn':
                // Skip the next player's turn - no shot taken, just skipping
                break;

            case 'pass_turn':
                // Pass turn directly to a specific player
                $this->passTurnToPlayer($game, $targetPlayer, $currentPlayerTurnOrder);
                break;

            case 'hand_shot':
                // Hand shot doesn't affect turn order, just logs the card usage
                break;
        }

        // If the card is not a pass_turn card, log a normal turn for the current player
        if ($cardType !== 'pass_turn') {
            MultiplayerGameLog::create([
                'multiplayer_game_id' => $game->id,
                'user_id'             => $actingPlayer->user_id,
                'action_type'         => 'turn',
                'action_data'         => ['card_used' => $cardType],
                'created_at'          => now(),
            ]);
        }

        // Move to next player unless we're using pass_turn (which already handles this)
        if ($cardType === 'skip_turn') {
            $this->moveToNextPlayer($game);
        }
    }

    /**
     * Handle handicap card actions
     */
    private function handleHandicapCard(
        MultiplayerGame $game,
        MultiplayerGamePlayer $actingPlayer,
        string $handicapAction,
        ?int $targetUserId,
    ): void {
        $targetPlayer = null;
        $currentPlayerTurnOrder = $game->players()->where('user_id', $actingPlayer->user_id)->first()?->turn_order;

        switch ($handicapAction) {
            case 'skip_turn':
                // Just skip turn, no target needed
                break;

            case 'take_life':
                if (!$targetUserId) {
                    throw new RuntimeException('Target player is required for take life action.');
                }

                $targetPlayer = $game->players()->where('user_id', $targetUserId)->first();
                if (!$targetPlayer || $targetPlayer->eliminated_at) {
                    throw new RuntimeException('Target player not found or eliminated.');
                }

                if ($targetPlayer->lives < 3) {
                    throw new RuntimeException('Target player must have at least 3 lives to take a life.');
                }

                // Take a life from target player
                $this->decrementPlayerLives($targetPlayer);

                // Log the life taking action
                MultiplayerGameLog::create([
                    'multiplayer_game_id' => $game->id,
                    'user_id'             => $actingPlayer->user_id,
                    'action_type'         => 'take_life',
                    'action_data'         => [
                        'target_user_id' => $targetPlayer->user_id,
                        'new_lives'      => $targetPlayer->lives,
                    ],
                    'created_at'          => now(),
                ]);
                break;
        }

        // Use the handicap card
        $this->usePlayerCard($actingPlayer, 'handicap');

        // Update game stats
        $this->updatePlayerStats($actingPlayer, 'cards_used');

        // Log the handicap card usage
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'use_card',
            'action_data'         => [
                'card_type'       => 'handicap',
                'handicap_action' => $handicapAction,
                'target_user_id'  => $targetUserId,
            ],
            'created_at'          => now(),
        ]);

        // Handle turn progression based on handicap action
        switch ($handicapAction) {
            case 'skip_turn':
                // Log a turn and move to next player - no shot taken, just skipping
                MultiplayerGameLog::create([
                    'multiplayer_game_id' => $game->id,
                    'user_id'             => $actingPlayer->user_id,
                    'action_type'         => 'turn',
                    'action_data'         => ['handicap_skip_turn' => true],
                    'created_at'          => now(),
                ]);
                $this->moveToNextPlayer($game);
                break;

            case 'pass_turn':
                // Pass turn to target player
                $this->passTurnToPlayer($game, $targetPlayer, $currentPlayerTurnOrder);
                break;

            case 'take_life':
                // Just log a turn, don't change turn order
                MultiplayerGameLog::create([
                    'multiplayer_game_id' => $game->id,
                    'user_id'             => $actingPlayer->user_id,
                    'action_type'         => 'turn',
                    'action_data'         => ['handicap_take_life' => true],
                    'created_at'          => now(),
                ]);
                break;
        }
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
     * Pass turn to a specific player
     */
    private function passTurnToPlayer(
        MultiplayerGame $game,
        MultiplayerGamePlayer $targetPlayer,
        int $currentPlayerTurnOrder,
    ): void {
        // Get active players sorted by turn order
        $activePlayers = $game->activePlayers()->orderBy('turn_order')->get();
        if ($activePlayers->isEmpty()) {
            return;
        }

        // Find the target player index
        $targetIndex = $activePlayers->search(function ($player) use ($targetPlayer) {
            return $player->id === $targetPlayer->id;
        });

        if ($targetIndex === false) {
            // Target player not found in active players
            $this->moveToNextPlayer($game);
            return;
        }

        // Update the game with new current and next player
        $game->update([
            'current_player_id' => $targetPlayer->user_id,
            'next_turn_order'   => $currentPlayerTurnOrder,
        ]);

        // Log a turn for the target player
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $targetPlayer->user_id,
            'action_type'         => 'turn',
            'action_data'         => ['received_turn' => true],
            'created_at'          => now(),
        ]);
    }

    private function handleRecordTurn(MultiplayerGame $game, MultiplayerGamePlayer $actingPlayer): void
    {
        // Verify this is the current player's turn
        if ($game->current_player_id !== $actingPlayer->user_id &&
            !Auth::user()->is_admin &&
            Auth::id() !== $game->moderator_user_id) {
            throw new RuntimeException('It is not your turn.');
        }

        // Update game stats - recording turn means ending turn after potting regular balls
        $this->updatePlayerStats($actingPlayer, 'turns_played');
        $this->updatePlayerStats($actingPlayer, 'shots_taken');

        // Record that this player has taken their turn
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'turn',
            'created_at'          => now(),
        ]);

        // Update to the next player
        $this->moveToNextPlayer($game);
    }

    public function handleSetTurn(MultiplayerGame $game, MultiplayerGamePlayer $actingPlayer, int $targetUserId): void
    {
        $players = $game->activePlayers()->orderBy('turn_order')->get();
        $nextTurnOrder = $players->where('id', $targetUserId)->first()?->turn_order + 1;

        $nextPlayer = $players->where('turn_order', $nextTurnOrder)->first();

        if (!$nextPlayer) {
            $players->first();
        }

        // Update the game with new current and next player
        $game->update([
            'current_player_id' => $targetUserId,
            'next_turn_order'   => $nextPlayer->turn_order,
        ]);

        // Log a turn for the target player
        MultiplayerGameLog::create([
            'multiplayer_game_id' => $game->id,
            'user_id'             => $actingPlayer->user_id,
            'action_type'         => 'set_turn',
            'action_data'         => ['target_user_id' => $targetUserId],
            'created_at'          => now(),
        ]);
    }

    /**
     * Finish the game and apply the rating changes to league ratings
     * @throws Throwable
     */
    public function finishGame(MultiplayerGame $game, User $user, ?int $officialRatingId = null): void
    {
        // Check if user is admin or moderator
        if (!$user->is_admin && $user->id !== $game->moderator_user_id) {
            throw new RuntimeException('Only admins or the moderator can finish the game.');
        }

        // Game must be in completed status
        if ($game->status !== 'completed') {
            throw new RuntimeException('Game must be completed to finish it.');
        }

        // If official rating is provided, create a tournament and link it
        if ($officialRatingId) {
            DB::transaction(static function () use ($game, $officialRatingId) {
                $tournamentService = app(TournamentService::class);
                $tournamentData = [
                    'name'               => $game->name,
                    'status'             => 'completed',
                    'game_id'            => $game->game_id,
                    'start_date'         => $game->started_at ?? now(),
                    'end_date'           => $game->completed_at ?? now(),
                    'prize_pool'         => $game->prize_pool['total'] ?? 0,
                    'official_rating_id' => $officialRatingId,
                ];
                $tournament = $tournamentService->createTournament($tournamentData);

                // Create TournamentPlayers from MultiplayerGamePlayers
                foreach ($game->players as $mpPlayer) {
                    $playerData = [
                        'tournament_id'      => $tournament->id,
                        'user_id'            => $mpPlayer->user_id,
                        'position'           => $mpPlayer->finish_position,
                        'rating_points'      => $mpPlayer->prize_amount,
                        'prize_amount'       => $mpPlayer->prize_amount,
                        'bonus_amount'       => 0,
                        'achievement_amount' => 0,
                        'status'             => 'confirmed',
                        'registered_at'      => $mpPlayer->joined_at,
                        'confirmed_at'       => $mpPlayer->eliminated_at ?? $game->completed_at,
                    ];
                    TournamentPlayer::create($playerData);
                }

                $tournamentService->updateOfficialRatingsFromTournament($tournament);
            });
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
     * @throws Throwable
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

        $penaltyRevenue = $game
                ->players()
                ->where('penalty_paid', true)
                ->count() * $game->penalty_fee;

        return [
            'entrance_fee'          => $game->entrance_fee,
            'total_players'         => $game->players()->count(),
            'total_prize_pool'   => $game->current_prize_pool,
            'first_place_prize'     => $game->prize_pool['first_place'] ?? 0,
            'second_place_prize'    => $game->prize_pool['second_place'] ?? 0,
            'grand_final_fund'      => $game->prize_pool['grand_final_fund'] ?? 0,
            'penalty_fee'           => $game->penalty_fee,
            'penalty_players_count' => $game->players()->where('penalty_paid', true)->count(),
            'time_fund_total'    => $penaltyRevenue,
            'rebuy_history'      => $game->rebuy_history ?? [],
            'total_rebuy_amount' => collect($game->rebuy_history ?? [])->sum('amount'),
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
                    'game_stats'  => $player->game_stats,
                    'rebuy_count' => $player->rebuy_count,
                    'total_paid'  => $player->total_paid,
                ];
            })
        ;

        return [
            'players'       => $playerRatings,
            'total_players' => $game->players()->count(),
        ];
    }
}
