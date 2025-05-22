<?php

use App\Core\Models\User;
use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_rating_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(OfficialRating::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained();
            $table->integer('rating_points')->default(1000); // Текущий рейтинг
            $table->integer('position')->default(0); // Позиция в рейтинге
            $table->integer('tournaments_played')->default(0); // Количество турниров
            $table->integer('tournaments_won')->default(0); // Выигранных турниров
            $table->timestamp('last_tournament_at')->nullable(); // Последний турнир
            $table->boolean('is_active')->default(true); // Активен ли игрок
            $table->timestamps();

            $table->index(['official_rating_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_rating_players');
    }
};
