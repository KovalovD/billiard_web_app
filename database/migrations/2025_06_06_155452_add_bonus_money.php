<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            $table->decimal('bonus_amount', 8, 2)->default(0)->after('prize_amount');
            $table->decimal('achievement_amount', 8, 2)->default(0)->after('bonus_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            $table->dropColumn(['bonus_amount', 'achievement_amount']);
        });
    }
};
