<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('tournament_type')->default('single_elimination')->after('format');
            $table->integer('group_size_min')->nullable()->after('tournament_type');
            $table->integer('group_size_max')->nullable()->after('group_size_min');
            $table->integer('playoff_players_per_group')->nullable()->after('group_size_max');
            $table->integer('races_to')->default(7)->after('playoff_players_per_group');
            $table->boolean('has_third_place_match')->default(false)->after('races_to');
            $table->string('seeding_method')->default('random')->after('has_third_place_match');
            $table->json('place_prizes')->nullable()->after('prize_distribution');
            $table->json('place_bonuses')->nullable()->after('place_prizes');
            $table->json('place_rating_points')->nullable()->after('place_bonuses');
            $table->string('stage')->default('registration')->after('status');
            $table->timestamp('seeding_completed_at')->nullable()->after('completed_at');
            $table->timestamp('groups_completed_at')->nullable()->after('seeding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'tournament_type',
                'group_size_min',
                'group_size_max',
                'playoff_players_per_group',
                'races_to',
                'has_third_place_match',
                'seeding_method',
                'place_prizes',
                'place_bonuses',
                'place_rating_points',
                'stage',
                'seeding_completed_at',
                'groups_completed_at',
            ]);
        });
    }
};
