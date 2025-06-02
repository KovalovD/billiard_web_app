<?php

namespace App\OfficialRatings\Http\Controllers;

use App\OfficialRatings\Http\Requests\CreateOfficialRatingRequest;
use App\OfficialRatings\Http\Requests\UpdateOfficialRatingRequest;
use App\OfficialRatings\Http\Resources\OfficialRatingResource;
use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * @group Admin Official Ratings
 */
readonly class AdminOfficialRatingsController
{
    public function __construct(
        private OfficialRatingService $officialRatingService,
    ) {
    }

    /**
     * Create official rating
     * @admin
     */
    public function store(CreateOfficialRatingRequest $request): OfficialRatingResource
    {
        $rating = $this->officialRatingService->createRating($request->validated());

        return new OfficialRatingResource($rating);
    }

    /**
     * Update official rating
     * @admin
     */
    public function update(UpdateOfficialRatingRequest $request, OfficialRating $officialRating): OfficialRatingResource
    {
        $rating = $this->officialRatingService->updateRating($officialRating, $request->validated());

        return new OfficialRatingResource($rating);
    }

    /**
     * Delete official rating
     * @admin
     */
    public function destroy(OfficialRating $officialRating): JsonResponse
    {
        $this->officialRatingService->deleteRating($officialRating);

        return response()->json(['message' => 'Official rating deleted successfully']);
    }

    /**
     * Add tournament to rating
     * @admin
     */
    public function addTournament(Request $request, OfficialRating $officialRating): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id'      => 'required|integer|exists:tournaments,id',
            'rating_coefficient' => 'numeric|min:0.1|max:5.0',
            'is_counting'        => 'boolean',
        ]);

        try {
            $this->officialRatingService->addTournamentToRating(
                $officialRating,
                $validated['tournament_id'],
                $validated['rating_coefficient'] ?? 1.0,
                $validated['is_counting'] ?? true,
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament added to rating successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove tournament from rating
     * @admin
     */
    public function removeTournament(OfficialRating $officialRating, Tournament $tournament): JsonResponse
    {
        try {
            $this->officialRatingService->removeTournamentFromRating($officialRating, $tournament);

            return response()->json([
                'success' => true,
                'message' => 'Tournament removed from rating successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add player to rating
     * @admin
     */
    public function addPlayer(Request $request, OfficialRating $officialRating): JsonResponse
    {
        $validated = $request->validate([
            'user_id'        => 'required|integer|exists:users,id',
            'initial_rating' => 'integer|min:0',
        ]);

        try {
            $player = $this->officialRatingService->addPlayerToRating(
                $officialRating,
                $validated['user_id'],
                $validated['initial_rating'] ?? $officialRating->initial_rating,
            );

            return response()->json([
                'success' => true,
                'message' => 'Player added to rating successfully',
                'player'  => $player,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove player from rating
     * @admin
     */
    public function removePlayer(OfficialRating $officialRating, int $userId): JsonResponse
    {
        try {
            $this->officialRatingService->removePlayerFromRating($officialRating, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Player removed from rating successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Recalculate rating positions
     * @admin
     */
    public function recalculatePositions(OfficialRating $officialRating): JsonResponse
    {
        try {
            $this->officialRatingService->recalculatePositions($officialRating);

            return response()->json([
                'success' => true,
                'message' => 'Rating positions recalculated successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update rating from tournament results
     * @admin
     */
    public function updateFromTournament(OfficialRating $officialRating, Tournament $tournament): JsonResponse
    {
        try {
            $updated = $this->officialRatingService->updateRatingFromTournament($officialRating, $tournament);

            return response()->json([
                'success' => true,
                'message'         => "Rating updated successfully. $updated players affected.",
                'players_updated' => $updated,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Recalculate all players from their tournament records (data integrity check)
     * @admin
     */
    public function recalculateFromRecords(OfficialRating $officialRating): JsonResponse
    {
        try {
            $updated = $this->officialRatingService->recalculateAllPlayersFromRecords($officialRating);

            return response()->json([
                'success'         => true,
                'message'         => "All players recalculated from tournament records. $updated players affected.",
                'players_updated' => $updated,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rating integrity report
     * @admin
     */
    public function getIntegrityReport(OfficialRating $officialRating): JsonResponse
    {
        try {
            $report = $this->officialRatingService->getRatingIntegrityReport($officialRating);

            return response()->json([
                'success' => true,
                'data'    => $report,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update tournament coefficient for rating
     * @admin
     */
    public function updateTournamentCoefficient(
        Request $request,
        OfficialRating $officialRating,
        Tournament $tournament,
    ): JsonResponse {
        $validated = $request->validate([
            'rating_coefficient' => 'required|numeric|min:0.1|max:5.0',
            'is_counting'        => 'boolean',
        ]);

        try {
            // Check if tournament is associated with this rating
            $pivot = $officialRating->tournaments()->where('tournament_id', $tournament->id)->first();

            if (!$pivot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament is not associated with this rating',
                ], 400);
            }

            // Update the pivot data
            $officialRating->tournaments()->updateExistingPivot($tournament->id, [
                'rating_coefficient' => $validated['rating_coefficient'],
                'is_counting'        => $validated['is_counting'] ?? $pivot->pivot->is_counting,
            ]);

            // If the tournament is completed and counting, update ratings
            if (($validated['is_counting'] ?? $pivot->pivot->is_counting) && $tournament->isCompleted()) {
                $updated = $this->officialRatingService->updateRatingFromTournament($officialRating, $tournament);

                return response()->json([
                    'success'         => true,
                    'message'         => "Tournament coefficient updated and ratings recalculated. $updated players affected.",
                    'players_updated' => $updated,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament coefficient updated successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk recalculate multiple tournaments
     * @admin
     */
    public function bulkRecalculateTournaments(Request $request, OfficialRating $officialRating): JsonResponse
    {
        $validated = $request->validate([
            'tournament_ids'   => 'required|array|min:1',
            'tournament_ids.*' => 'integer|exists:tournaments,id',
        ]);

        try {
            $totalUpdated = 0;
            $processedTournaments = [];

            foreach ($validated['tournament_ids'] as $tournamentId) {
                $tournament = Tournament::find($tournamentId);

                if (!$tournament || !$tournament->isCompleted()) {
                    continue;
                }

                // Check if tournament is associated with this rating
                if (!$officialRating->tournaments()->where('tournament_id', $tournamentId)->exists()) {
                    continue;
                }

                try {
                    $updated = $this->officialRatingService->updateRatingFromTournament($officialRating, $tournament);
                    $totalUpdated += $updated;
                    $processedTournaments[] = [
                        'id'              => $tournament->id,
                        'name'            => $tournament->name,
                        'players_updated' => $updated,
                    ];
                } catch (Throwable $e) {
                    $processedTournaments[] = [
                        'id'    => $tournament->id,
                        'name'  => $tournament->name,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success'               => true,
                'message'               => "Bulk recalculation completed. $totalUpdated total player updates.",
                'total_players_updated' => $totalUpdated,
                'processed_tournaments' => $processedTournaments,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export rating data for backup/analysis
     * @admin
     */
    public function exportRatingData(OfficialRating $officialRating): JsonResponse
    {
        try {
            $players = $officialRating->players()->with('user')->orderBy('position')->get();

            $exportData = [
                'rating'      => [
                    'id'                 => $officialRating->id,
                    'name'               => $officialRating->name,
                    'description'        => $officialRating->description,
                    'initial_rating'     => $officialRating->initial_rating,
                    'calculation_method' => $officialRating->calculation_method,
                    'exported_at'        => now()->toISOString(),
                ],
                'players'     => $players->map(function (OfficialRatingPlayer $player) {
                    return [
                        'id'                 => $player->id,
                        'user_id'            => $player->user_id,
                        'user_name'          => $player->user->firstname.' '.$player->user->lastname,
                        'user_email'         => $player->user->email,
                        'rating_points'      => $player->rating_points,
                        'position'           => $player->position,
                        'tournaments_played' => $player->tournaments_played,
                        'tournaments_won'    => $player->tournaments_won,
                        'is_active'          => $player->is_active,
                        'tournament_records' => $player->tournament_records,
                        'last_tournament_at' => $player->last_tournament_at?->toISOString(),
                    ];
                }),
                'tournaments' => $officialRating->tournaments()->get()->map(function ($tournament) {
                    return [
                        'id'                 => $tournament->id,
                        'name'               => $tournament->name,
                        'start_date'         => $tournament->start_date?->format('Y-m-d'),
                        'end_date'           => $tournament->end_date?->format('Y-m-d'),
                        'status'             => $tournament->status,
                        'rating_coefficient' => $tournament->pivot->rating_coefficient,
                        'is_counting'        => $tournament->pivot->is_counting,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data'    => $exportData,
                'meta'    => [
                    'players_count'     => $players->count(),
                    'tournaments_count' => $officialRating->tournaments()->count(),
                    'export_timestamp'  => now()->toISOString(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed player tournament history
     * @admin
     */
    public function getPlayerTournamentHistory(OfficialRating $officialRating, int $userId): JsonResponse
    {
        try {
            $player = $officialRating->getPlayerRating($userId);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found in this rating',
                ], 404);
            }

            $tournamentRecords = $player->tournament_records ?? [];

            // Enrich tournament records with tournament details
            $enrichedRecords = collect($tournamentRecords)->map(function ($record) {
                $tournament = Tournament::find($record['tournament_id']);

                return [
                    'tournament_id'     => $record['tournament_id'],
                    'tournament_name'   => $tournament?->name ?? 'Unknown Tournament',
                    'tournament_date'   => $record['tournament_date'],
                    'rating_points'     => $record['rating_points'],
                    'won'               => $record['won'],
                    'added_at'          => $record['added_at'] ?? null,
                    'updated_at'        => $record['updated_at'] ?? null,
                    'tournament_status' => $tournament?->status ?? 'unknown',
                ];
            })->sortByDesc('tournament_date')->values();

            return response()->json([
                'success' => true,
                'data'    => [
                    'player'             => [
                        'id'                 => $player->id,
                        'user_id'            => $player->user_id,
                        'user_name'          => $player->user->firstname.' '.$player->user->lastname,
                        'current_rating'     => $player->rating_points,
                        'position'           => $player->position,
                        'tournaments_played' => $player->tournaments_played,
                        'tournaments_won'    => $player->tournaments_won,
                    ],
                    'tournament_history' => $enrichedRecords,
                    'statistics'         => [
                        'total_tournaments'             => count($tournamentRecords),
                        'total_points_from_tournaments' => collect($tournamentRecords)->sum('rating_points'),
                        'calculated_rating'             => $officialRating->initial_rating + collect($tournamentRecords)->sum('rating_points'),
                        'rating_matches'                => ($officialRating->initial_rating + collect($tournamentRecords)->sum('rating_points')) === $player->rating_points,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset player tournament records (emergency action)
     * @admin
     */
    public function resetPlayerRecords(Request $request, OfficialRating $officialRating, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'confirm_reset' => 'required|boolean|accepted',
        ]);

        try {
            $player = $officialRating->getPlayerRating($userId);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found in this rating',
                ], 404);
            }

            // Reset player to initial state
            $player->update([
                'rating_points'      => $officialRating->initial_rating,
                'tournaments_played' => 0,
                'tournaments_won'    => 0,
                'tournament_records' => [],
                'last_tournament_at' => null,
            ]);

            // Recalculate positions
            $this->officialRatingService->recalculatePositions($officialRating);

            return response()->json([
                'success' => true,
                'message' => 'Player tournament records reset successfully',
                'player'  => [
                    'id'            => $player->id,
                    'rating_points' => $player->rating_points,
                    'position'      => $player->fresh()->position,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
