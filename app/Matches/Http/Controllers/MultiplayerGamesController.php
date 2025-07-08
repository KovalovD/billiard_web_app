<?php

namespace App\Matches\Http\Controllers;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\Matches\Http\Requests\JoinMultiplayerGameRequest;
use App\Matches\Http\Requests\PerformGameActionRequest;
use App\Matches\Http\Requests\RebuyPlayerRequest;
use App\Matches\Http\Resources\MultiplayerGameResource;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

readonly class MultiplayerGamesController
{

    public function __construct(private MultiplayerGameService $service)
    {
    }

    // Get all multiplayer games for a league
    public function index(League $league): JsonResponse
    {
        return response()->json(MultiplayerGameResource::collection($this->service->getAll($league)));
    }

    // Create a new multiplayer game (admin only)
    public function store(CreateMultiplayerGameRequest $request, League $league): JsonResponse
    {
        return response()->json(
            new MultiplayerGameResource($this->service->create($league, $request->validated())),
        );
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
        /** @var User $user */
        $user = Auth::user();
        return response()->json(
            new MultiplayerGameResource(
                $this->service->join($league, $multiplayerGame, $user),
            ),
        );
    }

    // Leave a game (if it's in registration status)
    public function leave(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->leave($multiplayerGame, $user),
        ));
    }

    // Start the game (admin only)
    public function start(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(new MultiplayerGameResource(
            $this->service->start($multiplayerGame),
        ));
    }

    // Cancel a game (admin only, if it's in registration status)
    public function cancel(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        $this->service->cancel($multiplayerGame);

        return response()->json(['message' => 'Game cancelled successfully.']);
    }

    // Set a user as game moderator (can perform actions for all players)
    public function setModerator(Request $request, League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->setModerator($multiplayerGame, $request->user_id, $user),
        ));
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
        /** @var User $user */
        $user = Auth::user();
        return response()->json(new MultiplayerGameResource(
            $this->service->performAction(
                $user,
                $multiplayerGame,
                $request->validated('action'),
                $request->validated('target_user_id'),
                $request->validated('card_type'),
                $request->validated('acting_user_id'),
                $request->validated('handicap_action'),
            ),
        ));
    }

    public function finish(League $league, MultiplayerGame $multiplayerGame, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $officialRatingId = $request->input('official_rating_id');
            $this->service->finishGame($multiplayerGame, $user, $officialRatingId);

            return response()->json([
                'message' => 'Game finished successfully.',
                'game'    => new MultiplayerGameResource($multiplayerGame->fresh(['players.user', 'game'])),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get financial summary for a game
     */
    public function getFinancialSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(
            $this->service->getFinancialSummary($multiplayerGame),
        );
    }

    /**
     * Get rating summary for a game
     */
    public function getRatingSummary(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        return response()->json(
            $this->service->getRatingSummary($multiplayerGame),
        );
    }

    // Remove a player from game (admin only, if it's in registration status)
    public function removePlayer(League $league, MultiplayerGame $multiplayerGame, User $user): JsonResponse
    {
        return response()->json(new MultiplayerGameResource(
            $this->service->removePlayer($multiplayerGame, $user),
        ));
    }

    /**
     * Add player during game (admin only)
     */
    public function addPlayerDuringGame(
        RebuyPlayerRequest $request,
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        try {
            /** @var User $admin */
            $admin = Auth::user();

            $user = User::find($request->user_id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $game = $this->service->addPlayerDuringGame(
                $league,
                $multiplayerGame,
                $user,
                $request->rebuy_fee,
                $request->is_new_player,
                $admin,
            );

            return response()->json([
                'message' => $request->is_new_player ? 'New player added successfully' : 'Player rebuy successful',
                'game'    => new MultiplayerGameResource($game),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available players for adding to game (admin only)
     */
    public function getAvailablePlayers(
        League $league,
        MultiplayerGame $multiplayerGame,
    ): JsonResponse {
        try {
            /** @var User $admin */
            $admin = Auth::user();

            if (!$admin->is_admin) {
                return response()->json(['message' => 'Admin access required'], 403);
            }

            // Get all league players
            $leaguePlayers = User::query()
                ->whereHas('activeRatings', function ($query) use ($league) {
                    $query
                        ->where('league_id', $league->id)
                        ->where('is_active', true)
                        ->where('is_confirmed', true)
                    ;
                })
                ->get()
            ;

            // Get current game players
            $gamePlayers = $multiplayerGame->players()->with('user')->get();

            $availablePlayers = $leaguePlayers->map(function ($user) use ($gamePlayers, $multiplayerGame) {
                $existingPlayer = $gamePlayers->firstWhere('user_id', $user->id);

                // Calculate fee
                $baseFee = $multiplayerGame->entrance_fee;
                $rebuyCount = $existingPlayer ? $existingPlayer->rebuy_count : 0;
                $fee = $baseFee + (100 * $rebuyCount);

                $status = 'new';
                $canAdd = true;
                $reason = null;

                if ($existingPlayer) {
                    if ($existingPlayer->eliminated_at) {
                        $status = 'eliminated';
                        if ($rebuyCount >= $multiplayerGame->rebuy_rounds) {
                            $canAdd = false;
                            $reason = 'Maximum rebuy limit reached';
                        }
                    } else {
                        $status = 'active';
                        $canAdd = false;
                        $reason = 'Player is still active';
                    }
                }

                return [
                    'user'          => [
                        'id'    => $user->id,
                        'name'  => $user->firstname.' '.$user->lastname,
                        'email' => $user->email,
                    ],
                    'status'        => $status,
                    'rebuy_count'   => $rebuyCount,
                    'suggested_fee' => $fee,
                    'can_add'       => $canAdd,
                    'reason'        => $reason,
                    'lives'         => $existingPlayer->lives ?? null,
                    'eliminated_at' => $existingPlayer->eliminated_at ?? null,
                ];
            });

            return response()->json([
                'players'   => $availablePlayers,
                'game_info' => [
                    'base_fee'             => $multiplayerGame->entrance_fee,
                    'max_rebuys'           => $multiplayerGame->rebuy_rounds,
                    'lives_per_new_player' => $multiplayerGame->lives_per_new_player,
                    'initial_lives'        => $multiplayerGame->initial_lives,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get game statistics
     */
    public function getGameStatistics(League $league, MultiplayerGame $multiplayerGame): JsonResponse
    {
        try {
            $players = $multiplayerGame->players()->with('user')->get();

            $statistics = [
                'total_shots'        => $players->sum(fn($p) => $p->game_stats['shots_taken'] ?? 0),
                'total_balls_potted' => $players->sum(fn($p) => $p->game_stats['balls_potted'] ?? 0),
                'total_cards_used'   => $players->sum(fn($p) => $p->game_stats['cards_used'] ?? 0),
                'total_turns_played' => $players->sum(fn($p) => $p->game_stats['turns_played'] ?? 0),
                'total_rebuys'       => $players->sum('rebuy_count'),
                'total_rebuy_amount' => collect($multiplayerGame->rebuy_history ?? [])->sum('amount'),
                'rebuy_history'      => $multiplayerGame->rebuy_history ?? [],
                'players'            => $players->map(function ($player) {
                    return [
                        'user'            => [
                            'id'   => $player->user->id,
                            'name' => $player->user->firstname.' '.$player->user->lastname,
                        ],
                        'stats'           => $player->game_stats ?? [
                                'shots_taken'  => 0,
                                'balls_potted' => 0,
                                'lives_gained' => 0,
                                'lives_lost'   => 0,
                                'cards_used'   => 0,
                                'turns_played' => 0,
                            ],
                        'rebuy_count'     => $player->rebuy_count,
                        'total_paid'      => $player->total_paid,
                        'finish_position' => $player->finish_position,
                        'lives'           => $player->lives,
                        'is_active'       => !$player->eliminated_at,
                        'rounds_played'   => $player->rounds_played,
                        'penalty_paid'    => $player->penalty_paid,
                    ];
                }),
            ];

            return response()->json($statistics);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
