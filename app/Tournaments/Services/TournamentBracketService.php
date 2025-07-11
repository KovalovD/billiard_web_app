<?php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\BracketType;
use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Enums\TournamentType;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentBracket;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;
use RuntimeException;
use Throwable;

class TournamentBracketService
{
    /**
     * Generate tournament bracket based on tournament type
     * @throws Throwable
     */
    public function generateBracket(Tournament $tournament): TournamentBracket
    {
        return DB::transaction(function () use ($tournament) {
            $confirmedPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->orderBy('seed_number')
                ->get()
            ;

            if ($confirmedPlayers->count() < 2) {
                throw new RuntimeException('At least 2 confirmed players are required to generate a bracket');
            }

            // Clear existing brackets and matches
            $tournament->brackets()->delete();
            $tournament->matches()->delete();

            return match ($tournament->tournament_type) {
                TournamentType::SINGLE_ELIMINATION => $this->generateSingleEliminationBracket($tournament,
                    $confirmedPlayers),
                TournamentType::DOUBLE_ELIMINATION, TournamentType::DOUBLE_ELIMINATION_FULL => $this->generateDoubleEliminationBracket($tournament,
                    $confirmedPlayers),
                TournamentType::OLYMPIC_DOUBLE_ELIMINATION => $this->generateOlympicDoubleEliminationBracket($tournament,
                    $confirmedPlayers),
                TournamentType::ROUND_ROBIN => $this->generateRoundRobinBracket($tournament, $confirmedPlayers),
                default => throw new RuntimeException("Bracket generation not supported for tournament type: {$tournament->tournament_type->value}"),
            };
        });
    }

