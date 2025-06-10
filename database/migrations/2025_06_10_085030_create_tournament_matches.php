<?php

use App\Core\Models\Club;
use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();

            // Match identification
            $table->string('match_type'); // group, bracket, final
            $table->integer('round_number'); // Round in tournament
            $table->integer('match_number'); // Match number within round
            $table->string('bracket_type')->nullable(); // upper, lower, final

            // Group stage specific
            $table->integer('group_id')->nullable();

            // Participants (can be players or teams)
            $table->integer('participant_1_id')->nullable(); // tournament_players.id or tournament_teams.id
            $table->string('participant_1_type')->default('player'); // player or team
            $table->integer('participant_2_id')->nullable();
            $table->string('participant_2_type')->default('player');

            // Match details
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->json('scores')->nullable(); // Store game-by-game scores
            $table->integer('participant_1_score')->default(0); // Games/sets won
            $table->integer('participant_2_score')->default(0);
            $table->integer('winner_id')->nullable(); // Winner participant ID
            $table->string('winner_type')->nullable(); // player or team

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('table_number')->nullable();
            $table->foreignIdFor(Club::class)->nullable()->constrained();

            // Additional data
            $table->text('notes')->nullable();
            $table->string('referee')->nullable();
            $table->json('match_data')->nullable(); // Additional match-specific data

            $table->timestamps();

            // Add foreign key for group_id
            $table->foreign('group_id')->references('id')->on('tournament_groups')->nullOnDelete();

            // Indexes
            $table->index(['tournament_id', 'status']);
            $table->index(['tournament_id', 'match_type', 'round_number']);
            $table->index(['tournament_id', 'group_id']);
            $table->index(['scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
