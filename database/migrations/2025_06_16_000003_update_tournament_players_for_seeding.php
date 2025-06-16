<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_players', function (Blueprint $table) {
            $table->integer('seed_number')->nullable()->after('position');
            $table->string('group_code')->nullable()->after('seed_number');
            $table->integer('group_position')->nullable()->after('group_code');
            $table->integer('group_wins')->default(0)->after('group_position');
            $table->integer('group_losses')->default(0)->after('group_wins');
            $table->integer('group_games_diff')->default(0)->after('group_losses');
            $table->string('elimination_round')->nullable()->after('status');

            $table->index(['tournament_id', 'seed_number']);
            $table->index(['tournament_id', 'group_code']);
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', function (Blueprint $table) {
            $table->dropColumn([
                'seed_number',
                'group_code',
                'group_position',
                'group_wins',
                'group_losses',
                'group_games_diff',
                'elimination_round',
            ]);
        });
    }
};
