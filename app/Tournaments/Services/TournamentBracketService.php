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
use RuntimeException;
use Throwable;

class TournamentBracketService
{
    /**
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

            // Clear any existing bracket/match data
            $tournament->brackets()->delete();
            $tournament->matches()->delete();

            return match ($tournament->tournament_type) {
                TournamentType::SINGLE_ELIMINATION
                => $this->generateSingleEliminationBracket($tournament, $confirmedPlayers),
                TournamentType::DOUBLE_ELIMINATION,
                TournamentType::DOUBLE_ELIMINATION_FULL
                => $this->generateDoubleEliminationBracket($tournament, $confirmedPlayers),
                TournamentType::OLYMPIC_DOUBLE_ELIMINATION
                => $this->generateOlympicDoubleEliminationBracket($tournament, $confirmedPlayers),
                TournamentType::ROUND_ROBIN
                => $this->generateRoundRobinBracket($tournament, $confirmedPlayers),
                default
                => throw new RuntimeException("Bracket generation not supported for tournament type: {$tournament->tournament_type->value}"),
            };
        });
    }

    private function generateRoundRobinBracket(Tournament $tournament, Collection $confirmedPlayers): TournamentBracket
    {
        $playerCount = $confirmedPlayers->count();

        $bracket = TournamentBracket::create([
            'tournament_id'     => $tournament->id,
            'bracket_type'      => BracketType::SINGLE,
            'total_rounds'      => 1,
            'players_count'     => $playerCount,
            'bracket_structure' => [
                'type'          => 'round_robin',
                'total_matches' => ($playerCount * ($playerCount - 1)) / 2,
            ],
        ]);

        // Initialize player stats for round-robin
        foreach ($confirmedPlayers as $player) {
            $player->update([
                'group_wins'       => 0,
                'group_losses'     => 0,
                'group_games_diff' => 0,
            ]);
        }

        // Create all round-robin matches (every pair of players meets once)
        $matchNumber = 1;
        foreach ($confirmedPlayers as $i => $p1) {
            foreach ($confirmedPlayers->slice($i + 1) as $p2) {
                TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'       => sprintf('RR_M%d', $matchNumber++),
                    'stage' => MatchStage::GROUP,
                    'round' => EliminationRound::GROUPS,
                    'player1_id'       => $p1->user_id,
                    'player2_id'       => $p2->user_id,
                    'races_to'         => $tournament->races_to,
                    'status'           => MatchStatus::READY,
                    'bracket_position' => $matchNumber - 1,
                ]);
            }
        }

        return $bracket;
    }

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

        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);
        $this->createSingleEliminationMatches($tournament, $bracket, $seededBracket);

        if ($tournament->has_third_place_match) {
            $this->createThirdPlaceMatch($tournament);
        }

        return $bracket;
    }

    private function getNextPowerOfTwo(int $n): int
    {
        return 2 ** ceil(log($n, 2));
    }

    private function createSeededBracket(Collection $players, int $bracketSize): array
    {
        // Arrange players by seed number, and fill with null for byes up to bracketSize
        $seededBracket = [];
        foreach ($players as $player) {
            $seededBracket[$player->seed_number] = $player;
        }
        for ($seed = $players->count() + 1; $seed <= $bracketSize; $seed++) {
            $seededBracket[$seed] = null;
        }
        return $seededBracket;
    }

    private function createSingleEliminationMatches(
        Tournament $tournament,
        TournamentBracket $bracket,
        array $seededBracket,
    ): void {
        $bracketSize = $bracket->players_count;
        $rounds = $bracket->total_rounds;
        $previousRoundMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnum($matchesInRound);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf('R%sM%s', $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf('UB_R%s', $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                $currentRoundMatches = $this->setupMatchConnections(
                    $round,
                    $matchNum,
                    $bracketSize,
                    $seededBracket,
                    $match,
                    $tournament,
                    $previousRoundMatches,
                    $currentRoundMatches,
                );
            }

            $previousRoundMatches = $currentRoundMatches;
        }

        $this->progressWalkoverWinners($tournament);
    }

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
            // Assign players for first round match based on seeding
            $matchup = $this->getFirstRoundMatchup($matchNum, $bracketSize);
            $player1 = $seededBracket[$matchup[0]] ?? null;
            $player2 = $seededBracket[$matchup[1]] ?? null;

            if ($player1) {
                $match->player1_id = $player1->user_id;
            }
            if ($player2) {
                $match->player2_id = $player2->user_id;
            }

            if ($player1 && !$player2) {
                // Player1 has no opponent (bye) – mark as walkover win
                $match->winner_id = $player1->user_id;
                $match->status = MatchStatus::COMPLETED;
                $match->player1_score = $match->races_to;
                $match->player2_score = 0;
                $match->completed_at = now();
                $match->admin_notes = 'Walkover - No opponent';
            } elseif (!$player1 && $player2) {
                // Player2 has no opponent (bye)
                $match->winner_id = $player2->user_id;
                $match->status = MatchStatus::COMPLETED;
                $match->player1_score = 0;
                $match->player2_score = $match->races_to;
                $match->completed_at = now();
                $match->admin_notes = 'Walkover - No opponent';
            } elseif ($player1 && $player2) {
                // Both players present – match is ready to be played
                $match->status = MatchStatus::READY;
            }
        } else {
            // Connect winners from previous round to this match
            $this->setUpProgressionFromPreviousRound($matchNum, $previousRoundMatches, $match);
        }

        $match->save();
        $currentRoundMatches[] = $match;
        return $currentRoundMatches;
    }

    private function getFirstRoundMatchup(int $matchNum, int $bracketSize): array
    {
        $matchups = $this->generateSeedMatchups($bracketSize);
        return $matchups[$matchNum] ?? [1, 2];
    }

    private function generateSeedMatchups(int $bracketSize): array
    {
        // Pair top half vs bottom half seeds
        $matchups = [];
        $seeds = range(1, $bracketSize);
        $top = array_slice($seeds, 0, $bracketSize / 2);
        $bottom = array_reverse(array_slice($seeds, $bracketSize / 2));

        for ($i = 0, $max = count($top); $i < $max; $i++) {
            $matchups[] = [$top[$i], $bottom[$i]];
        }

        return $this->arrangeBracketMatchups($matchups);
    }

    private function arrangeBracketMatchups(array $matchups): array
    {
        // Arrange matchups in bracket order to maintain seed distribution
        $arranged = [];
        $indices = $this->getBracketIndices(count($matchups));
        foreach ($indices as $i => $index) {
            $arranged[$i] = $matchups[$index];
        }
        return $arranged;
    }

    private function getBracketIndices(int $size): array
    {
        // Predefined bracket index patterns for common sizes
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
                0, 31, 15, 16, 7, 24, 8, 23,
                3, 28, 12, 19, 4, 27, 11, 20,
                1, 30, 14, 17, 6, 25, 9, 22,
                2, 29, 13, 18, 5, 26, 10, 21,
            ];
        }

        // Recursively build bracket indices for larger sizes (64, 128)
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
        // Link the winners from two matches of the previous round into the current match
        $prev1 = $matchNum * 2;
        $prev2 = $matchNum * 2 + 1;

        if (isset($previousRoundMatches[$prev1])) {
            $match->previous_match1_id = $previousRoundMatches[$prev1]->id;
            $previousRoundMatches[$prev1]->next_match_id = $match->id;
            $previousRoundMatches[$prev1]->save();
        }
        if (isset($previousRoundMatches[$prev2])) {
            $match->previous_match2_id = $previousRoundMatches[$prev2]->id;
            $previousRoundMatches[$prev2]->next_match_id = $match->id;
            $previousRoundMatches[$prev2]->save();
        }
    }

    private function progressWalkoverWinners(Tournament $tournament): void
    {
        // Automatically advance winners of walkover matches to the next round
        $walkoverMatches = $tournament
            ->matches()
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($q) {
                $q
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
        if (!$match->next_match_id || !$match->winner_id) {
            return;
        }

        $next = TournamentMatch::find($match->next_match_id);
        if (!$next) {
            return;
        }

        // Place the winner in the correct slot of the next match
        if ($next->previous_match1_id === $match->id) {
            $next->player1_id = $match->winner_id;
        } else {
            $next->player2_id = $match->winner_id;
        }

        if ($next->player1_id && $next->player2_id) {
            // Next match is ready to be played once both slots are filled
            $next->status = MatchStatus::READY;
        }
        $next->save();
    }

    private function createThirdPlaceMatch(Tournament $tournament): void
    {
        // If the bracket requires a third-place playoff (for single elimination)
        $thirdPlace = TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'match_code'    => '3RD',
            'stage'         => MatchStage::THIRD_PLACE,
            'round'         => EliminationRound::THIRD_PLACE,
            'races_to' => $this->getRaceToForRound($tournament, '3RD'),
            'status'        => MatchStatus::PENDING,
        ]);

        // Losers of the semifinals drop into the third-place match
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

    private function generateDoubleEliminationBracket(
        Tournament $tournament,
        Collection $confirmedPlayers,
    ): TournamentBracket {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $upperRounds = (int) log($bracketSize, 2);

        // Create Upper Bracket record
        $upperBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_UPPER,
            'total_rounds'  => $upperRounds,
            'players_count' => $bracketSize,
        ]);

        // Create Lower Bracket record
        $lowerRounds = ($upperRounds - 1) * 2;
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_LOWER,
            'total_rounds'  => $lowerRounds,
            'players_count' => $bracketSize - 1,
        ]);

        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);
        $upperMatches = $this->createUpperBracketMatches($tournament, $upperBracket, $seededBracket);
        $lowerFinal = $this->createLowerBracketComplete($tournament, $bracketSize, $upperRounds, $upperMatches);
        $upperFinal = $upperMatches[$upperRounds][0] ?? null;

        $this->createGrandFinals($tournament, $upperFinal, $lowerFinal);

        return $upperBracket;
    }

    private function createUpperBracketMatches(
        Tournament $tournament,
        TournamentBracket $bracket,
        array $seededBracket,
    ): array {
        $bracketSize = $bracket->players_count;
        $rounds = $bracket->total_rounds;
        $previousRoundMatches = [];
        $allUpperMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnumForDouble($round, $rounds);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf('UB_R%sM%s', $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf('UB_R%s', $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                $currentRoundMatches = $this->setupMatchConnections(
                    $round,
                    $matchNum,
                    $bracketSize,
                    $seededBracket,
                    $match,
                    $tournament,
                    $previousRoundMatches,
                    $currentRoundMatches,
                );
            }

            $allUpperMatches[$round] = $currentRoundMatches;
            $previousRoundMatches = $currentRoundMatches;
        }

        // Handle any walkover winners in the upper bracket (e.g., byes) and drop losers to lower bracket
        $this->progressWalkoverWinnersInBracket($tournament);
        return $allUpperMatches;
    }

    private function getRoundEnumForDouble(int $round, int $totalRounds): EliminationRound
    {
        // Similar to getRoundEnum, but for double elimination we consider remaining rounds in upper bracket
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

    private function progressWalkoverWinnersInBracket(Tournament $tournament): void
    {
        // Advance winners of any walkover matches in the upper bracket and place losers into the lower bracket
        $walkoverMatches = $tournament
            ->matches()
            ->where('bracket_side', 'upper')
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($q) {
                $q
                    ->whereNull('player1_id')
                    ->orWhereNull('player2_id')
                ;
            })
            ->get()
        ;

        foreach ($walkoverMatches as $match) {
            $this->progressWinnerToNextMatch($match);

            // If there was a loser (i.e., one player present, one bye), drop that loser into the lower bracket
            if ($match->loser_next_match_id) {
                $loserId = ($match->winner_id === $match->player1_id)
                    ? $match->player2_id
                    : $match->player1_id;
                if (!$loserId) {
                    continue;
                }

                $loserMatch = TournamentMatch::find($match->loser_next_match_id);
                if (!$loserMatch) {
                    continue;
                }

                // Place the loser in the designated slot (player1 or player2) of the next lower bracket match
                $pos = $match->loser_next_match_position ?? null;
                if ($pos === 1) {
                    $loserMatch->player1_id = $loserId;
                } elseif ($pos === 2) {
                    $loserMatch->player2_id = $loserId;
                } elseif (!$loserMatch->player1_id) {
                    $loserMatch->player1_id = $loserId;
                } else {
                    $loserMatch->player2_id = $loserId;
                }

                if ($loserMatch->player1_id && $loserMatch->player2_id) {
                    $loserMatch->status = MatchStatus::READY;
                }
                $loserMatch->save();
            }
        }
    }

    private function createLowerBracketComplete(
        Tournament $tournament,
        int $bracketSize,
        int $upperRounds,
        array $upperMatches,
    ): ?TournamentMatch {
        $lowerStructure = $this->getLowerBracketStructure($bracketSize);
        $allLowerMatches = [];
        $dropCount = 0;  // Counter to alternate drop patterns

        foreach ($lowerStructure as $round => $roundData) {
            $currentRoundMatches = [];
            $roundEnum = $this->getLowerRoundEnum($round, count($lowerStructure));
            // Increment drop counter for each phase where players drop from the upper bracket
            if (in_array($roundData['type'], ['initial', 'drop', 'final_drop'])) {
                $dropCount++;
            }

            for ($matchNum = 0; $matchNum < $roundData['matches']; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf('LB_R%sM%s', $round, $matchNum + 1),
                    'stage'      => MatchStage::LOWER_BRACKET,
                    'round'      => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'lower',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf('LB_R%s', $round)),
                    'status'           => MatchStatus::PENDING,
                ]);

                if ($roundData['type'] === 'initial') {
                    // **Initial losers bracket round**: regular order pairing (no immediate rematches)
                    $totalUpperMatches = $roundData['matches'] * 2;
                    $isReversed = ($dropCount % 2 === 0);  // dropCount=1 here, so $isReversed = false
                    if (!$isReversed) {
                        // Regular order: pair losers sequentially (e.g., match0 vs match1 losers, match2 vs match3 losers, etc.)
                        $u1 = $matchNum * 2;
                        $u2 = $matchNum * 2 + 1;
                    } else {
                        // Reversed order (not expected for first drop, but handling for completeness)
                        $reorderedIndices = $this->getAlternatingLoserIndices($totalUpperMatches);
                        $u1 = $reorderedIndices[$matchNum * 2];
                        $u2 = $reorderedIndices[$matchNum * 2 + 1];
                    }
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
                    // **Intermediate losers bracket round**: alternate pairing order
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    if (isset($prev[$matchNum])) {
                        // Link the winner from the previous lower bracket round
                        $match->previous_match1_id = $prev[$matchNum]->id;
                        $prev[$matchNum]->next_match_id = $match->id;
                        $prev[$matchNum]->save();
                    }
                    $uRound = $roundData['upper_round'];
                    $totalUpperMatches = $roundData['matches'];
                    $isReversed = ($dropCount % 2 === 0);
                    if (!$isReversed) {
                        // Regular order: take the loser from the corresponding upper bracket match
                        $index = $matchNum;
                        if (isset($upperMatches[$uRound][$index])) {
                            $upperMatches[$uRound][$index]->loser_next_match_id = $match->id;
                            $upperMatches[$uRound][$index]->loser_next_match_position = 2;
                            $upperMatches[$uRound][$index]->save();
                        }
                    } else {
                        // Reversed order: take the loser from the opposite end of the upper bracket round
                        $reversedIndex = $totalUpperMatches - 1 - $matchNum;
                        if (isset($upperMatches[$uRound][$reversedIndex])) {
                            $upperMatches[$uRound][$reversedIndex]->loser_next_match_id = $match->id;
                            $upperMatches[$uRound][$reversedIndex]->loser_next_match_position = 2;
                            $upperMatches[$uRound][$reversedIndex]->save();
                        }
                    }
                } elseif ($roundData['type'] === 'regular') {
                    // Regular losers bracket round (no new drops from upper bracket)
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    $this->setUpProgressionFromPreviousRound($matchNum, $prev, $match);
                } elseif ($roundData['type'] === 'final_drop') {
                    // **Final drop**: loser of the upper bracket final drops to lower bracket final
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    if (isset($prev[0])) {
                        // Winner of previous lower bracket round goes to this match
                        $match->previous_match1_id = $prev[0]->id;
                        $prev[0]->next_match_id = $match->id;
                        $prev[0]->save();
                    }
                    if (isset($upperMatches[$upperRounds][0])) {
                        // Loser of the upper bracket Final goes to this match (always as player2)
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

        // Return the final match of the lower bracket (the Lower Bracket Final)
        $lastRound = max(array_keys($allLowerMatches));
        return $allLowerMatches[$lastRound][0] ?? null;
    }

    /**
     * Get alternating indices to maximize separation between players from the same upper bracket section.
     * (Used for pairing initial drop-down matches in losers bracket)
     */
    private function getAlternatingLoserIndices(int $totalMatches): array
    {
        $indices = [];
        $half = $totalMatches / 2;
        // Alternate between the first half and second half of the matches
        for ($i = 0; $i < $half; $i++) {
            $indices[] = $i;
            $indices[] = $totalMatches - 1 - $i;
        }
        return $indices;
    }

