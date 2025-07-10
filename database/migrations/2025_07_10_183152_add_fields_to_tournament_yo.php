<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Olympic double elimination settings
            $table->integer('olympic_phase_size')->nullable()->after('tournament_type');
            $table->boolean('olympic_has_third_place')->default(false)->after('olympic_phase_size');

            // Race-to settings per round
            $table->json('round_races_to')->nullable()->after('races_to');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'olympic_phase_size',
                'olympic_has_third_place',
                'round_races_to',
            ]);
        });
    }
};
