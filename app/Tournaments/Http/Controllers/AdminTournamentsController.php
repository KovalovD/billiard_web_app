<?php

namespace App\Tournaments\Http\Controllers;

use App\Admin\Http\Requests\AddPlayerRequest;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Http\Requests\AddTournamentPlayerRequest;
use App\Tournaments\Http\Requests\CreateTournamentRequest;
use App\Tournaments\Http\Requests\UpdateTournamentRequest;
use App\Tournaments\Http\Resources\TournamentMatchResource;
use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Http\Resources\TournamentResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use App\Tournaments\Services\TournamentBracketService;
use App\Tournaments\Services\TournamentService;
use App\User\Http\Resources\UserResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @group Admin Tournaments
 */
readonly class AdminTournamentsController
{
    public function __construct(
        private TournamentService $tournamentService,
        private TournamentBracketService $bracketService,
        private OfficialRatingService $officialRatingService,
    ) {
    }

    /**
     * Create tournament
     * @admin
     * @throws Throwable
     */
    public function store(CreateTournamentRequest $request): TournamentResource
    {
        $tournament = $this->tournamentService->createTournament($request->validated());

        return new TournamentResource($tournament);
    }

    /**
     * Update tournament
     * @admin
     * @throws Throwable
     */
    public function update(UpdateTournamentRequest $request, Tournament $tournament): TournamentResource
    {
        $data = $request->validated();

        // Handle official rating association update
        if (array_key_exists('official_rating_id', $data)) {
            DB::transaction(function () use ($tournament, $data) {
                // Remove existing associations
                $tournament->officialRatings()->detach();

                // Add new association if provided
                if (!empty($data['official_rating_id'])) {
                    $rating = $this->officialRatingService
                        ->getAllRatings()
                        ->where('id', $data['official_rating_id'])
                        ->first()
                    ;

                    if ($rating) {
                        $this->officialRatingService->addTournamentToRating(
                            $rating,
                            $tournament->id,
                            $data['rating_coefficient'] ?? 1.0,
                        );
                    }
                }
            });

            // Remove these fields from the update data as they're handled separately
            unset($data['official_rating_id'], $data['rating_coefficient']);
        }

        $tournament = $this->tournamentService->updateTournament($tournament, $data);

        return new TournamentResource($tournament);
    }

    /**
     * Delete tournament
     * @admin
     */
    public function destroy(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->deleteTournament($tournament);

        return response()->json(['message' => 'Tournament deleted successfully']);
    }

    /**
     * Add existing player to tournament
     * @admin
     */
    public function addPlayer(AddTournamentPlayerRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $player = $this->tournamentService->addPlayerToTournament(
                $tournament,
                $request->validated('user_id'),
            );

            return response()->json([
                'success' => true,
                'player' => new TournamentPlayerResource($player),
                'message' => 'Player added to tournament successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add new player to tournament with registration
     * @admin
     * @throws Throwable
     */
    public function addNewPlayer(AddPlayerRequest $request, Tournament $tournament): JsonResponse
    {
        $result = $this->tournamentService->addNewPlayerToTournament(
            $tournament,
            RegisterDTO::fromRequest($request),
        );

        return response()->json([
            'success' => $result['success'],
            'user'   => new UserResource($result['user']),
            'player' => $result['player'] ? new TournamentPlayerResource($result['player']) : null,
            'message' => $result['message'],
        ]);
    }

    /**
     * Remove player from tournament
     * @admin
     */
    public function removePlayer(Tournament $tournament, TournamentPlayer $player): JsonResponse
    {
        if ($player->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Player does not belong to this tournament',
            ], 400);
        }

        $this->tournamentService->removePlayerFromTournament($player);

        return response()->json(['message' => 'Player removed from tournament successfully']);
    }

    /**
     * Update player position, rating points, and financial rewards
     * @admin
     */
    public function updatePlayer(Request $request, Tournament $tournament, TournamentPlayer $player): JsonResponse
    {
        if ($player->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Player does not belong to this tournament',
            ], 400);
        }

        $validated = $request->validate([
            'position'          => 'nullable|integer|min:1',
            'rating_points'     => 'integer|min:0',
            'prize_amount'      => 'numeric|min:0',
            'bonus_amount'      => 'numeric|min:0',
            'achievement_amount' => 'numeric|min:0',
            'status'            => 'string|in:applied,confirmed,rejected,eliminated,dnf',
            'seed_number'       => 'nullable|integer|min:1',
            'group_code'        => 'nullable|string|max:10',
            'elimination_round' => 'nullable|string',
        ]);

        $player = $this->tournamentService->updateTournamentPlayer($player, $validated);

        return response()->json([
            'player' => new TournamentPlayerResource($player),
            'message' => 'Player updated successfully',
        ]);
    }

    /**
     * Set tournament results with bonus and achievement amounts
     * @admin
     */
    public function setResults(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'results'                 => 'required|array',
            'results.*.player_id'     => 'required|integer|exists:tournament_players,id',
            'results.*.position'      => 'required|integer|min:1',
            'results.*.rating_points' => 'integer|min:0',
            'results.*.prize_amount'  => 'numeric|min:0',
            'results.*.bonus_amount'  => 'numeric|min:0',
            'results.*.achievement_amount' => 'numeric|min:0',
        ]);

        try {
            $this->tournamentService->setTournamentResults($tournament, $validated['results']);

            return response()->json(['message' => 'Tournament results set successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Change tournament status
     * @admin
     */
    public function changeStatus(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:upcoming,active,completed,cancelled',
        ]);

        $tournament = $this->tournamentService->changeTournamentStatus($tournament, $validated['status']);

        return response()->json([
            'tournament' => new TournamentResource($tournament),
            'message' => 'Tournament status updated successfully',
        ]);
    }

    /**
     * Change tournament stage
     * @admin
     */
    public function changeStage(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'stage' => 'required|string|in:registration,seeding,group,bracket,completed',
        ]);

        $tournament = $this->tournamentService->changeTournamentStage($tournament, $validated['stage']);

        return response()->json([
            'tournament' => new TournamentResource($tournament),
            'message'    => 'Tournament stage updated successfully',
        ]);
    }

    /**
     * Generate tournament bracket
     * @admin
     */
    public function generateBracket(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            // Validate bracket generation parameters
            $validated = $request->validate([
                'round_races_to'          => 'nullable|array',
                'round_races_to.*'        => 'integer|min:1|max:99',
                'olympic_phase_size'      => 'nullable|integer|in:2,4,8,16,32,64,128',
                'olympic_has_third_place' => 'nullable|boolean',
            ]);

            // Update tournament with bracket settings
            if (!empty($validated['round_races_to'])) {
                $tournament->round_races_to = $validated['round_races_to'];
            }

            if ($tournament->tournament_type === 'olympic_double_elimination' && isset($validated['olympic_phase_size'])) {
                $tournament->olympic_phase_size = $validated['olympic_phase_size'];
                $tournament->olympic_has_third_place = $validated['olympic_has_third_place'] ?? false;
            }

            $tournament->save();

            // Generate bracket
            $bracket = $this->bracketService->generateBracket($tournament);

            return response()->json([
                'message' => 'Tournament bracket generated successfully',
                'bracket' => $bracket,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate tournament groups
     * @admin
     */
    public function generateGroups(Tournament $tournament): JsonResponse
    {
        try {
            $groups = $this->bracketService->generateGroups($tournament);

            return response()->json([
                'message' => 'Tournament groups generated successfully',
                'groups'  => $groups,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get tournament matches
     * @admin
     */
    public function getMatches(Tournament $tournament): AnonymousResourceCollection
    {
        $matches = $tournament
            ->matches()
            ->with(['player1', 'player2', 'winner', 'clubTable'])
            ->orderBy('match_code')
            ->get()
        ;

        return TournamentMatchResource::collection($matches);
    }

    /**
     * Search users for adding to tournament
     * @admin
     */
    public function searchUsers(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $users = $this->tournamentService->searchUsers($validated['query']);

        return UserResource::collection($users);
    }

    /**
     * Get available stage transitions for tournament
     * @admin
     * @throws Exception
     */
    public function getStageTransitions(Tournament $tournament): JsonResponse
    {
        $transitions = $this->tournamentService->getStageTransitions($tournament);

        return response()->json($transitions);
    }

    /**
     * Get bracket generation options
     * @admin
     */
    public function getBracketOptions(Tournament $tournament): JsonResponse
    {
        $playerCount = $tournament->confirmed_players_count;
        $bracketSize = 2 ** ceil(log($playerCount, 2));

        $options = [
            'tournament_type'  => $tournament->tournament_type,
            'player_count'     => $playerCount,
            'bracket_size'     => $bracketSize,
            'default_races_to' => $tournament->races_to,
        ];

        // For Olympic double elimination, calculate available phase sizes
        if ($tournament->tournament_type === 'olympic_double_elimination') {
            $availablePhases = [];
            $size = $bracketSize;
            while ($size >= 2) {
                if ($size <= $playerCount) {
                    $availablePhases[] = $size;
                }
                $size = $size / 2;
            }
            $options['available_olympic_phases'] = $availablePhases;
        }

        // Calculate rounds for races_to configuration
        if (in_array($tournament->tournament_type->value,
            ['single_elimination', 'double_elimination', 'double_elimination_full', 'olympic_double_elimination'])) {
            $upperRounds = (int) log($bracketSize, 2);
            $rounds = [];

            // Upper bracket rounds
            for ($i = 1; $i <= $upperRounds; $i++) {
                $matchesInRound = $bracketSize / (2 ** $i);
                $roundName = $this->getRoundDisplayName($i, $upperRounds);
                $rounds["UB_R{$i}"] = $roundName;
            }

            // Lower bracket rounds for double elimination
            if (in_array($tournament->tournament_type->value, ['double_elimination', 'double_elimination_full'])) {
                $lowerRounds = ($upperRounds - 1) * 2;
                for ($i = 1; $i <= $lowerRounds; $i++) {
                    $rounds["LB_R{$i}"] = "Lower Bracket Round {$i}";
                }
                $rounds['GF'] = 'Grand Finals';
            }

            // Olympic phase rounds
            if ($tournament->tournament_type->value === 'olympic_double_elimination') {
                $rounds['O_R1'] = 'Olympic Phase R1';
                $rounds['O_R2'] = 'Olympic Semifinals';
                $rounds['O_R3'] = 'Olympic Finals';
            }

            // Third place match
            if ($tournament->has_third_place_match ||
                ($tournament->tournament_type->value === 'olympic_double_elimination' && $tournament->olympic_has_third_place)) {
                $rounds['3RD'] = 'Third Place Match';
            }

            $options['rounds'] = $rounds;
            $options['current_round_races_to'] = $tournament->round_races_to ?? [];
        }

        return response()->json($options);
    }

    /**
     * Get round display name
     */
    private function getRoundDisplayName(int $round, int $totalRounds): string
    {
        $remaining = $totalRounds - $round + 1;

        return match ($remaining) {
            1 => 'Finals',
            2 => 'Semifinals',
            3 => 'Quarterfinals',
            4 => 'Round of 16',
            5 => 'Round of 32',
            6 => 'Round of 64',
            7 => 'Round of 128',
            default => "Round {$round}",
        };
    }
}
