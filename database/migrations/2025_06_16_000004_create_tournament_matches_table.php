<?php

use App\Core\Models\User;
use App\Tournaments\Models\Tournament;
use App\Core\Models\ClubTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();

            $table->string('match_code')->nullable();
            $table->string('stage')->default('bracket');
            $table->string('round')->nullable();
            $table->integer('bracket_position')->nullable();
            $table->string('bracket_side')->nullable()->default('upper');

            $table->foreignId('player1_id')->nullable()->constrained('users');
            $table->foreignId('player2_id')->nullable()->constrained('users');
            $table->foreignId('winner_id')->nullable()->constrained('users');

            $table->integer('player1_score')->default(0);
            $table->integer('player2_score')->default(0);
            $table->integer('races_to')->nullable();

            $table->foreignId('previous_match1_id')->nullable()->constrained('tournament_matches');
            $table->foreignId('previous_match2_id')->nullable()->constrained('tournament_matches');
            $table->foreignId('next_match_id')->nullable()->constrained('tournament_matches');
            $table->foreignId('loser_next_match_id')->nullable()->constrained('tournament_matches');

            $table->foreignId('club_table_id')->nullable()->constrained('club_tables');
            $table->string('stream_url')->nullable();

            $table->string('status')->default('pending');

            $table->datetime('scheduled_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();

            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'stage']);
            $table->index(['tournament_id', 'round']);
            $table->index(['tournament_id', 'status']);
            $table->unique(['tournament_id', 'match_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
