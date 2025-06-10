<?php

use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();

            // Bracket information
            $table->string('bracket_type'); // upper, lower, final, consolation
            $table->integer('total_rounds');
            $table->integer('total_participants');
            $table->boolean('is_active')->default(true);

            // Bracket structure data
            $table->json('bracket_structure'); // Complete bracket layout
            $table->json('participant_positions')->nullable(); // Current participant positions
            $table->json('advancement_rules')->nullable(); // Rules for advancement

            // Status tracking
            $table->integer('current_round')->default(1);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'bracket_type']);
            $table->index(['tournament_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_brackets');
    }
};
