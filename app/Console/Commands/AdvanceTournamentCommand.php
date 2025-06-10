<?php

namespace App\Console\Commands;

use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentBracket;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Services\TournamentManagementService;
use Illuminate\Console\Command;

class AdvanceTournamentCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tournament:advance
                           {--tournament= : Specific tournament ID to advance}
                           {--dry-run : Show what would be advanced without making changes}
                           {--force : Force advancement even if conditions are not met}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically advance tournaments based on completed matches';

    public function __construct(
        private readonly TournamentManagementService $managementService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tournamentId = $this->option('tournament');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($tournamentId) {
            $tournament = Tournament::find($tournamentId);
            if (!$tournament) {
                $this->error("Tournament with ID {$tournamentId} not found.");
                return 1;
            }
            $tournaments = collect([$tournament]);
        } else {
            // Get all active tournaments
            $tournaments = Tournament::where('status', 'active')
                ->with(['groups', 'brackets', 'matches'])
                ->get()
            ;
        }

        if ($tournaments->isEmpty()) {
            $this->info('No tournaments found to advance.');
            return 0;
        }

        $this->info("Found {$tournaments->count()} tournament(s) to check for advancement.");

        $advancedCount = 0;

        foreach ($tournaments as $tournament) {
            $this->line("Processing tournament: {$tournament->name} (ID: {$tournament->id})");

            try {
                $advanced = $this->advanceTournament($tournament, $dryRun, $force);
                if ($advanced) {
                    $advancedCount++;
                    $this->info("✓ Tournament {$tournament->name} was advanced.");
                } else {
                    $this->line("- Tournament {$tournament->name} does not need advancement.");
                }
            } catch (\Exception $e) {
                $this->error("✗ Error advancing tournament {$tournament->name}: ".$e->getMessage());
            }
        }

        $this->info("Advancement complete. {$advancedCount} tournament(s) were advanced.");

        return 0;
    }

    /**
     * Advance a specific tournament
     */
    private function advanceTournament(Tournament $tournament, bool $dryRun, bool $force): bool
    {
        $advanced = false;

        // Check group stage advancement
        if ($tournament->hasGroups()) {
            $advanced = $this->checkGroupAdvancement($tournament, $dryRun, $force) || $advanced;
        }

        // Check bracket advancement
        if ($tournament->hasBrackets()) {
            $advanced = $this->checkBracketAdvancement($tournament, $dryRun, $force) || $advanced;
        }

        // Check tournament completion
        $this->checkTournamentCompletion($tournament, $dryRun);

        return $advanced;
    }

    /**
     * Check and advance group stages
     */
    private function checkGroupAdvancement(Tournament $tournament, bool $dryRun, bool $force): bool
    {
        $advanced = false;

        foreach ($tournament->groups as $group) {
            if ($group->is_completed) {
                continue;
            }

            $totalMatches = $group->matches()->count();
            $completedMatches = $group->matches()->where('status', 'completed')->count();

            if ($totalMatches === 0) {
                continue;
            }

            $isComplete = $totalMatches === $completedMatches;

            if ($isComplete || $force) {
                $this->line("  Group {$group->name}: {$completedMatches}/{$totalMatches} matches complete");

                if (!$dryRun) {
                    $group->updateStandings();
                    $group->update(['is_completed' => true]);
                    $this->line("    ✓ Group standings updated and marked complete");
                } else {
                    $this->line("    [DRY RUN] Would update standings and mark complete");
                }

                $advanced = true;
            } else {
                $this->line("  Group {$group->name}: {$completedMatches}/{$totalMatches} matches complete (waiting)");
            }
        }

        // Check if all groups are complete and create playoff bracket
        if ($tournament->tournament_format === 'group_playoff') {
            $allGroupsComplete = $tournament->groups()->where('is_completed', false)->count() === 0;
            $hasBracketMatches = $tournament->matches()->where('match_type', 'bracket')->exists();

            if ($allGroupsComplete && !$hasBracketMatches) {
                $this->line("  All groups complete - creating playoff bracket");

                if (!$dryRun) {
                    $this->managementService->createBracketMatches($tournament);
                    $this->line("    ✓ Playoff bracket created");
                } else {
                    $this->line("    [DRY RUN] Would create playoff bracket");
                }

                $advanced = true;
            }
        }

        return $advanced;
    }

    /**
     * Check and advance bracket stages
     */
    private function checkBracketAdvancement(Tournament $tournament, bool $dryRun, bool $force): bool
    {
        $advanced = false;

        foreach ($tournament->brackets as $bracket) {
            if ($bracket->is_completed) {
                continue;
            }

            // Check if current round is complete
            $currentRound = $bracket->current_round;
            $roundMatches = $bracket
                ->matches()
                ->where('round_number', $currentRound)
                ->get()
            ;

            if ($roundMatches->isEmpty()) {
                continue;
            }

            $completedMatches = $roundMatches->where('status', 'completed');
            $isRoundComplete = $roundMatches->count() === $completedMatches->count();

            if ($isRoundComplete || $force) {
                $this->line("  Bracket {$bracket->bracket_type} Round {$currentRound}: {$completedMatches->count()}/{$roundMatches->count()} matches complete");

                if (!$dryRun) {
                    $bracket->advanceToNextRound($currentRound);
                    $this->line("    ✓ Bracket advanced to next round");
                } else {
                    $this->line("    [DRY RUN] Would advance bracket to next round");
                }

                $advanced = true;
            } else {
                $this->line("  Bracket {$bracket->bracket_type} Round {$currentRound}: {$completedMatches->count()}/{$roundMatches->count()} matches complete (waiting)");
            }
        }

        return $advanced;
    }

    /**
     * Check tournament completion
     */
    private function checkTournamentCompletion(Tournament $tournament, bool $dryRun): void
    {
        if ($tournament->status === 'completed') {
            return;
        }

        $isComplete = false;

        if ($tournament->hasBrackets()) {
            // Tournament is complete when all main/upper brackets are complete
            $mainBrackets = $tournament
                ->brackets()
                ->whereIn('bracket_type', ['main', 'upper'])
                ->get()
            ;

            $isComplete = $mainBrackets->isNotEmpty() &&
                $mainBrackets->every(fn($bracket) => $bracket->is_completed);
        } elseif ($tournament->hasGroups() && $tournament->tournament_format === 'group_stage') {
            // Group-only tournament is complete when all groups are complete
            $isComplete = $tournament
                    ->groups()
                    ->where('is_completed', false)
                    ->count() === 0;
        }

        if ($isComplete) {
            $this->line("  Tournament is ready for completion");

            if (!$dryRun) {
                $tournament->update(['status' => 'completed']);
                $this->line("    ✓ Tournament marked as completed");
            } else {
                $this->line("    [DRY RUN] Would mark tournament as completed");
            }
        }
    }
}