    private function getLowerBracketStructure(int $bracketSize): array
    {
        // Define the structure of the lower bracket for various bracket sizes
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

    private function getLowerRoundEnum(int $round, int $totalRounds): EliminationRound
    {
        // Determine elimination round name for lower bracket rounds based on how many rounds remain after this
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
            $currentPlayers = $roundData['type'] === 'initial'
                ? $roundData['matches'] * 2
                : (int) ceil($currentPlayers / 2);

            if ($currentPlayers === $lowerAdvancing) {
                $targetRound = $round;
                while (
                    isset($fullLowerStructure[$targetRound]) &&
                    $fullLowerStructure[$targetRound]['matches'] > $lowerAdvancing
                ) {
                    ++$targetRound;
                }
                $nextType = $fullLowerStructure[$targetRound + 1]['type'] ?? null;
                if (
                    $nextType &&
                    in_array($nextType, ['drop', 'final_drop'], true) &&
                    $fullLowerStructure[$targetRound]['type'] !== 'drop'
                ) {
                    ++$targetRound;
                }
                break;
            }
        }

        if ($targetRound === 0) {
            throw new RuntimeException('Cannot calculate lower bracket rounds for Olympic phase');
        }

        foreach ($fullLowerStructure as $round => $roundData) {
            if ($round > $targetRound) {
                break;
            }

            $currentRoundMatches = [];
            $roundEnum = $this->getLowerRoundEnum($round, $targetRound);

            for ($matchNum = 0; $matchNum < $roundData['matches']; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code' => sprintf('FS_LB_R%sM%s', $round, $matchNum + 1),
                    'stage'            => MatchStage::LOWER_BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'lower',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf('LB_R%s', $round)),
                    'status'           => MatchStatus::PENDING,
                    'metadata'         => ['olympic_stage' => 'first'],
                ]);

