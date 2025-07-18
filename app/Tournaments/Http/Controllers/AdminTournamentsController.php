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

    // ─────────────────────────────────────────────────────────────────────────────
    //  CRUD / PLAYERS / STATUS/STAGE
    // ─────────────────────────────────────────────────────────────────────────────

    public function store(CreateTournamentRequest $request): TournamentResource
    {
        $tournament = $this->tournamentService->createTournament($request->validated());

        return new TournamentResource($tournament);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): TournamentResource
    {
        $data = $request->validated();

        // handle rating relation
        if (array_key_exists('official_rating_id', $data)) {
            DB::transaction(function () use ($tournament, $data) {
                $tournament->officialRatings()->detach();

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
            unset($data['official_rating_id'], $data['rating_coefficient']);
        }

        $tournament = $this->tournamentService->updateTournament($tournament, $data);

        return new TournamentResource($tournament);
    }

    public function destroy(Tournament $tournament): JsonResponse
    {
        $this->tournamentService->deleteTournament($tournament);

        return response()->json(['message' => 'Tournament deleted successfully']);
    }

    public function addPlayer(AddTournamentPlayerRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $player = $this->tournamentService->addPlayerToTournament(
                $tournament,
                $request->validated('user_id'),
            );

            return response()->json([
                'success' => true,
                'player'  => new TournamentPlayerResource($player),
                'message' => 'Player added to tournament successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function addNewPlayer(AddPlayerRequest $request, Tournament $tournament): JsonResponse
    {
        $result = $this->tournamentService->addNewPlayerToTournament(
            $tournament,
            RegisterDTO::fromRequest($request),
        );

        return response()->json([
            'success' => $result['success'],
            'user'    => new UserResource($result['user']),
            'player'  => $result['player'] ? new TournamentPlayerResource($result['player']) : null,
            'message' => $result['message'],
        ]);
    }

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

    public function updatePlayer(Request $request, Tournament $tournament, TournamentPlayer $player): JsonResponse
    {
        if ($player->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Player does not belong to this tournament',
            ], 400);
        }

        $validated = $request->validate([
            'position'           => 'nullable|integer|min:1',
            'rating_points'      => 'integer|min:0',
            'prize_amount'       => 'numeric|min:0',
            'bonus_amount'       => 'numeric|min:0',
            'achievement_amount' => 'numeric|min:0',
            'status'             => 'string|in:applied,confirmed,rejected,eliminated,dnf',
            'seed_number'        => 'nullable|integer|min:1',
            'group_code'         => 'nullable|string|max:10',
            'elimination_round'  => 'nullable|string',
        ]);

        $player = $this->tournamentService->updateTournamentPlayer($player, $validated);

        return response()->json([
            'player'  => new TournamentPlayerResource($player),
            'message' => 'Player updated successfully',
        ]);
    }

    public function setResults(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'results'                      => 'required|array',
            'results.*.player_id'          => 'required|integer|exists:tournament_players,id',
            'results.*.position'           => 'required|integer|min:1',
            'results.*.rating_points'      => 'integer|min:0',
            'results.*.prize_amount'       => 'numeric|min:0',
            'results.*.bonus_amount'       => 'numeric|min:0',
            'results.*.achievement_amount' => 'numeric|min:0',
        ]);

        try {
            $this->tournamentService->setTournamentResults($tournament, $validated['results']);

            return response()->json(['message' => 'Tournament results set successfully']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changeStatus(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:upcoming,active,completed,cancelled',
        ]);

        $tournament = $this->tournamentService->changeTournamentStatus($tournament, $validated['status']);

        return response()->json([
            'tournament' => new TournamentResource($tournament),
            'message'    => 'Tournament status updated successfully',
        ]);
    }

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

    // ─────────────────────────────────────────────────────────────────────────────
    //  BRACKET & GROUP GENERATION
    // ─────────────────────────────────────────────────────────────────────────────

    public function generateBracket(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $validated = $request->validate([
                'round_races_to'          => 'nullable|array',
                'round_races_to.*'        => 'integer|min:1|max:99',
                'olympic_phase_size'      => 'nullable|integer|in:2,4,8,16,32,64,128',
                'olympic_has_third_place' => 'nullable|boolean',
            ]);

            if (!empty($validated['round_races_to'])) {
                $tournament->round_races_to = $validated['round_races_to'];
            }

            if (
                $tournament->tournament_type->value === 'olympic_double_elimination' &&
                isset($validated['olympic_phase_size'])
            ) {
                $tournament->olympic_phase_size = $validated['olympic_phase_size'];
                $tournament->olympic_has_third_place = $validated['olympic_has_third_place'] ?? false;
            }

            $tournament->save();

            $bracket = $this->bracketService->generateBracket($tournament->refresh());

            return response()->json([
                'message' => 'Tournament bracket generated successfully',
                'bracket' => $bracket,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function generateGroups(Tournament $tournament): JsonResponse
    {
        try {
            $groups = $this->bracketService->generateGroups($tournament);

            return response()->json([
                'message' => 'Tournament groups generated successfully',
                'groups'  => $groups,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    //  READ-ONLY ENDPOINTS
    // ─────────────────────────────────────────────────────────────────────────────

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

    public function searchUsers(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate(['query' => 'required|string|min:2']);

        $users = $this->tournamentService->searchUsers($validated['query']);

        return UserResource::collection($users);
    }

    public function getStageTransitions(Tournament $tournament): JsonResponse
    {
        $transitions = $this->tournamentService->getStageTransitions($tournament);

        return response()->json($transitions);
    }

    /**
     * Get bracket generation options (supports per-round race_to editing).
     * Обновлено — учитывает фактическое количество раундов «первого этапа»
     * олимпийской двойной сетки согласно новой бизнес-логике.
     */
    public function getBracketOptions(Tournament $tournament): JsonResponse
    {
        $playerCount = $tournament->confirmed_players_count;
        $bracketSize = 2 ** ceil(log($playerCount, 2));

        $base = [
            'tournament_type'  => $tournament->tournament_type,
            'player_count'     => $playerCount,
            'bracket_size'     => $bracketSize,
            'default_races_to' => $tournament->races_to,
        ];

        // ── classic SE/DE ───────────────────────────────────────────────────────
        if (in_array(
            $tournament->tournament_type->value,
            ['single_elimination', 'double_elimination', 'double_elimination_full'],
            true,
        )) {
            $base += $this->buildStdElimRoundList($tournament, $bracketSize);
            return response()->json($base);
        }

        // ── olympic double elimination ─────────────────────────────────────────
        if ($tournament->tournament_type->value === 'olympic_double_elimination') {
            $olSize = $tournament->olympic_phase_size ?? 4;

            // available olympic sizes ≤ playerCount
            $avail = [];
            for ($sz = $bracketSize; $sz >= 2; $sz >>= 1) {
                if ($sz <= $playerCount) {
                    $avail[] = $sz;
                }
            }
            $base['available_olympic_phases'] = $avail;

            // upper rounds actually played
            $upperRounds = (int) log($bracketSize, 2);
            $olRound = $upperRounds - (int) log($olSize / 2, 2) + 1;
            $playedUpperRounds = $olRound - 1;

            $rounds = [];
            for ($i = 1; $i <= $playedUpperRounds; $i++) {
                $rounds["UB_R{$i}"] = $this->getRoundDisplayName($i, $playedUpperRounds);
            }

            // lower rounds actually played
            $ls = $this->getLowerBracketStructureForDisplay($bracketSize);
            $targetLB = $this->calcOlympicTargetLowerRound($ls, $olSize);

            for ($i = 1; $i <= $targetLB; $i++) {
                $rounds["LB_R{$i}"] = $this->getLowerRoundDisplayName($i, $targetLB, $ls[$i] ?? []);
            }

            // olympic single-elim
            $olRounds = (int) log($olSize, 2);
            for ($i = 1; $i <= $olRounds; $i++) {
                $rounds["O_R{$i}"] = 'Olympic '.$this->getRoundDisplayName($i, $olRounds);
            }
            if ($tournament->olympic_has_third_place) {
                $rounds['O_3RD'] = 'Olympic Third Place Match';
            }

            $base['rounds'] = $rounds;
            $base['current_round_races_to'] = $tournament->round_races_to ?? [];

            return response()->json($base);
        }

        // ── fallback for any future types ──────────────────────────────────────
        return response()->json($base);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    //  HELPER METHODS (display & calculations)
    // ─────────────────────────────────────────────────────────────────────────────

    private function buildStdElimRoundList(Tournament $t, int $bracketSize): array
    {
        $upperRounds = (int) log($bracketSize, 2);
        $r = [];

        for ($i = 1; $i <= $upperRounds; $i++) {
            $r["UB_R{$i}"] = $this->getRoundDisplayName($i, $upperRounds);
        }

        if (in_array($t->tournament_type->value, ['double_elimination', 'double_elimination_full'], true)) {
            $lowerRounds = ($upperRounds - 1) * 2;
            $ls = $this->getLowerBracketStructureForDisplay($bracketSize);

            for ($i = 1; $i <= $lowerRounds; $i++) {
                $r["LB_R{$i}"] = $this->getLowerRoundDisplayName($i, $lowerRounds, $ls[$i] ?? []);
            }

            $r['GF'] = 'Grand Finals';
            if ($t->tournament_type->value === 'double_elimination_full') {
                $r['GF_RESET'] = 'Grand Finals Reset';
            }
        }

        if ($t->tournament_type->value === 'single_elimination' && $t->has_third_place_match) {
            $r['3RD'] = 'Third Place Match';
        }

        return [
            'rounds'                 => $r,
            'current_round_races_to' => $t->round_races_to ?? [],
        ];
    }

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

    private function getLowerBracketStructureForDisplay(int $bracketSize): array
    {
        // (версия без изменений — держим синхронной с сервисом)
        return match ($bracketSize) {
            4 => [
                1 => ['matches' => 1, 'type' => 'initial'],
                2 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 2],
            ],
            8 => [
                1 => ['matches' => 2, 'type' => 'initial'], 2 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 1, 'type' => 'regular'],
                4 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 3],
            ],
            16 => [
                1 => ['matches' => 4, 'type' => 'initial'], 2 => ['matches' => 4, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 2, 'type' => 'regular'], 4 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 3],
                5 => ['matches' => 1, 'type' => 'regular'],
                6 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 4],
            ],
            32 => [
                1 => ['matches' => 8, 'type' => 'initial'], 2 => ['matches' => 8, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 4, 'type' => 'regular'], 4 => ['matches' => 4, 'type' => 'drop', 'upper_round' => 3],
                5 => ['matches' => 2, 'type' => 'regular'], 6 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 4],
                7 => ['matches' => 1, 'type' => 'regular'],
                8 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 5],
            ],
            64 => [
                1  => ['matches' => 16, 'type' => 'initial'],
                2  => ['matches' => 16, 'type' => 'drop', 'upper_round' => 2],
                3  => ['matches' => 8, 'type' => 'regular'],
                4  => ['matches' => 8, 'type' => 'drop', 'upper_round' => 3],
                5  => ['matches' => 4, 'type' => 'regular'],
                6  => ['matches' => 4, 'type' => 'drop', 'upper_round' => 4],
                7  => ['matches' => 2, 'type' => 'regular'],
                8  => ['matches' => 2, 'type' => 'drop', 'upper_round' => 5],
                9  => ['matches' => 1, 'type' => 'regular'],
                10 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 6],
            ],
            128 => [
                1  => ['matches' => 32, 'type' => 'initial'],
                2  => ['matches' => 32, 'type' => 'drop', 'upper_round' => 2],
                3  => ['matches' => 16, 'type' => 'regular'],
                4  => ['matches' => 16, 'type' => 'drop', 'upper_round' => 3],
                5  => ['matches' => 8, 'type' => 'regular'],
                6  => ['matches' => 8, 'type' => 'drop', 'upper_round' => 4],
                7  => ['matches' => 4, 'type' => 'regular'],
                8  => ['matches' => 4, 'type' => 'drop', 'upper_round' => 5],
                9  => ['matches' => 2, 'type' => 'regular'],
                10 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 6],
                11 => ['matches' => 1, 'type' => 'regular'],
                12 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 7],
            ],
            default => [],
        };
    }

    private function getLowerRoundDisplayName(int $round, int $total, array $info): string
    {
        $type = $info['type'] ?? 'regular';
        $matches = $info['matches'] ?? 0;

        $stage = match ($matches) {
            1 => 'Finals',
            2 => 'Semifinals',
            4 => 'Quarterfinals',
            8 => 'Round of 16',
            16 => 'Round of 32',
            32 => 'Round of 64',
            default => "Round {$round}",
        };

        return match ($type) {
            'initial', 'regular' => "Lower Bracket {$stage}",
            'drop' => "Lower Bracket {$stage} (w/ drops)",
            'final_drop' => "Lower Bracket Finals (w/ UB Finals loser)",
            default => "Lower Bracket Round {$round}",
        };
    }

    private function calcOlympicTargetLowerRound(array $ls, int $olSize): int
    {
        $adv = $olSize / 2;
        $tar = 0;
        $p = 0;

        foreach ($ls as $round => $info) {
            $p = $info['type'] === 'initial'
                ? $info['matches'] * 2
                : (int) ceil($p / 2);

            if ($p === $adv) {
                $tar = $round;
                if (
                    isset($ls[$tar + 1]) &&
                    $ls[$tar]['type'] !== 'drop' &&
                    $ls[$tar + 1]['type'] === 'drop'
                ) {
                    ++$tar;
                }
                break;
            }
        }

        return $tar;
    }
}
