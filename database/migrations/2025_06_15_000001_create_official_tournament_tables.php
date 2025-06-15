<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		// Main tournaments table
		Schema::create('official_tournaments', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('discipline', 50);
			$table->timestamp('start_at')->nullable();
			$table->timestamp('end_at')->nullable();
			$table->foreignId('city_id')->nullable()->constrained('cities');
			$table->foreignId('club_id')->nullable()->constrained('clubs');
			$table->decimal('entry_fee', 10, 2)->default(0);
			$table->decimal('prize_pool', 12, 2)->default(0);
			$table->json('format')->nullable();
			$table->timestamps();
		});

		// Tournament stages (single_elim, double_elim, swiss, group)
		Schema::create('official_stages', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
			$table->string('type', 30); // single_elim, double_elim, swiss, group
			$table->integer('number')->default(1);
			$table->json('settings')->nullable();
			$table->timestamps();
		});

		// Teams for team tournaments
		Schema::create('official_teams', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
			$table->string('name');
			$table->foreignId('club_id')->nullable()->constrained('clubs');
			$table->integer('seed')->nullable();
			$table->timestamps();
		});

		// Team members
		Schema::create('official_team_members', function (Blueprint $table) {
			$table->id();
			$table->foreignId('team_id')->constrained('official_teams')->cascadeOnDelete();
			$table->foreignId('user_id')->constrained('users');
			$table->timestamps();

			$table->unique(['team_id', 'user_id']);
		});

		// Tournament/stage participants
		Schema::create('official_participants', function (Blueprint $table) {
			$table->id();
			$table->foreignId('stage_id')->constrained('official_stages')->cascadeOnDelete();
			$table->foreignId('user_id')->nullable()->constrained('users');
			$table->foreignId('team_id')->nullable()->constrained('official_teams');
			$table->integer('seed')->nullable();
			$table->integer('rating_snapshot')->nullable();
			$table->timestamps();
		});

		// Pool tables for scheduling
		Schema::create('official_pool_tables', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
			$table->string('name', 50)->nullable();
			$table->string('cloth_speed', 30)->nullable();
			$table->timestamps();
		});

		// Tournament matches
		Schema::create('official_matches', function (Blueprint $table) {
			$table->id();
			$table->foreignId('stage_id')->constrained('official_stages')->cascadeOnDelete();
			$table->integer('round')->nullable();
			$table->string('bracket', 10)->default('W'); // W: Winner, L: Loser, C: Consolation
			$table->timestamp('scheduled_at')->nullable();
			$table->foreignId('table_id')->nullable()->constrained('official_pool_tables');
			$table->string('status', 20)->default('pending'); // pending, ongoing, finished, walkover
			$table->json('metadata')->nullable();
			$table->timestamps();
		});

		// Match sets/games
		Schema::create('official_match_sets', function (Blueprint $table) {
			$table->id();
			$table->foreignId('match_id')->constrained('official_matches')->cascadeOnDelete();
			$table->integer('set_no');
			$table->foreignId('winner_participant_id')->nullable()->constrained('official_participants');
			$table->json('score_json')->nullable();
			$table->timestamps();
		});

		// Schedule time slots
		Schema::create('official_schedule_slots', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
			$table->foreignId('table_id')->constrained('official_pool_tables');
			$table->timestamp('start_at')->nullable();
			$table->timestamp('end_at')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('official_schedule_slots');
		Schema::dropIfExists('official_match_sets');
		Schema::dropIfExists('official_matches');
		Schema::dropIfExists('official_pool_tables');
		Schema::dropIfExists('official_participants');
		Schema::dropIfExists('official_team_members');
		Schema::dropIfExists('official_teams');
		Schema::dropIfExists('official_stages');
		Schema::dropIfExists('official_tournaments');
	}
};
