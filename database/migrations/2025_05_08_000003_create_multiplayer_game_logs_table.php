<?php

use App\Core\Models\User;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('multiplayer_game_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MultiplayerGame::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('action_type');
            $table->json('action_data')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multiplayer_game_logs');
    }
};
