<?php

use App\Core\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'current_player_id')->nullable()->after('moderator_user_id');
            $table->integer('next_turn_order')->nullable()->after('current_player_id');
        });
    }

    public function down(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->dropForeign(['current_player_id']);
            $table->dropColumn(['current_player_id', 'next_turn_order']);
        });
    }
};
