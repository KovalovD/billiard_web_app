<?php

use App\Core\Models\User;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('multiplayer_game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MultiplayerGame::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained();
            $table->integer('lives')->default(5);
            $table->integer('turn_order')->nullable();
            $table->integer('finish_position')->nullable();
            $table->json('cards')->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamps();

            $table->unique(['multiplayer_game_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multiplayer_game_players');
    }
};