                if ($roundData['type'] === 'initial') {
                    // Apply same alternating logic for Olympic stage
                    $totalUpperMatches = $roundData['matches'] * 2;
                    $reorderedIndices = $this->getAlternatingLoserIndices($totalUpperMatches);

                    $u1 = $reorderedIndices[$matchNum * 2];
                    $u2 = $reorderedIndices[$matchNum * 2 + 1];

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
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    if (isset($prev[$matchNum])) {
                        $match->previous_match1_id = $prev[$matchNum]->id;
                        $prev[$matchNum]->next_match_id = $match->id;
                        $prev[$matchNum]->save();
                    }
                    $uRound = $roundData['upper_round'];

                    // Reverse the order for drop rounds in Olympic stage too
                    $totalUpperMatches = $roundData['matches'];
                    $reversedIndex = $totalUpperMatches - 1 - $matchNum;

                    if (isset($upperMatches[$uRound][$reversedIndex])) {
                        $upperMatches[$uRound][$reversedIndex]->loser_next_match_id = $match->id;
                        $upperMatches[$uRound][$reversedIndex]->loser_next_match_position = 2;
                        $upperMatches[$uRound][$reversedIndex]->save();
                    }
                } elseif ($roundData['type'] === 'regular') {
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    $this->setUpProgressionFromPreviousRound($matchNum, $prev, $match);
                } elseif ($roundData['type'] === 'final_drop') {
                    $prev = $allLowerMatches[$round - 1] ?? [];
                    if (isset($prev[0])) {
                        $match->previous_match1_id = $prev[0]->id;
                        $prev[0]->next_match_id = $match->id;
                        $prev[0]->save();
                    }
                    $uRound = $roundData['upper_round'];
                    if (isset($upperMatches[$uRound][0])) {
                        $upperMatches[$uRound][0]->loser_next_match_id = $match->id;
                        $upperMatches[$uRound][0]->loser_next_match_position = 2;
                        $upperMatches[$uRound][0]->save();
                    }
                }

