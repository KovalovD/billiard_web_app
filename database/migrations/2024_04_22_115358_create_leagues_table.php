<?php

use App\Core\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leagues', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Game::class)->nullable();
            $table->string('picture')->nullable();
            $table->text('details')->nullable();
            $table->string('rating_type')->default('elo');
            $table->boolean('has_rating')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('start_rating')->default(1000);
            $table->json('rating_change_for_winners_rule')->nullable();
            $table->json('rating_change_for_losers_rule')->nullable();
            $table->integer('max_players')->default(0);
            $table->integer('invite_days_expire')->default(3);
            $table->integer('max_score')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
