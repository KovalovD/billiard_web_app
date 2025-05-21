<?php

namespace App\Admin\Services;

use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Services\AuthService;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class AdminPlayersService
{
    public function __construct(
        private AuthService $authService,
        private RatingService $ratingService,
        private MultiplayerGameService $gameService,
    ) {
    }

    /**
     * Add a new player to a league with automatic registration
     *
     * @param  League  $league
     * @param  RegisterDTO  $playerData
     * @return array
     * @throws Throwable
     */
    public function addPlayerToLeague(League $league, RegisterDTO $playerData): array
    {
        return DB::transaction(function () use ($league, $playerData) {
            // Get or create the user
            $user = $this->getOrCreateUser($playerData);

            // Add user to league
            $success = $this->ratingService->addPlayer($league, $user);

            // Force confirm the player in the league
            if ($success) {
                $this->confirmPlayerInLeague($user, $league);
            }

            return [
                'success' => $success,
                'user'    => $user,
                'message' => $success
                    ? 'Player added to league successfully'
                    : 'Failed to add player to league',
            ];
        });
    }

    /**
     * Get an existing user or create a new one
     *
     * @param  RegisterDTO  $playerData
     * @return User
     */
    private function getOrCreateUser(RegisterDTO $playerData): User
    {
        // Check if user with this email already exists
        $existingUser = User::where('email', $playerData->email)->first();

        // If user doesn't exist, create new user
        if (!$existingUser) {
            $existingUser = $this->authService->register($playerData, false)['user'];
        }

        return $existingUser;
    }

    /**
     * Set a player as confirmed in a league and rearrange positions
     *
     * @param  User  $user
     * @param  League  $league
     * @return bool
     * @throws Throwable
     */
    private function confirmPlayerInLeague(User $user, League $league): bool
    {
        $rating = $this->ratingService->getActiveRatingForUserLeague($user, $league);

        if (!$rating) {
            return false;
        }

        $rating->update(['is_confirmed' => true]);
        $this->ratingService->rearrangePositions($league->id);

        return true;
    }

    /**
     * Add a new player to a multiplayer game with automatic registration
     *
     * @param  League  $league
     * @param  MultiplayerGame  $multiplayerGame
     * @param  RegisterDTO  $playerData
     * @return array
     * @throws Throwable
     */
    public function addPlayerToGame(League $league, MultiplayerGame $multiplayerGame, RegisterDTO $playerData): array
    {
        return DB::transaction(function () use ($league, $multiplayerGame, $playerData) {
            // Get or create the user
            $user = $this->getOrCreateUser($playerData);

            // First ensure user is in the league and confirmed
            $ratingSuccess = $this->ensureUserInLeague($user, $league);

            // Then add to multiplayer game
            $gameSuccess = false;
            if ($ratingSuccess) {
                try {
                    $this->gameService->join($league, $multiplayerGame, $user);
                    $gameSuccess = true;
                } catch (Throwable) {
                }
            }

            return [
                'success' => $ratingSuccess && $gameSuccess,
                'user'    => $user,
                'message' => $ratingSuccess && $gameSuccess
                    ? 'Player added to game successfully'
                    : 'Failed to add player to game',
            ];
        });
    }

    /**
     * Ensure a user is a confirmed member of a league
     *
     * @param  User  $user
     * @param  League  $league
     * @return bool
     * @throws Throwable
     * @throws Throwable
     * @throws Throwable
     */
    private function ensureUserInLeague(User $user, League $league): bool
    {
        $rating = $this->ratingService->getActiveRatingForUserLeague($user, $league);

        if (!$rating) {
            // Add to league first
            $success = $this->ratingService->addPlayer($league, $user);

            // Force confirm the player in the league
            if ($success) {
                return $this->confirmPlayerInLeague($user, $league);
            }

            return false;
        }

        // If player already in league but not confirmed
        if (!$rating->is_confirmed) {
            return $this->confirmPlayerInLeague($user, $league);
        }

        // Already in league and confirmed
        return true;
    }
}
