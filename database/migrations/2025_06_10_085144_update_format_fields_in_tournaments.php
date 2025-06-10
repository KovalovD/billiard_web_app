<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Tournament format and structure
            $table
                ->string('tournament_format')->default('single_elimination')
                ->after('format')
            ; // single_elimination, double_elimination, group_stage, group_playoff, round_robin

            $table
                ->string('seeding_method')->default('manual')
                ->after('tournament_format')
            ; // manual, random, rating_based

            $table
                ->integer('number_of_groups')->nullable()
                ->after('seeding_method')
            ; // For group stage tournaments

            $table
                ->integer('players_per_group')->nullable()
                ->after('number_of_groups')
            ; // For group stage tournaments

            $table
                ->integer('advance_per_group')->nullable()
                ->after('players_per_group')
            ; // How many advance from each group

            $table
                ->string('best_of_rule')->default('best_of_1')
                ->after('advance_per_group')
            ; // best_of_1, best_of_3, best_of_5, etc.

            $table
                ->boolean('has_lower_bracket')->default(false)
                ->after('best_of_rule')
            ; // For double elimination

            $table
                ->boolean('is_team_tournament')->default(false)
                ->after('has_lower_bracket')
            ; // Individual vs team tournament

            $table
                ->integer('team_size')->nullable()
                ->after('is_team_tournament')
            ; // 2 for pairs, 3+ for teams

            $table
                ->json('bracket_structure')->nullable()
                ->after('team_size')
            ; // Store bracket configuration

            $table
                ->json('seeding_configuration')->nullable()
                ->after('bracket_structure')
            ; // Store seeding rules and data
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'tournament_format',
                'seeding_method',
                'number_of_groups',
                'players_per_group',
                'advance_per_group',
                'best_of_rule',
                'has_lower_bracket',
                'is_team_tournament',
                'team_size',
                'bracket_structure',
                'seeding_configuration',
            ]);
        });
    }
};
