<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('official_rating_players', function (Blueprint $table) {
            $table->decimal('total_prize_amount', 10, 2)->default(0)->after('total_achievement_amount');
        });
    }

    public function down(): void
    {
        Schema::table('official_rating_players', function (Blueprint $table) {
            $table->dropColumn('total_prize_amount');
        });
    }
};
