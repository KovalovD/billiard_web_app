<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('multiplayer_games', static function (Blueprint $table) {
            $table->boolean('allow_rebuy')->default(false)->after('penalty_fee');
            $table->integer('rebuy_rounds')->nullable()->after('allow_rebuy');
            $table->integer('lives_per_new_player')->default(0)->after('rebuy_rounds');
            $table->boolean('enable_penalties')->default(false)->after('lives_per_new_player');
            $table->integer('penalty_rounds_threshold')->nullable()->after('enable_penalties');
            $table->json('rebuy_history')->nullable()->after('prize_pool');
            $table->decimal('current_prize_pool', 10, 2)->default(0)->after('rebuy_history');
        });

        Schema::table('multiplayer_game_players', static function (Blueprint $table) {
            $table->integer('rebuy_count')->default(0)->after('penalty_paid');
            $table->integer('rounds_played')->default(1)->after('rebuy_count');
            $table->decimal('total_paid', 10, 2)->default(0)->after('rounds_played');
            $table->json('game_stats')->nullable()->after('total_paid');
            $table->boolean('is_rebuy')->default(false)->after('game_stats');
            $table->timestamp('last_rebuy_at')->nullable()->after('is_rebuy');
        });
    }

    public function down(): void
    {
        Schema::table('multiplayer_games', static function (Blueprint $table) {
            $table->dropColumn([
                'allow_rebuy',
                'rebuy_rounds',
                'lives_per_new_player',
                'enable_penalties',
                'penalty_rounds_threshold',
                'rebuy_history',
                'current_prize_pool',
            ]);
        });

        Schema::table('multiplayer_game_players', static function (Blueprint $table) {
            $table->dropColumn([
                'rebuy_count',
                'rounds_played',
                'total_paid',
                'game_stats',
                'is_rebuy',
                'last_rebuy_at',
            ]);
        });
    }
};
