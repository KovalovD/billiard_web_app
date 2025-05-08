<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->integer('entrance_fee')->default(300)->after('allow_player_targeting'); // Entrance fee in UAH
            $table->integer('first_place_percent')->default(60)->after('entrance_fee'); // Winner percentage
            $table->integer('second_place_percent')->default(20)->after('first_place_percent'); // Second place percentage
            $table->integer('grand_final_percent')->default(20)->after('second_place_percent'); // Grand final fund percentage
            $table->integer('penalty_fee')->default(50)->after('grand_final_percent'); // Penalty fee for first eliminated players
            $table->json('prize_pool')->nullable()->after('penalty_fee'); // Total prize pool info
        });

        Schema::table('multiplayer_game_players', function (Blueprint $table) {
            $table->integer('rating_points')->default(0)->after('finish_position'); // Rating points earned
            $table->integer('prize_amount')->default(0)->after('rating_points'); // Prize amount in UAH
            $table->boolean('penalty_paid')->default(false)->after('prize_amount'); // Whether the player paid the penalty
        });
    }

    public function down(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->dropColumn([
                'entrance_fee',
                'first_place_percent',
                'second_place_percent',
                'grand_final_percent',
                'penalty_fee',
                'prize_pool',
            ]);
        });

        Schema::table('multiplayer_game_players', function (Blueprint $table) {
            $table->dropColumn([
                'rating_points',
                'prize_amount',
                'penalty_paid',
            ]);
        });
    }
};
