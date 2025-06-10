<?php

use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->string('name'); // Team name
            $table->string('short_name')->nullable(); // Abbreviated name
            $table->integer('seed')->nullable(); // Seeding position
            $table->integer('group_id')->nullable(); // Reference to tournament_groups
            $table->integer('bracket_position')->nullable(); // Position in bracket
            $table->boolean('is_active')->default(true);
            $table->json('roster_data')->nullable(); // Additional team data
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('tournament_groups')->nullOnDelete();
            $table->index(['tournament_id', 'is_active']);
            $table->index(['tournament_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
    }
};
