<?php

use App\Core\Models\Game;
use App\Leagues\Models\League;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('multiplayer_games', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(League::class)->constrained();
            $table->foreignIdFor(Game::class)->constrained();
            $table->string('name')->nullable();
            $table->string('status')->default('registration');  // registration, in_progress, completed
            $table->integer('initial_lives')->default(5);
            $table->integer('max_players')->nullable();
            $table->timestamp('registration_ends_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multiplayer_games');
    }
};