                $match->save();
                $currentRoundMatches[] = $match;
            }

            $allLowerMatches[$round] = $currentRoundMatches;
        }

        $this->connectRemainingUpperBracketLosers($upperMatches, $allLowerMatches, $olympicPhaseRound);
        $this->markOlympicAdvancingMatches(
            $upperMatches,
            $allLowerMatches,
            $olympicPhaseRound,
            $upperAdvancing,
            $lowerAdvancing,
        );
    }

    private function connectRemainingUpperBracketLosers(
        array $upperMatches,
        array $lowerMatches,
        int $olympicPhaseRound,
    ): void {
        for ($round = 1; $round < $olympicPhaseRound - 1; $round++) {
            if (!isset($upperMatches[$round])) {
                continue;
            }

            foreach ($upperMatches[$round] as $upperMatch) {
                if ($upperMatch->loser_next_match_id) {
                    continue;
                }

                $target = $this->findLowerBracketMatchForUpperLoser($upperMatch, $lowerMatches, $round);
                if (!$target) {
                    continue;
                }

                $upperMatch->loser_next_match_id = $target->id;
                $upperMatch->loser_next_match_position = 2;
                $upperMatch->save();
            }
        }
    }

    private function findLowerBracketMatchForUpperLoser(
        TournamentMatch $upperMatch,
        array $lowerMatches,
        int $upperRound,
    ): ?TournamentMatch {
        foreach ($lowerMatches as $lowerRound => $matches) {
            foreach ($matches as $lowerMatch) {
                if ($lowerMatch->previous_match2_id) {
                    continue;
                }
                if (!str_contains($lowerMatch->match_code, 'FS_LB_R')) {
                    continue;
                }
                $num = (int) preg_replace('/[^0-9]/', '', $lowerMatch->match_code);
                if ($num % 2 === 0 && $num / 2 === $upperRound) {
                    return $lowerMatch;
                }
            }
        }
        return null;
    }

    private function markOlympicAdvancingMatches(
        array $upperMatches,
        array $lowerMatches,
        int $olympicPhaseRound,
        int $upperAdvancing,
        int $lowerAdvancing,
    ): void {
        $lastUpperRound = $olympicPhaseRound - 1;
        $upperAdv = $upperMatches[$lastUpperRound] ?? [];
        $lastLowerRound = !empty($lowerMatches) ? max(array_keys($lowerMatches)) : null;
        $lowerAdv = $lastLowerRound !== null ? ($lowerMatches[$lastLowerRound] ?? []) : [];

        if (count($upperAdv) !== $upperAdvancing || count($lowerAdv) !== $lowerAdvancing) {
            throw new RuntimeException('Advancing match count mismatch between upper and lower brackets');
        }

        $pos = 0;
        for ($i = 0; $i < $upperAdvancing; $i++) {
            $m = $upperAdv[$i];
            $m->metadata = array_merge($m->metadata ?? [],
                ['advances_to_olympic' => true, 'olympic_position' => $pos++]);
            $m->save();

            $m = $lowerAdv[$i];
            $m->metadata = array_merge($m->metadata ?? [],
                ['advances_to_olympic' => true, 'olympic_position' => $pos++]);
            $m->save();
        }
    }

    private function progressWalkoverWinnersInOlympicFirstStage(Tournament $tournament): void
    {
        $walkoverMatches = $tournament
            ->matches()
            ->where('status', MatchStatus::COMPLETED)
            ->whereNotNull('winner_id')
            ->where(function ($q) {
                $q->whereNull('player1_id')->orWhereNull('player2_id');
            })
            ->whereJsonContains('metadata->olympic_stage', 'first')
            ->get()
        ;

        foreach ($walkoverMatches as $match) {
            $adv = $match->metadata['advances_to_olympic'] ?? false;
            if ($adv) {
                $this->progressWinnerToOlympicStageFromWalkover($match);
            } else {
                if ($match->next_match_id) {
                    $this->progressWinnerToNextMatch($match);
                }

                if ($match->loser_next_match_id) {
                    $loserId = $match->winner_id === $match->player1_id ? $match->player2_id : $match->player1_id;
                    if ($loserId) {
                        $loserMatch = TournamentMatch::find($match->loser_next_match_id);
                        if ($loserMatch) {
                            $pos = $match->loser_next_match_position ?? null;
                            if ($pos === 1) {
                                $loserMatch->player1_id = $loserId;
                            } elseif ($pos === 2) {
                                $loserMatch->player2_id = $loserId;
                            } elseif (!$loserMatch->player1_id) {
                                $loserMatch->player1_id = $loserId;
                            } else {
                                $loserMatch->player2_id = $loserId;
                            }

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

    private function progressWinnerToOlympicStageFromWalkover(TournamentMatch $match): void
    {
        if (!$match->winner_id || !isset($match->metadata['olympic_position'])) {
            return;
        }

        $pos = $match->metadata['olympic_position'];
        $tournament = $match->tournament;
        $idx = floor($pos / 2);
        $num = $idx + 1;

        $olMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('match_code', 'OS_R1M'.$num)
            ->whereJsonContains('metadata->olympic_stage', 'second')
            ->first()
        ;

        if (!$olMatch) {
            return;
        }

        if (!$olMatch->player1_id) {
            $olMatch->player1_id = $match->winner_id;
        } elseif (!$olMatch->player2_id) {
            $olMatch->player2_id = $match->winner_id;
        }

        if ($olMatch->player1_id && $olMatch->player2_id) {
            $olMatch->status = MatchStatus::READY;
        }
        $olMatch->save();
    }

    private function createGrandFinals(
        Tournament $tournament,
        ?TournamentMatch $upperFinal,
        ?TournamentMatch $lowerFinal,
    ): void {
        $grandFinal = TournamentMatch::create([
            'tournament_id'      => $tournament->id,
            'match_code'         => 'GF',
            'stage'              => MatchStage::BRACKET,
            'round'              => EliminationRound::GRAND_FINALS,
            'bracket_position'   => 0,
            'races_to' => $this->getRaceToForRound($tournament, 'GF'),
            'status'             => MatchStatus::PENDING,
            'previous_match1_id' => $upperFinal?->id,
            'previous_match2_id' => $lowerFinal?->id,
        ]);

        if ($upperFinal) {
            $upperFinal->next_match_id = $grandFinal->id;
            $upperFinal->save();
        }
        if ($lowerFinal) {
            $lowerFinal->next_match_id = $grandFinal->id;
            $lowerFinal->save();
        }
    }

    private function generateOlympicDoubleEliminationBracket(
        Tournament $tournament,
        Collection $confirmedPlayers,
    ): TournamentBracket {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = $this->getNextPowerOfTwo($playerCount);
        $upperRounds = (int) log($bracketSize, 2);
        $olympicPhaseSize = $tournament->olympic_phase_size ?? 8;
        if ($olympicPhaseSize > $bracketSize) {
            $olympicPhaseSize = $bracketSize;
        }
        if ($olympicPhaseSize === $bracketSize) {
            return $this->generateSingleEliminationBracket($tournament, $confirmedPlayers);
        }

        $olympicPhaseRound = $upperRounds - (int) log($olympicPhaseSize / 2, 2) + 1;
        $upperBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_UPPER,
            'total_rounds' => $olympicPhaseRound - 1,
            'players_count' => $bracketSize,
            'metadata'     => ['stage' => 'first_stage', 'olympic_phase_size' => $olympicPhaseSize],
        ]);

        $lowerRounds = ($olympicPhaseRound - 1) * 2 - 1;
        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_LOWER,
            'total_rounds'  => $lowerRounds,
            'players_count' => $bracketSize - 1,
            'metadata'      => ['stage' => 'first_stage', 'olympic_phase_size' => $olympicPhaseSize],
        ]);

        TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds'  => (int) log($olympicPhaseSize, 2),
            'players_count' => $olympicPhaseSize,
            'metadata'      => ['stage' => 'olympic_stage'],
        ]);

        $seededBracket = $this->createSeededBracket($confirmedPlayers, $bracketSize);
        $this->createOlympicFirstStage($tournament, $upperBracket, $seededBracket, $olympicPhaseRound,
            $olympicPhaseSize);
        $this->createOlympicSecondStage($tournament, $olympicPhaseSize);

        return $upperBracket;
    }

    private function createOlympicFirstStage(
        Tournament $tournament,
        TournamentBracket $upperBracket,
        array $seededBracket,
        int $olympicPhaseRound,
        int $olympicPhaseSize,
    ): void {
        $bracketSize = $upperBracket->players_count;
        $rounds = $olympicPhaseRound - 1;
        $previousRoundMatches = [];
        $allUpperMatches = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $roundEnum = $this->getRoundEnumForDouble($round, $rounds);
            $currentRoundMatches = [];

            for ($matchNum = 0; $matchNum < $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'   => sprintf('FS_UB_R%sM%s', $round, $matchNum + 1),
                    'stage'        => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side' => 'upper',
                    'races_to'     => $this->getRaceToForRound($tournament, sprintf('UB_R%s', $round)),
                    'status'           => MatchStatus::PENDING,
                    'metadata'     => ['olympic_stage' => 'first'],
                ]);

                $currentRoundMatches = $this->setupMatchConnections(
                    $round,
                    $matchNum,
                    $bracketSize,
                    $seededBracket,
                    $match,
                    $tournament,
                    $previousRoundMatches,
                    $currentRoundMatches,
                );
            }

            $allUpperMatches[$round] = $currentRoundMatches;
            $previousRoundMatches = $currentRoundMatches;
        }

        $this->createOlympicFirstStageLowerBracket($tournament, $bracketSize, $olympicPhaseRound, $allUpperMatches,
            $olympicPhaseSize);
        $this->progressWalkoverWinnersInOlympicFirstStage($tournament);
    }

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
                    'match_code' => sprintf('OS_R%sM%s', $round, $matchNum + 1),
                    'stage'            => MatchStage::BRACKET,
                    'round'            => $roundEnum,
                    'bracket_position' => $matchNum,
                    'bracket_side'     => 'upper',
                    'races_to'   => $this->getRaceToForRound($tournament, sprintf('O_R%s', $round)),
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

        if ($olympicPhaseSize > 4 && $tournament->olympic_has_third_place) {
            $this->createOlympicThirdPlaceMatch($tournament);
        }
    }

    private function createOlympicThirdPlaceMatch(Tournament $tournament): void
    {
        $thirdPlace = TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'match_code' => 'OS_3RD',
            'stage'         => MatchStage::THIRD_PLACE,
            'round'         => EliminationRound::THIRD_PLACE,
            'races_to'   => $this->getRaceToForRound($tournament, 'O_3RD'),
            'status'        => MatchStatus::PENDING,
            'metadata'   => ['olympic_stage' => 'second'],
        ]);

        $semis = $tournament
            ->matches()
            ->where('match_code', 'LIKE', 'OS_%')
            ->where('round', EliminationRound::SEMIFINALS)
            ->get()
        ;

        foreach ($semis as $semi) {
            $semi->loser_next_match_id = $thirdPlace->id;
            $semi->save();
        }
    }

    private function getRaceToForRound(Tournament $tournament, string $roundCode): int
    {
        $roundRacesTo = $tournament->round_races_to ?? [];
        if (isset($roundRacesTo[$roundCode])) {
            return (int) $roundRacesTo[$roundCode];
        }
        return (int) ($tournament->races_to ?? 7);
    }

    /**
     * @throws Throwable
     */
    public function generateGroups(Tournament $tournament): array
    {
        return DB::transaction(function () use ($tournament) {
            if (!in_array($tournament->tournament_type->value,
                ['groups', 'groups_playoff', 'round_robin', 'team_groups_playoff'])) {
                throw new RuntimeException('Tournament type does not support group stage');
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

            $tournament->groups()->delete();

            $playerCount = $confirmedPlayers->count();
            $minGroupSize = $tournament->group_size_min ?? 4;
            $maxGroupSize = $tournament->group_size_max ?? 5;
            $groupConfig = $this->calculateGroupConfiguration($playerCount, $minGroupSize, $maxGroupSize);
            $groups = [];

            foreach ($groupConfig as $idx => $size) {
                $code = chr(65 + $idx);
                $group = $tournament->groups()->create([
                    'group_code' => $code,
                    'group_size' => $size,
                    'advance_count' => $tournament->playoff_players_per_group ?? 2,
                ]);

                $players = $this->assignPlayersToGroup($confirmedPlayers, $idx, count($groupConfig), $size);
                foreach ($players as $pl) {
                    $pl->update(['group_code' => $code]);
                }
                $this->createGroupMatches($tournament, $group, $players);
                $groups[] = $group;
            }

            return $groups;
        });
    }

    private function calculateGroupConfiguration(int $playerCount, int $minSize, int $maxSize): array
    {
        for ($size = $maxSize; $size >= $minSize; $size--) {
            if ($playerCount % $size === 0) {
                return array_fill(0, $playerCount / $size, $size);
            }
        }

        $numGroups = ceil($playerCount / $maxSize);
        $baseSize = floor($playerCount / $numGroups);
        $extra = $playerCount % $numGroups;
        $groups = array_fill(0, $numGroups, $baseSize);
        for ($i = 0; $i < $extra; $i++) {
            $groups[$i]++;
        }
        return $groups;
    }

    private function assignPlayersToGroup(
        Collection $players,
        int $groupIndex,
        int $totalGroups,
        int $groupSize,
    ): Collection {
        $assigned = collect();
        for ($round = 0; $round < $groupSize; $round++) {
            $idx = $round % 2 === 0
                ? $round * $totalGroups + $groupIndex
                : $round * $totalGroups + ($totalGroups - 1 - $groupIndex);
            if (isset($players[$idx])) {
                $assigned->push($players[$idx]);
            }
        }
        return $assigned;
    }

    private function createGroupMatches(Tournament $tournament, $group, Collection $players): void
    {
        foreach ($players as $i => $p1) {
            foreach ($players->slice($i + 1) as $p2) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'match_code' => sprintf('%s_%sv%s', $group->group_code, $p1->seed_number, $p2->seed_number),
                    'stage'         => MatchStage::GROUP,
                    'round'         => EliminationRound::GROUPS,
                    'player1_id' => $p1->user_id,
                    'player2_id' => $p2->user_id,
                    'races_to'      => $tournament->races_to,
                    'status'        => MatchStatus::READY,
                ]);
            }
        }
    }
}
