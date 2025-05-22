<?php

use App\Core\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название рейтинга
            $table->text('description')->nullable(); // Описание
            $table->foreignIdFor(Game::class)->constrained(); // Вид бильярда
            $table->boolean('is_active')->default(true); // Активен ли рейтинг
            $table->integer('initial_rating')->default(1000); // Начальный рейтинг
            $table->string('calculation_method')->default('tournament_points'); // Метод расчета
            $table->json('rating_rules')->nullable(); // Правила начисления очков
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_ratings');
    }
};
