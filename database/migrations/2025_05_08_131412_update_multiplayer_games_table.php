<?php

use App\Core\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'moderator_user_id')->nullable()->after('completed_at');
            $table->boolean('allow_player_targeting')->default(false)->after('moderator_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->dropForeign(['moderator_user_id']);
            $table->dropColumn(['moderator_user_id', 'allow_player_targeting']);
        });
    }
};
