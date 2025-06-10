<?php

use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_players', function (Blueprint $table) {
            // Seeding and bracket information
            $table->integer('seed')->nullable()->after('position'); // Seeding position
            $table->integer('bracket_position')->nullable()->after('seed'); // Position in bracket
            $table->foreignIdFor(TournamentGroup::class,
                'group_id')->nullable()->constrained()->nullOnDelete(); // Reference to tournament_groups
            $table->foreignIdFor(TournamentTeam::class,
                'team_id')->nullable()->constrained()->nullOnDelete(); // Reference to tournament_groups
            $table->string('team_role')->nullable()->after('team_id'); // captain, player, substitute

            // Performance tracking
            $table->integer('matches_played')->default(0)->after('rating_points');
            $table->integer('matches_won')->default(0)->after('matches_played');
            $table->integer('matches_lost')->default(0)->after('matches_won');
            $table->integer('games_won')->default(0)->after('matches_lost');
            $table->integer('games_lost')->default(0)->after('games_won');
            $table->decimal('win_percentage', 5, 2)->default(0)->after('games_lost');

            // Additional tournament data
            $table->json('bracket_path')->nullable()->after('achievement_amount'); // Track progression through bracket
            $table->json('group_standings')->nullable()->after('bracket_path'); // Group stage performance

            // Add indexes
            $table->index(['tournament_id', 'seed']);
            $table->index(['tournament_id', 'group_id']);
            $table->index(['tournament_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['team_id']);

            $table->dropColumn([
                'seed',
                'bracket_position',
                'group_id',
                'team_id',
                'team_role',
                'matches_played',
                'matches_won',
                'matches_lost',
                'games_won',
                'games_lost',
                'win_percentage',
                'bracket_path',
                'group_standings',
            ]);
        });
    }
};
