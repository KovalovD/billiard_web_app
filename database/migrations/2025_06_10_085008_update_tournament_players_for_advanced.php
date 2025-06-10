<?php

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
            $table->integer('group_id')->nullable()->after('bracket_position'); // Reference to tournament_groups
            $table->integer('team_id')->nullable()->after('group_id'); // Reference to tournament_teams
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

            // Add foreign keys
            $table->foreign('group_id')->references('id')->on('tournament_groups')->nullOnDelete();
            $table->foreign('team_id')->references('id')->on('tournament_teams')->nullOnDelete();

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
