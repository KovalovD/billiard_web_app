<?php

use App\Core\Models\Game;
use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->string('game_type')->after('game_id')->nullable();
        });

        // Update existing ratings to set game_type based on their game_id
        $rating = OfficialRating::first();
        $rating->update(['game_type' => Game::find($rating->game_id)->type]);

        // Make game_type required and remove game_id
        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->string('game_type')->nullable(false)->change();
            $table->dropForeign(['game_id']);
            $table->dropColumn('game_id');
        });
    }

    public function down(): void
    {
        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->foreignId('game_id')->nullable()->constrained('games');
        });

        // Restore game_id based on game_type (approximate restoration)
        $ratings = OfficialRating::all();
        foreach ($ratings as $rating) {
            $game = Game::where('type', $rating->game_type)->first();
            if ($game) {
                $rating->update(['game_id' => $game->id]);
            }
        }

        Schema::table('official_ratings', static function (Blueprint $table) {
            $table->dropColumn('game_type');
        });
    }
};