    /**
     * Generate single elimination bracket
     */
    private function generateSingleEliminationBracket(
        Tournament $tournament,
        Collection $confirmedPlayers,
    ): TournamentBracket {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $totalRounds = (int) log($bracketSize, 2);

        $bracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds' => $totalRounds,
            'players_count' => $bracketSize,
        ]);

        // Create seeded bracket with proper matchups
        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);

        // Create matches with proper seeding
        $this->createSingleEliminationMatches($tournament, $bracket, $seededBracket);

        // Create third place match if enabled
        if ($tournament->has_third_place_match) {
            $this->createThirdPlaceMatch($tournament);
        }

        return $bracket;
    }

    /**
     * Get next power of two
     */
    private function getNextPowerOfTwo(int $n): int
    {
        return 2 ** ceil(log($n, 2));
    }

    /**
     * Create properly seeded bracket with byes
     */
    private function createSeededBracket(Collection $players, int $bracketSize): array
    {
        $seededBracket = [];

        // Fill bracket with players by seed
        foreach ($players as $player) {
            $seededBracket[$player->seed_number] = $player;
        }

        // Fill remaining spots with byes (null)
        for ($seed = $players->count() + 1; $seed <= $bracketSize; $seed++) {
            $seededBracket[$seed] = null;
        }

        return $seededBracket;
    }

    /**
     * Create single elimination matches with proper seeding
     */
    private function createSingleEliminationMatches(
        Tournament $tournament,
        TournamentBracket $bracket,
        array $seededBracket,
    ): void {
        $bracketSize = $bracket->players_count;
        $rounds = $bracket->total_rounds;

        // Create matches round by round
        $previousRoundMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnum($matchesInRound);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'       => sprintf("R%sM%s", $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    // Fix: For single elimination, use UB_R prefix to match the keys generated in getBracketOptions
                    'races_to' => $this->getRaceToForRound($tournament, sprintf("UB_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                $currentRoundMatches = $this->setupMatchConnections($round, $matchNum, $bracketSize, $seededBracket,
                    $match, $tournament, $previousRoundMatches, $currentRoundMatches);
            }

            $previousRoundMatches = $currentRoundMatches;
        }

        // Progress winners from walkover matches
        $this->progressWalkoverWinners($tournament);
    }

    /**
     * Get round enum based on matches in round
     */
    private function getRoundEnum(int $matchesInRound): EliminationRound
    {
        return match ($matchesInRound) {
            1 => EliminationRound::FINALS,
            2 => EliminationRound::SEMIFINALS,
            4 => EliminationRound::QUARTERFINALS,
            8 => EliminationRound::ROUND_16,
            16 => EliminationRound::ROUND_32,
            32 => EliminationRound::ROUND_64,
            default => EliminationRound::ROUND_128,
        };
    }

    /**
     * Setup match connections and players
     */
    private function setupMatchConnections(
        mixed $round,
        int $matchNum,
        mixed $bracketSize,
        array $seededBracket,
        TournamentMatch $match,
        Tournament $tournament,
        array $previousRoundMatches,
        array $currentRoundMatches,
    ): array {
        if ($round === 1) {
            // First round: assign players with proper seeding
            $matchup = $this->getFirstRoundMatchup($matchNum, $bracketSize);
            $player1 = $seededBracket[$matchup[0]] ?? null;
            $player2 = $seededBracket[$matchup[1]] ?? null;

            if ($player1) {
                $match->player1_id = $player1->user_id;
            }
            if ($player2) {
                $match->player2_id = $player2->user_id;
            }

            // Handle walkovers
            if ($player1 && !$player2) {
                $match->winner_id = $player1->user_id;
                $match->status = MatchStatus::COMPLETED;
                $match->player1_score = $match->races_to;
                $match->player2_score = 0;
                $match->completed_at = now();
                $match->admin_notes = 'Walkover - No opponent';
            } elseif (!$player1 && $player2) {
                $match->winner_id = $player2->user_id;
                $match->status = MatchStatus::COMPLETED;
                $match->player1_score = 0;
                $match->player2_score = $match->races_to;
                $match->completed_at = now();
                $match->admin_notes = 'Walkover - No opponent';
            } elseif ($player1 && $player2) {
                $match->status = MatchStatus::READY;
            }
        } else {
            // Set up progression from previous round
            $this->setUpProgressionFromPreviousRound($matchNum, $previousRoundMatches, $match);
        }

        $match->save();
        $currentRoundMatches[] = $match;
        return $currentRoundMatches;
    }

    /**
     * Get proper matchup for first round based on seeding
     */
    private function getFirstRoundMatchup(int $matchNum, int $bracketSize): array
    {
        // Standard tournament seeding
        $matchups = $this->generateSeedMatchups($bracketSize);
        return $matchups[$matchNum] ?? [1, 2];
    }

    /**
     * Generate seed matchups for bracket
     */
    private function generateSeedMatchups(int $bracketSize): array
    {
        $matchups = [];
        $seeds = range(1, $bracketSize);

        // Create pairs ensuring 1 and 2 seeds are on opposite sides
        $top = array_slice($seeds, 0, $bracketSize / 2);
        $bottom = array_reverse(array_slice($seeds, $bracketSize / 2));

        for ($i = 0, $iMax = count($top); $i < $iMax; $i++) {
            $matchups[] = [$top[$i], $bottom[$i]];
        }

        // Rearrange to ensure proper bracket distribution
        return $this->arrangeBracketMatchups($matchups);
    }

    /**
     * Arrange matchups for proper bracket distribution
     */
    private function arrangeBracketMatchups(array $matchups): array
    {
        // This ensures seeds are distributed properly across the bracket
        $arranged = [];
        $indices = $this->getBracketIndices(count($matchups));

        foreach ($indices as $i => $index) {
            $arranged[$i] = $matchups[$index];
        }

        return $arranged;
    }

    /**
     * Get bracket indices for proper seed distribution
     */
    private function getBracketIndices(int $size): array
    {
        if ($size === 1) {
            return [0];
        }
        if ($size === 2) {
            return [0, 1];
        }
        if ($size === 4) {
            return [0, 3, 1, 2];
        }
        if ($size === 8) {
            return [0, 7, 3, 4, 1, 6, 2, 5];
        }
        if ($size === 16) {
            return [0, 15, 7, 8, 3, 12, 4, 11, 1, 14, 6, 9, 2, 13, 5, 10];
        }
        if ($size === 32) {
            return [
                0, 31, 15, 16, 7, 24, 8, 23, 3, 28, 12, 19, 4, 27, 11, 20,
                1, 30, 14, 17, 6, 25, 9, 22, 2, 29, 13, 18, 5, 26, 10, 21,
            ];
        }

        // For larger brackets, use recursive approach
        $half = $size / 2;
        $firstHalf = $this->getBracketIndices($half);
        $secondHalf = array_map(static fn($i) => $i + $half, $firstHalf);

        $result = [];
        for ($i = 0; $i < $half; $i++) {
            $result[] = $firstHalf[$i];
            $result[] = $secondHalf[$i];
        }

        return $result;
    }

    private function setUpProgressionFromPreviousRound(
        int $matchNum,
        array $previousRoundMatches,
        TournamentMatch $match,
    ): void {
        $prevMatch1Index = $matchNum * 2;
        $prevMatch2Index = $matchNum * 2 + 1;

        if (isset($previousRoundMatches[$prevMatch1Index])) {
            $match->previous_match1_id = $previousRoundMatches[$prevMatch1Index]->id;
            $previousRoundMatches[$prevMatch1Index]->next_match_id = $match->id;
            $previousRoundMatches[$prevMatch1Index]->save();
        }

        if (isset($previousRoundMatches[$prevMatch2Index])) {
            $match->previous_match2_id = $previousRoundMatches[$prevMatch2Index]->id;
            $previousRoundMatches[$prevMatch2Index]->next_match_id = $match->id;
            $previousRoundMatches[$prevMatch2Index]->save();
        }
    }

    /**
     * Progress winners from walkover matches
     */
    private function progressWalkoverWinners(Tournament $tournament): void
    {
        $walkoverMatches = $tournament
            ->matches()
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($query) {
                $query
                    ->whereNull('player1_id')
                    ->orWhereNull('player2_id')
                ;
            })
            ->get()
        ;

        foreach ($walkoverMatches as $match) {
            $this->progressWinnerToNextMatch($match);
        }
    }

    private function progressWinnerToNextMatch(mixed $match): void
    {
        if ($match->next_match_id && $match->winner_id) {
            $nextMatch = TournamentMatch::find($match->next_match_id);
            if ($nextMatch) {
                if ($nextMatch->previous_match1_id === $match->id) {
                    $nextMatch->player1_id = $match->winner_id;
                } else {
                    $nextMatch->player2_id = $match->winner_id;
                }

                // Check if next match is ready
                if ($nextMatch->player1_id && $nextMatch->player2_id) {
                    $nextMatch->status = MatchStatus::READY;
                }

                $nextMatch->save();
            }
        }
    }

    /**
     * Create third place match
     */
    private function createThirdPlaceMatch(Tournament $tournament): void
    {
        $thirdPlace = TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'match_code'    => '3RD',
            'stage'         => MatchStage::THIRD_PLACE,
            'round'         => EliminationRound::THIRD_PLACE,
            'races_to' => $this->getRaceToForRound($tournament, "3RD"),
            'status'        => MatchStatus::PENDING,
        ]);

        // Connect semifinals losers to third place match
        $semifinals = $tournament
            ->matches()
            ->where('round', EliminationRound::SEMIFINALS)
            ->get()
        ;

        foreach ($semifinals as $semi) {
            $semi->loser_next_match_id = $thirdPlace->id;
            $semi->save();
        }
    }

    /**
     * Generate double elimination bracket
     */
    private function generateDoubleEliminationBracket(
        Tournament $tournament,
        Collection $confirmedPlayers,
    ): TournamentBracket {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $upperRounds = (int) log($bracketSize, 2);

        // Create upper bracket
        $upperBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_UPPER,
            'total_rounds'  => $upperRounds,
            'players_count' => $bracketSize,
        ]);

        // Create lower bracket
        $lowerRounds = ($upperRounds - 1) * 2;

        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_LOWER,
            'total_rounds'  => $lowerRounds,
            'players_count' => $bracketSize - 1, // Total losers count
        ]);

        // Create seeded bracket with proper matchups
        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);

        // Create upper bracket matches
        $upperMatches = $this->createUpperBracketMatches($tournament, $upperBracket, $seededBracket);

        // Create lower bracket structure
        $lowerFinalMatch = $this->createLowerBracketComplete($tournament, $bracketSize, $upperRounds, $upperMatches);

        // Create grand finals
        $upperFinal = $upperMatches[$upperRounds][0] ?? null;
        $this->createGrandFinals($tournament, $upperFinal, $lowerFinalMatch);

        return $upperBracket;
    }

    /**
     * Create upper bracket matches for double elimination
     */
    private function createUpperBracketMatches(
        Tournament $tournament,
        TournamentBracket $bracket,
        array $seededBracket,
    ): array {
        $bracketSize = $bracket->players_count;
        $rounds = $bracket->total_rounds;
        $allUpperMatches = [];

        // Create matches round by round
        $previousRoundMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnumForDouble($round, $rounds);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf("UB_R%sM%s", $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf("UB_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                $currentRoundMatches = $this->setupMatchConnections($round, $matchNum, $bracketSize, $seededBracket,
                    $match, $tournament, $previousRoundMatches, $currentRoundMatches);
            }

            $allUpperMatches[$round] = $currentRoundMatches;
            $previousRoundMatches = $currentRoundMatches;
        }

        // Progress winners from walkover matches in upper bracket
        $this->progressWalkoverWinnersInBracket($tournament);

        return $allUpperMatches;
    }

    /**
     * Get round enum for double elimination upper bracket
     */
    private function getRoundEnumForDouble(int $round, int $totalRounds): EliminationRound
    {
        $remaining = $totalRounds - $round + 1;

        return match ($remaining) {
            1 => EliminationRound::FINALS,
            2 => EliminationRound::SEMIFINALS,
            3 => EliminationRound::QUARTERFINALS,
            4 => EliminationRound::ROUND_16,
            5 => EliminationRound::ROUND_32,
            6 => EliminationRound::ROUND_64,
            default => EliminationRound::ROUND_128,
        };
    }

    /**
     * Progress winners from walkover matches in specific bracket
     */
    private function progressWalkoverWinnersInBracket(Tournament $tournament): void
    {
        $walkoverMatches = $tournament
            ->matches()
            ->where('bracket_side', 'upper')
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($query) {
                $query
                    ->whereNull('player1_id')
                    ->orWhereNull('player2_id')
                ;
            })
            ->get()
        ;

        foreach ($walkoverMatches as $match) {
            // Progress winner to next match
            $this->progressWinnerToNextMatch($match);

            // Progress loser to lower bracket if applicable
            if ($match->loser_next_match_id) {
                $loserId = $match->winner_id === $match->player1_id
                    ? $match->player2_id
                    : $match->player1_id;

                if ($loserId) {
                    $loserMatch = TournamentMatch::find($match->loser_next_match_id);
                    if ($loserMatch) {
                        $position = $match->loser_next_match_position ?? null;

                        if ($position === 1) {
                            $loserMatch->player1_id = $loserId;
                        } elseif ($position === 2) {
                            $loserMatch->player2_id = $loserId;
                        } elseif (!$loserMatch->player1_id) {
                            $loserMatch->player1_id = $loserId;
                        } else {
                            $loserMatch->player2_id = $loserId;
                        }

                        // Check if match is ready
                        if ($loserMatch->player1_id && $loserMatch->player2_id) {
                            $loserMatch->status = MatchStatus::READY;
                        }

                        $loserMatch->save();
                    }
                }
            }
        }
    }

    /**
     * Create complete lower bracket structure
     */
    private function createLowerBracketComplete(
        Tournament $tournament,
        int $bracketSize,
        int $upperRounds,
        array $upperMatches,
    ): ?TournamentMatch {
        // Calculate structure for each bracket size
        $lowerStructure = $this->getLowerBracketStructure($bracketSize);
        $allLowerMatches = [];

        foreach ($lowerStructure as $round => $roundData) {
            $currentRoundMatches = [];
            $roundEnum = $this->getLowerRoundEnum($round, count($lowerStructure));

            for ($matchNum = 0; $matchNum < $roundData['matches']; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'       => sprintf("LB_R%sM%s", $round, $matchNum + 1),
                    'stage'    => MatchStage::LOWER_BRACKET,
                    'round'    => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'lower',
                    'races_to' => $this->getRaceToForRound($tournament, sprintf("LB_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                // Connect based on round type
                if ($roundData['type'] === 'initial') {
                    // First round - receives losers from upper bracket R1
                    $upperMatch1Index = $matchNum * 2;
                    $upperMatch2Index = $matchNum * 2 + 1;

                    if (isset($upperMatches[1][$upperMatch1Index])) {
                        $upperMatches[1][$upperMatch1Index]->loser_next_match_id = $match->id;
                        $upperMatches[1][$upperMatch1Index]->loser_next_match_position = 1;
                        $upperMatches[1][$upperMatch1Index]->save();
                    }

                    if (isset($upperMatches[1][$upperMatch2Index])) {
                        $upperMatches[1][$upperMatch2Index]->loser_next_match_id = $match->id;
                        $upperMatches[1][$upperMatch2Index]->loser_next_match_position = 2;
                        $upperMatches[1][$upperMatch2Index]->save();
                    }
                } elseif ($roundData['type'] === 'drop') {
                    // Drop round - receives upper bracket losers and lower bracket winners
                    $prevRoundMatches = $allLowerMatches[$round - 1] ?? [];

                    // Connect lower bracket winner to position 1
                    if (isset($prevRoundMatches[$matchNum])) {
                        $match->previous_match1_id = $prevRoundMatches[$matchNum]->id;
                        $prevRoundMatches[$matchNum]->next_match_id = $match->id;
                        $prevRoundMatches[$matchNum]->save();
                    }

                    // Connect upper bracket loser to position 2
                    $upperRoundIndex = $roundData['upper_round'];
                    if (isset($upperMatches[$upperRoundIndex][$matchNum])) {
                        $upperMatches[$upperRoundIndex][$matchNum]->loser_next_match_id = $match->id;
                        $upperMatches[$upperRoundIndex][$matchNum]->loser_next_match_position = 2;
                        $upperMatches[$upperRoundIndex][$matchNum]->save();
                    }
                } elseif ($roundData['type'] === 'regular') {
                    // Regular round - two lower bracket winners fight
                    $prevRoundMatches = $allLowerMatches[$round - 1] ?? [];
                    $this->setUpProgressionFromPreviousRound($matchNum, $prevRoundMatches, $match);
                } elseif ($roundData['type'] === 'final_drop') {
                    // Final drop - receives upper bracket final loser
                    $prevRoundMatches = $allLowerMatches[$round - 1] ?? [];

                    // Connect lower bracket winner to position 1
                    if (isset($prevRoundMatches[0])) {
                        $match->previous_match1_id = $prevRoundMatches[0]->id;
                        $prevRoundMatches[0]->next_match_id = $match->id;
                        $prevRoundMatches[0]->save();
                    }

                    // Connect upper bracket final loser to position 2
                    if (isset($upperMatches[$upperRounds][0])) {
                        $upperMatches[$upperRounds][0]->loser_next_match_id = $match->id;
                        $upperMatches[$upperRounds][0]->loser_next_match_position = 2;
                        $upperMatches[$upperRounds][0]->save();
                    }
                }

                $match->save();
                $currentRoundMatches[] = $match;
            }

            $allLowerMatches[$round] = $currentRoundMatches;
        }

        // Return the lower bracket final match
        $lastRound = max(array_keys($allLowerMatches));
        return $allLowerMatches[$lastRound][0] ?? null;
    }

    /**
     * Get lower bracket structure for a given bracket size
     */
    private function getLowerBracketStructure(int $bracketSize): array
    {
        return match ($bracketSize) {
            4 => [
                1 => ['matches' => 1, 'type' => 'initial'],
                2 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 2],
            ],
            8 => [
                1 => ['matches' => 2, 'type' => 'initial'],
                2 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 1, 'type' => 'regular'],
                4 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 3],
            ],
            16 => [
                1 => ['matches' => 4, 'type' => 'initial'],
                2 => ['matches' => 4, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 2, 'type' => 'regular'],
                4 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 3],
                5 => ['matches' => 1, 'type' => 'regular'],
                6 => ['matches' => 1, 'type' => 'final_drop', 'upper_round' => 4],
            ],
            32 => [
                1 => ['matches' => 8, 'type' => 'initial'],
                2 => ['matches' => 8, 'type' => 'drop', 'upper_round' => 2],
                3 => ['matches' => 4, 'type' => 'regular'],
                4 => ['matches' => 4, 'type' => 'drop', 'upper_round' => 3],
                5 => ['matches' => 2, 'type' => 'regular'],
                6 => ['matches' => 2, 'type' => 'drop', 'upper_round' => 4],
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
            default => throw new RuntimeException("Unsupported bracket size: $bracketSize"),
        };
    }

    /**
     * Get lower bracket round enum
     */
    private function getLowerRoundEnum(int $round, int $totalRounds): EliminationRound
    {
        $remaining = $totalRounds - $round + 1;

        if ($remaining <= 2) {
            return EliminationRound::FINALS;
        }

        if ($remaining <= 4) {
            return EliminationRound::SEMIFINALS;
        }

        if ($remaining <= 6) {
            return EliminationRound::QUARTERFINALS;
        }

        if ($remaining <= 8) {
            return EliminationRound::ROUND_16;
        }

        if ($remaining <= 10) {
            return EliminationRound::ROUND_32;
        }

        return EliminationRound::ROUND_64;
    }

    /**
     * Create Olympic first stage lower bracket
     */
    private function createOlympicFirstStageLowerBracket(
        Tournament $tournament,
        int $bracketSize,
        int $olympicPhaseRound,
        array $upperMatches,
        int $olympicPhaseSize,
    ): void {
        $upperAdvancing = $olympicPhaseSize / 2;
        $lowerAdvancing = $olympicPhaseSize / 2;

        $fullLowerStructure = $this->getLowerBracketStructure($bracketSize);
        $allLowerMatches = [];

        $targetRound = 0;
        $currentPlayers = 0;

        foreach ($fullLowerStructure as $round => $roundData) {
            // how many players ENTER this round?
            if ($roundData['type'] === 'initial') {
                $currentPlayers = $roundData['matches'] * 2;
            } else {
                $currentPlayers = (int) ceil($currentPlayers / 2);
            }

            // have we reached the desired amount?
            if ($currentPlayers === $lowerAdvancing) {
                $targetRound = $round;

                // If the current round still produces *more* winners than we need
                // (e.g. 2 matches → 2 winners while we need only 1), advance one more
                // round until matches == $lowerAdvancing.
                while (
                    isset($fullLowerStructure[$targetRound])
                    && $fullLowerStructure[$targetRound]['matches'] > $lowerAdvancing
                ) {
                    ++$targetRound;
                }

                // If the *next* round is a drop/final_drop (it receives UB losers) and
                // the round we stopped at is *not* a drop, we must include that next
                // round, otherwise the UB finals loser has nowhere to go.
                $nextType = $fullLowerStructure[$targetRound + 1]['type'] ?? null;
                if (
                    $nextType
                    && in_array($nextType, ['drop', 'final_drop'], true)
                    && $fullLowerStructure[$targetRound]['type'] !== 'drop'
                ) {
                    ++$targetRound;
                }

                break;
            }
        }

        if ($targetRound === 0) {
            throw new RuntimeException(
                "Cannot calculate lower-bracket rounds for Olympic phase size: $olympicPhaseSize",
            );
        }

        // Create matches up to and including the target round
        foreach ($fullLowerStructure as $round => $roundData) {
            if ($round > $targetRound) {
                break;
            }

            $currentRoundMatches = [];
            $roundEnum = $this->getLowerRoundEnum($round, $targetRound);

            for ($matchNum = 0; $matchNum < $roundData['matches']; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'       => sprintf("FS_LB_R%sM%s", $round, $matchNum + 1),
                    'stage'            => MatchStage::LOWER_BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'lower',
                    'races_to'         => $this->getRaceToForRound($tournament, sprintf("LB_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                    'metadata'         => ['olympic_stage' => 'first'],
                ]);

                // --- wiring logic -------------------------------------------------
                if ($roundData['type'] === 'initial') {
                    // losers from UB-R1 land here
                    $u1 = $matchNum * 2;
                    $u2 = $matchNum * 2 + 1;

                    if (isset($upperMatches[1][$u1])) {
                        $upperMatches[1][$u1]->loser_next_match_id = $match->id;
                        $upperMatches[1][$u1]->loser_next_match_position = 1;
                        $upperMatches[1][$u1]->save();
                    }
                    if (isset($upperMatches[1][$u2])) {
                        $upperMatches[1][$u2]->loser_next_match_id = $match->id;
                        $upperMatches[1][$u2]->loser_next_match_position = 2;
                        $upperMatches[1][$u2]->save();
                    }
                } elseif ($roundData['type'] === 'drop') {
                    // LB winner vs UB loser
                    $prev = $allLowerMatches[$round - 1] ?? [];

                    if (isset($prev[$matchNum])) {
                        $match->previous_match1_id = $prev[$matchNum]->id;
                        $prev[$matchNum]->next_match_id = $match->id;
                        $prev[$matchNum]->save();
                    }

                    $upperRoundIndex = $roundData['upper_round'];
                    if (isset($upperMatches[$upperRoundIndex][$matchNum])) {
                        $upperMatches[$upperRoundIndex][$matchNum]->loser_next_match_id = $match->id;
                        $upperMatches[$upperRoundIndex][$matchNum]->loser_next_match_position = 2;
                        $upperMatches[$upperRoundIndex][$matchNum]->save();
                    }
                } else { // regular
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    $this->setUpProgressionFromPreviousRound($matchNum, $prev, $match);
                }
                // ------------------------------------------------------------------

                $match->save();
                $currentRoundMatches[] = $match;
            }

            $allLowerMatches[$round] = $currentRoundMatches;
        }
        // ------------------------------------------------------------------------

        // hand the remaining UB losers off, mark advancing etc. (unchanged)
        $this->connectRemainingUpperBracketLosers($upperMatches, $allLowerMatches, $olympicPhaseRound);
        $this->markOlympicAdvancingMatches(
            $upperMatches,
            $allLowerMatches,
            $olympicPhaseRound,
            $upperAdvancing,
            $lowerAdvancing,
        );
    }

    /**
     * Connect remaining upper bracket losers to lower bracket
     */
    private function connectRemainingUpperBracketLosers(
        array $upperMatches,
        array $lowerMatches,
        int $olympicPhaseRound,
    ): void {
        // Go through all upper bracket rounds except the last one (which advances to Olympic)
        for ($round = 1; $round < $olympicPhaseRound - 1; $round++) {
            if (!isset($upperMatches[$round])) {
                continue;
            }

            foreach ($upperMatches[$round] as $upperMatch) {
                // If this match doesn't have a loser connection yet, find where it should go
                if (!$upperMatch->loser_next_match_id) {
                    // Find the appropriate lower bracket match
                    $targetLowerMatch = $this->findLowerBracketMatchForUpperLoser($upperMatch, $lowerMatches, $round);

                    if ($targetLowerMatch) {
                        $upperMatch->loser_next_match_id = $targetLowerMatch->id;
                        // Determine position based on whether the lower match already has players
                        if (!$targetLowerMatch->player1_id && !$targetLowerMatch->player2_id) {
                            $upperMatch->loser_next_match_position = 2; // Upper losers typically go to position 2
                        }
                        $upperMatch->save();
                    }
                }
            }
        }
    }

    /**
     * Find appropriate lower bracket match for upper bracket loser
     */
    private function findLowerBracketMatchForUpperLoser(
        TournamentMatch $upperMatch,
        array $lowerMatches,
        int $upperRound,
    ): ?TournamentMatch {
        // For Olympic brackets, we need to find where this upper match's loser should go
        // This is complex because we have a partial lower bracket

        // Try to find a drop round that corresponds to this upper round
        foreach ($lowerMatches as $lowerRound => $matches) {
            foreach ($matches as $lowerMatch) {
                // Check if this lower match is expecting an upper bracket loser from this round
                // and doesn't have one assigned yet
                if (!$lowerMatch->previous_match2_id &&
                    str_contains($lowerMatch->match_code, 'FS_LB_R')) {
                    // Check if this is a drop round that should receive from this upper round
                    $lowerRoundNum = (int) preg_replace('/[^0-9]/', '', $lowerMatch->match_code);

                    // Drop rounds are typically even numbered and receive from upper round (dropRound/2)
                    if ($lowerRoundNum % 2 === 0 && $lowerRoundNum / 2 === $upperRound) {
                        return $lowerMatch;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Mark matches that advance to Olympic stage
     */
    private function markOlympicAdvancingMatches(
        array $upperMatches,
        array $lowerMatches,
        int $olympicPhaseRound,
        int $upperAdvancing,
        int $lowerAdvancing,
    ): void {
        // собираем последнюю сыгранную стадию каждой сетки
        $lastUpperRound = $olympicPhaseRound - 1;
        $upperAdvancers = $upperMatches[$lastUpperRound] ?? [];
        $lastLowerRound = !empty($lowerMatches) ? max(array_keys($lowerMatches)) : null;
        $lowerAdvancers = $lastLowerRound !== null ? ($lowerMatches[$lastLowerRound] ?? []) : [];

        // гарантируем одинаковое количество участников — иначе логическая ошибка
        if (count($upperAdvancers) !== $upperAdvancing || count($lowerAdvancers) !== $lowerAdvancing) {
            throw new RuntimeException('Advancing match count mismatch between upper and lower brackets');
        }

        // чередуем позиции: 0-upper, 1-lower, 2-upper, 3-lower…
        $olympicPos = 0;
        for ($i = 0; $i < $upperAdvancing; $i++) {
            // верхний победитель
            $match = $upperAdvancers[$i];
            $match->metadata = array_merge($match->metadata ?? [], [
                'advances_to_olympic' => true,
                'olympic_position'    => $olympicPos++,
            ]);
            $match->save();

            // нижний победитель
            $match = $lowerAdvancers[$i];
            $match->metadata = array_merge($match->metadata ?? [], [
                'advances_to_olympic' => true,
                'olympic_position'    => $olympicPos++,
            ]);
            $match->save();
        }
    }

    /**
     * Progress walkover winners in Olympic first stage
     */
    private function progressWalkoverWinnersInOlympicFirstStage(Tournament $tournament): void
    {
        $walkoverMatches = $tournament
            ->matches()
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($query) {
                $query
                    ->whereNull('player1_id')
                    ->orWhereNull('player2_id')
                ;
            })
            ->whereJsonContains('metadata->olympic_stage', 'first')
            ->get()
        ;

        foreach ($walkoverMatches as $match) {
            // Check if this match advances to Olympic stage
            $advancesToOlympic = $match->metadata['advances_to_olympic'] ?? false;

            if ($advancesToOlympic) {
                // Progress winner directly to Olympic stage
                $this->progressWinnerToOlympicStageFromWalkover($match);
            } else {
                // Progress winner to next match in first stage
                if ($match->next_match_id) {
                    $this->progressWinnerToNextMatch($match);
                }

                // Progress loser to lower bracket if applicable
                if ($match->loser_next_match_id) {
                    $loserId = $match->winner_id === $match->player1_id
                        ? $match->player2_id
                        : $match->player1_id;

                    if ($loserId) {
                        $loserMatch = TournamentMatch::find($match->loser_next_match_id);
                        if ($loserMatch) {
                            $position = $match->loser_next_match_position ?? null;

                            if ($position === 1) {
                                $loserMatch->player1_id = $loserId;
                            } elseif ($position === 2) {
                                $loserMatch->player2_id = $loserId;
                            } elseif (!$loserMatch->player1_id) {
                                $loserMatch->player1_id = $loserId;
                            } else {
                                $loserMatch->player2_id = $loserId;
                            }

                            // Check if match is ready
                            if ($loserMatch->player1_id && $loserMatch->player2_id) {
                                $loserMatch->status = MatchStatus::READY;
                            }

                            $loserMatch->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Progress winner to Olympic stage from walkover
     */
    private function progressWinnerToOlympicStageFromWalkover(TournamentMatch $match): void
    {
        if (!$match->winner_id || !isset($match->metadata['olympic_position'])) {
            return;
        }

        $olympicPosition = $match->metadata['olympic_position'];
        $tournament = $match->tournament;

        // Find the corresponding Olympic stage match
        $matchIndex = floor($olympicPosition / 2);
        $matchNumber = $matchIndex + 1;

        $olympicMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('match_code', 'OS_R1M'.$matchNumber)
            ->whereJsonContains('metadata->olympic_stage', 'second')
            ->first()
        ;

        if (!$olympicMatch) {
            return;
        }

        // Assign winner to the Olympic match
        if (!$olympicMatch->player1_id) {
            $olympicMatch->player1_id = $match->winner_id;
        } elseif (!$olympicMatch->player2_id) {
            $olympicMatch->player2_id = $match->winner_id;
        }

        // Update status to ready if both players are set
        if ($olympicMatch->player1_id && $olympicMatch->player2_id) {
            $olympicMatch->status = MatchStatus::READY;
        }

        $olympicMatch->save();
    }

    /**
     * Create grand finals for double elimination
     */
    private function createGrandFinals(
        Tournament $tournament,
        ?TournamentMatch $upperFinal,
        ?TournamentMatch $lowerFinal,
    ): void {
        // Main grand final
        $grandFinal = TournamentMatch::create([
            'tournament_id'      => $tournament->id,
            'match_code'         => 'GF',
            'stage'              => MatchStage::BRACKET,
            'round'              => EliminationRound::GRAND_FINALS,
            'bracket_position'   => 0,
            'races_to' => $this->getRaceToForRound($tournament, "GF"),
            'status'             => MatchStatus::PENDING,
            'previous_match1_id' => $upperFinal?->id,
            'previous_match2_id' => $lowerFinal?->id,
        ]);

        // Connect finals to grand final
        if ($upperFinal) {
            $upperFinal->next_match_id = $grandFinal->id;
            $upperFinal->save();
        }

        if ($lowerFinal) {
            $lowerFinal->next_match_id = $grandFinal->id;
            $lowerFinal->save();
        }
    }

    /**
     * Generate Olympic double elimination bracket (2 separate brackets)
     */
    private function generateOlympicDoubleEliminationBracket(
        Tournament $tournament,
        Collection $confirmedPlayers,
    ): TournamentBracket {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $upperRounds = (int) log($bracketSize, 2);

        // Validate olympic phase size
        $olympicPhaseSize = $tournament->olympic_phase_size ?? 8;
        if ($olympicPhaseSize > $bracketSize) {
            $olympicPhaseSize = $bracketSize;
        }

        // If Olympic phase is the entire bracket, just generate single elimination
        if ($olympicPhaseSize === $bracketSize) {
            return $this->generateSingleEliminationBracket($tournament, $confirmedPlayers);
        }

        // Calculate rounds until Olympic phase
        // We need to play until we have olympicPhaseSize/2 players in upper bracket
        $olympicPhaseRound = $upperRounds - (int) log($olympicPhaseSize / 2, 2) + 1;

        // Create First Stage bracket (Double Elimination)
        $upperBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_UPPER,
            'total_rounds' => $olympicPhaseRound - 1, // Only up to Olympic phase
            'players_count' => $bracketSize,
            'metadata'     => ['stage' => 'first_stage', 'olympic_phase_size' => $olympicPhaseSize],
        ]);

        // Create lower bracket for first stage
        $lowerRounds = ($olympicPhaseRound - 1) * 2 - 1; // One less round since we stop early
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_LOWER,
            'total_rounds'  => $lowerRounds,
            'players_count' => $bracketSize - 1,
            'metadata'      => ['stage' => 'first_stage', 'olympic_phase_size' => $olympicPhaseSize],
        ]);

        // Create Olympic Stage bracket (Single Elimination)
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds'  => (int) log($olympicPhaseSize, 2),
            'players_count' => $olympicPhaseSize,
            'metadata'      => ['stage' => 'olympic_stage'],
        ]);

        // Create seeded bracket with proper matchups
        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);

        // Create first stage matches (Double Elimination that stops at Olympic phase)
        $this->createOlympicFirstStage($tournament, $upperBracket, $seededBracket, $olympicPhaseRound,
            $olympicPhaseSize);

        // Create Olympic stage matches (Single Elimination)
        $this->createOlympicSecondStage($tournament, $olympicPhaseSize);

        return $upperBracket;
    }

    /**
     * Create Olympic first stage (partial double elimination)
     */
    private function createOlympicFirstStage(
        Tournament $tournament,
        TournamentBracket $upperBracket,
        array $seededBracket,
        int $olympicPhaseRound,
        int $olympicPhaseSize,
    ): void {
        $bracketSize = $upperBracket->players_count;
        $rounds = $olympicPhaseRound - 1; // Stop one round before Olympic phase
        $allUpperMatches = [];

        // Create upper bracket matches
        $previousRoundMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnumForDouble($round, $rounds);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'   => sprintf("FS_UB_R%sM%s", $round, $matchNum + 1),
                    'stage'        => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side' => 'upper',
                    'races_to'     => $this->getRaceToForRound($tournament, sprintf("UB_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                    'metadata'     => ['olympic_stage' => 'first'],
                ]);

                $currentRoundMatches = $this->setupMatchConnections($round, $matchNum, $bracketSize, $seededBracket,
                    $match, $tournament, $previousRoundMatches, $currentRoundMatches);
            }

            $allUpperMatches[$round] = $currentRoundMatches;
            $previousRoundMatches = $currentRoundMatches;
        }

        // Create lower bracket structure (partial)
        $this->createOlympicFirstStageLowerBracket($tournament, $bracketSize, $olympicPhaseRound, $allUpperMatches,
            $olympicPhaseSize);

        // Progress winners from walkover matches
        $this->progressWalkoverWinnersInOlympicFirstStage($tournament);
    }

    /**
     * Create Olympic second stage (single elimination)
     */
    private function createOlympicSecondStage(Tournament $tournament, int $olympicPhaseSize): void
    {
        $olympicRounds = (int) log($olympicPhaseSize, 2);
        $previousRoundMatches = [];

        for ($round = 1; $round <= $olympicRounds; $round++) {
            $matchesInRound = $olympicPhaseSize / (2 ** $round);
            $roundEnum = $this->getRoundEnum($matchesInRound);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf("OS_R%sM%s", $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    'races_to'         => $this->getRaceToForRound($tournament, sprintf("O_R%s", $round)),
                    'status'           => MatchStatus::PENDING,
                    'metadata'   => ['olympic_stage' => 'second'],
                ]);

                if ($round > 1) {
                    $this->setUpProgressionFromPreviousRound($matchNum, $previousRoundMatches, $match);
                }

                $match->save();
                $currentRoundMatches[] = $match;
            }

            $previousRoundMatches = $currentRoundMatches;
        }

        // Create third place match if enabled and Olympic phase > 4
        if ($olympicPhaseSize > 4 && $tournament->olympic_has_third_place) {
            $this->createOlympicThirdPlaceMatch($tournament);
        }
    }

    /**
     * Create third place match for Olympic phase
     */
    private function createOlympicThirdPlaceMatch(Tournament $tournament): void
    {
        $thirdPlace = TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'match_code' => 'OS_3RD',
            'stage'         => MatchStage::THIRD_PLACE,
            'round'         => EliminationRound::THIRD_PLACE,
            'races_to'      => $this->getRaceToForRound($tournament, "O_3RD"),
            'status'        => MatchStatus::PENDING,
            'metadata'   => ['olympic_stage' => 'second'],
        ]);

        // Connect Olympic semifinals losers to third place match
        $olympicSemifinals = $tournament
            ->matches()
            ->where('match_code', 'LIKE', 'OS_%')
            ->where('round', EliminationRound::SEMIFINALS)
            ->get()
        ;

        foreach ($olympicSemifinals as $semi) {
            $semi->loser_next_match_id = $thirdPlace->id;
            $semi->save();
        }
    }

    /**
     * Get race-to value for specific round
     *
     * This method now properly handles the mapping between round codes
     * and the round_races_to array stored in the tournament
     */
    private function getRaceToForRound(Tournament $tournament, string $roundCode): int
    {
        $roundRacesTo = $tournament->round_races_to ?? [];

        // Debug log
        Log::info('Getting race-to for round', [
            'round_code'       => $roundCode,
            'round_races_to'   => $roundRacesTo,
            'default_races_to' => $tournament->races_to,
        ]);

        // Check if custom race-to is defined for this round
        if (isset($roundRacesTo[$roundCode])) {
            return (int) $roundRacesTo[$roundCode];
        }

        // Default to tournament races_to
        return (int) ($tournament->races_to ?? 7);
    }

    /**
     * Generate round robin bracket
     */
    private function generateRoundRobinBracket(Tournament $tournament, Collection $confirmedPlayers): TournamentBracket
    {
        $bracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds'  => 1,
            'players_count' => $confirmedPlayers->count(),
        ]);

        // Create matches between every pair of players
        foreach ($confirmedPlayers as $i => $player1) {
            foreach ($confirmedPlayers->slice($i + 1) as $player2) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'match_code' => sprintf("RR_%sv%s", $player1->seed_number, $player2->seed_number),
                    'stage'         => MatchStage::BRACKET,
                    'round'      => EliminationRound::ROUND_128,
                    'player1_id'    => $player1->user_id,
                    'player2_id'    => $player2->user_id,
                    'races_to'      => $tournament->races_to,
                    'status'     => MatchStatus::READY,
                ]);
            }
        }

        return $bracket;
    }

    /**
     * Generate groups for group stage tournaments
     * @throws Throwable
     */
    public function generateGroups(Tournament $tournament): array
    {
        return DB::transaction(function () use ($tournament) {
            // Validate tournament type supports groups
            if (!in_array($tournament->tournament_type->value, [
                'groups',
                'groups_playoff',
                'round_robin',
                'team_groups_playoff',
            ])) {
                throw new RuntimeException("Tournament type does not support group stage");
            }

            $confirmedPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->orderBy('seed_number')
                ->get()
            ;

            if ($confirmedPlayers->count() < 4) {
                throw new RuntimeException('At least 4 confirmed players are required for group stage');
            }

            // Clear existing groups
            $tournament->groups()->delete();

            // Calculate group configuration
            $playerCount = $confirmedPlayers->count();
            $minGroupSize = $tournament->group_size_min ?? 4;
            $maxGroupSize = $tournament->group_size_max ?? 5;

            $groupConfig = $this->calculateGroupConfiguration($playerCount, $minGroupSize, $maxGroupSize);

            // Create groups and assign players
            $groups = [];

            foreach ($groupConfig as $groupIndex => $groupSize) {
                $groupCode = chr(65 + $groupIndex); // A, B, C, etc.

                $group = $tournament->groups()->create([
                    'group_code'    => $groupCode,
                    'group_size'    => $groupSize,
                    'advance_count' => $tournament->playoff_players_per_group ?? 2,
                ]);

                // Assign players to group using snake draft based on seeding
                $groupPlayers = $this->assignPlayersToGroup(
                    $confirmedPlayers,
                    $groupIndex,
                    count($groupConfig),
                    $groupSize,
                );

                foreach ($groupPlayers as $player) {
                    $player->update(['group_code' => $groupCode]);
                }

                // Create round-robin matches within the group
                $this->createGroupMatches($tournament, $group, $groupPlayers);

                $groups[] = $group;
            }

            return $groups;
        });
    }

    /**
     * Calculate optimal group configuration
     */
    private function calculateGroupConfiguration(int $playerCount, int $minSize, int $maxSize): array
    {
        // Try to create equal-sized groups
        for ($groupSize = $maxSize; $groupSize >= $minSize; $groupSize--) {
            if ($playerCount % $groupSize === 0) {
                return array_fill(0, $playerCount / $groupSize, $groupSize);
            }
        }

        // If equal groups not possible, minimize variance
        $numGroups = ceil($playerCount / $maxSize);
        $baseSize = floor($playerCount / $numGroups);
        $remainder = $playerCount % $numGroups;

        $groups = array_fill(0, $numGroups, $baseSize);
        for ($i = 0; $i < $remainder; $i++) {
            $groups[$i]++;
        }

        return $groups;
    }

    /**
     * Assign players to group using snake draft
     */
    private function assignPlayersToGroup(
        Collection $players,
        int $groupIndex,
        int $totalGroups,
        int $groupSize,
    ): Collection {
        $assignedPlayers = collect();

        // Snake draft pattern: A-B-C-C-B-A-A-B-C...
        for ($round = 0; $round < $groupSize; $round++) {
            if ($round % 2 === 0) {
                // Forward direction
                $playerIndex = $round * $totalGroups + $groupIndex;
            } else {
                // Reverse direction
                $playerIndex = $round * $totalGroups + ($totalGroups - 1 - $groupIndex);
            }

            if (isset($players[$playerIndex])) {
                $assignedPlayers->push($players[$playerIndex]);
            }
        }

        return $assignedPlayers;
    }

    /**
     * Create round-robin matches within a group
     */
    private function createGroupMatches(Tournament $tournament, $group, Collection $players): void
    {
        foreach ($players as $i => $player1) {
            foreach ($players->slice($i + 1) as $player2) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'match_code'    => sprintf("%s_%sv%s", $group->group_code, $player1->seed_number,
                        $player2->seed_number),
                    'stage'         => MatchStage::GROUP,
                    'round'         => EliminationRound::GROUPS,
                    'player1_id'    => $player1->user_id,
                    'player2_id'    => $player2->user_id,
                    'races_to'      => $tournament->races_to,
                    'status'        => MatchStatus::READY,
                ]);
            }
        }
    }
}
