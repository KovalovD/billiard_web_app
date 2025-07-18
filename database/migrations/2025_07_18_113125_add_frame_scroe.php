<?php
// database/migrations/2024_01_01_000000_add_frame_scores_to_tournament_matches.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->json('frame_scores')->nullable()->after('player2_score');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropColumn('frame_scores');
        });
    }
};
