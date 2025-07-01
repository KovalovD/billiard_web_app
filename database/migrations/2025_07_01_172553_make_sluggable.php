<?php

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug to leagues table
        Schema::table('leagues', static function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->index('slug');
        });

        // Add slug to multiplayer_games table
        Schema::table('multiplayer_games', static function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->index('slug');
        });

        // Add slug to tournaments table
        Schema::table('tournaments', static function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->index('slug');
        });

        // Add slug to users table (for players)
        Schema::table('users', static function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('lastname');
            $table->index('slug');
        });

        // Add slug to official_ratings table
        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->index('slug');
        });

        // Generate slugs for existing records
        $this->generateSlugsForExistingRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', static function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('multiplayer_games', static function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('tournaments', static function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * Generate slugs for existing records
     */
    private function generateSlugsForExistingRecords(): void
    {
        // Generate slugs for leagues
        League::withTrashed()->whereNull('slug')->chunkById(100, function ($leagues) {
            foreach ($leagues as $league) {
                $league->slug = $this->generateUniqueSlug($league->name, 'leagues', $league->id);
                $league->saveQuietly();
            }
        });

        // Generate slugs for multiplayer games
        MultiplayerGame::whereNull('slug')->chunkById(100, function ($games) {
            foreach ($games as $game) {
                $name = $game->name ?: "game-{$game->id}";
                $game->slug = $this->generateUniqueSlug($name, 'multiplayer_games', $game->id);
                $game->saveQuietly();
            }
        });

        // Generate slugs for tournaments
        Tournament::whereNull('slug')->chunkById(100, function ($tournaments) {
            foreach ($tournaments as $tournament) {
                $tournament->slug = $this->generateUniqueSlug($tournament->name, 'tournaments', $tournament->id);
                $tournament->saveQuietly();
            }
        });

        // Generate slugs for users
        User::whereNull('slug')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $name = $user->firstname.' '.$user->lastname;
                $user->slug = $this->generateUniqueSlug($name, 'users', $user->id);
                $user->saveQuietly();
            }
        });

        // Generate slugs for official ratings
        OfficialRating::whereNull('slug')->chunkById(100, function ($ratings) {
            foreach ($ratings as $rating) {
                $rating->slug = $this->generateUniqueSlug($rating->name, 'official_ratings', $rating->id);
                $rating->saveQuietly();
            }
        });
    }

    /**
     * Generate unique slug for a given string
     */
    private function generateUniqueSlug(string $title, string $table, int $currentId): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (DB::table($table)
            ->where('slug', $slug)
            ->where('id', '!=', $currentId)
            ->exists()
        ) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
};
