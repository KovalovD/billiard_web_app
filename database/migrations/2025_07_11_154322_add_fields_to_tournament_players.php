<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            $table->integer('tiebreaker_wins')->default(0);
            $table->integer('tiebreaker_games_diff')->default(0);
            $table->integer('temp_wins')->default(0);
            $table->integer('temp_games_diff')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            $table->dropColumn(['tiebreaker_wins', 'tiebreaker_games_diff', 'temp_wins', 'temp_games_diff']);
        });
    }
};
