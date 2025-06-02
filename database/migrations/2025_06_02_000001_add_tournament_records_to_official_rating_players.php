<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('official_rating_players', static function (Blueprint $table) {
            $table->json('tournament_records')->nullable()->after('last_tournament_at');
        });
    }

    public function down(): void
    {
        Schema::table('official_rating_players', static function (Blueprint $table) {
            $table->dropColumn('tournament_records');
        });
    }
};
