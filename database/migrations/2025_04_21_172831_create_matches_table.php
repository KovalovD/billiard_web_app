<?php

use App\Core\Models\Club;
use App\Core\Models\Game;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_games', static function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(GameStatus::PENDING->value);
            $table->foreignIdFor(Club::class)->nullable();
            $table->foreignIdFor(Game::class)->nullable();
            $table->foreignIdFor(League::class);
            $table->foreignIdFor(Rating::class, 'first_rating_id');
            $table->foreignIdFor(Rating::class, 'second_rating_id');
            $table->smallInteger('first_user_score')->default(0);
            $table->smallInteger('second_user_score')->default(0);
            $table->foreignIdFor(Rating::class, 'winner_rating_id')->nullable();
            $table->foreignIdFor(Rating::class, 'loser_rating_id')->nullable();
            $table->integer('rating_change_for_winner')->default('+0');
            $table->integer('rating_change_for_loser')->default('-0');
            $table->string('stream_url')->nullable();
            $table->string('details')->nullable();
            $table->timestamp('invitation_sent_at');
            $table->timestamp('invitation_available_till')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
