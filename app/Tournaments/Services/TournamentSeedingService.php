<?php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\TournamentStage;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Schema;
use Throwable;

class TournamentSeedingService
{
    /**
     * Generate seeds for tournament players
     * @throws Throwable
     */
    public function generateSeeds(Tournament $tournament, string $method): Collection
    {
        return DB::transaction(function () use ($tournament, $method) {
            if ($tournament->stage !== TournamentStage::SEEDING) {
                throw new RuntimeException('Tournament is not in seeding phase');
            }

            $confirmedPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->get()
            ;

            if ($confirmedPlayers->count() < 2) {
                throw new RuntimeException('At least 2 confirmed players are required for seeding');
            }

            return match ($method) {
                'random' => $this->randomSeeding($confirmedPlayers),
                'rating' => $this->ratingBasedSeeding($tournament, $confirmedPlayers),
                'manual' => $confirmedPlayers,
                default => throw new RuntimeException("Invalid seeding method: $method"),
            };
        });
    }

    /**
     * Random seeding
     */
    private function randomSeeding(Collection $players): Collection
    {
        $shuffled = $players->shuffle();

        foreach ($shuffled as $index => $player) {
            $player->update(['seed_number' => $index + 1]);
        }

        return $shuffled;
    }

    /**
     * Rating-based seeding
     */
    private function ratingBasedSeeding(Tournament $tournament, Collection $players): Collection
    {
        // Get official rating if tournament is associated with one
        $officialRating = $tournament->officialRatings()->first();

        if ($officialRating) {
            // Sort players by their rating in the official rating system
            $playerRatings = [];

            foreach ($players as $player) {
                $ratingPlayer = $officialRating
                    ->players()
                    ->where('user_id', $player->user_id)
                    ->first()
                ;

                $playerRatings[$player->id] = $ratingPlayer->rating_points ?? 0;
            }

            // Sort by rating (descending)
            $sorted = $players->sortByDesc(function ($player) use ($playerRatings) {
                return $playerRatings[$player->id];
            });
        } else {
            // If no official rating, randomize
            $sorted = $players->shuffle();
        }

        foreach ($sorted->values() as $index => $player) {
            $player->update(['seed_number' => $index + 1]);
        }

        return $sorted;
    }

    /**
     * Update seeds manually
     * @throws Throwable
     */
    public function updateSeeds(Tournament $tournament, array $seeds): void
    {
        DB::transaction(static function () use ($tournament, $seeds) {
            if ($tournament->stage !== TournamentStage::SEEDING) {
                throw new RuntimeException('Tournament is not in seeding phase');
            }

            // Validate all players belong to tournament
            $playerIds = array_column($seeds, 'player_id');
            $validPlayers = $tournament
                ->players()
                ->whereIn('id', $playerIds)
                ->where('status', 'confirmed')
                ->count()
            ;

            if ($validPlayers !== count($playerIds)) {
                throw new RuntimeException('Invalid player IDs provided');
            }

            // Validate unique seed numbers
            $seedNumbers = array_column($seeds, 'seed_number');
            if (count($seedNumbers) !== count(array_unique($seedNumbers))) {
                throw new RuntimeException('Seed numbers must be unique');
            }

            // Update seeds
            foreach ($seeds as $seed) {
                TournamentPlayer::where('id', $seed['player_id'])
                    ->update(['seed_number' => $seed['seed_number']])
                ;
            }
        });
    }

    /**
     * Complete seeding phase
     * @throws Throwable
     */
    public function completeSeedingPhase(Tournament $tournament): void
    {
        DB::transaction(static function () use ($tournament) {
            if ($tournament->stage !== TournamentStage::SEEDING) {
                throw new RuntimeException('Tournament is not in seeding phase');
            }

            // Verify all confirmed players have seed numbers
            $unseededPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->whereNull('seed_number')
                ->count()
            ;

            if ($unseededPlayers > 0) {
                throw new RuntimeException("There are $unseededPlayers confirmed players without seed numbers");
            }

            // Move to next stage based on tournament type
            $nextStage = in_array($tournament->tournament_type->value,
                ['groups', 'groups_playoff', 'team_groups_playoff'])
                ? TournamentStage::GROUP
                : TournamentStage::BRACKET;

            $tournament->update([
                'stage'                => $nextStage,
                'seeding_completed_at' => now(),
                'seeding_completed'    => true,
            ]);

            Schema::disableForeignKeyConstraints();   // отключить проверки
            $tournament->matches()->forceDelete();
            $tournament->brackets()->forceDelete();
            $tournament->groups()->forceDelete();
            Schema::enableForeignKeyConstraints();    // включить обратно
        });
    }
}
