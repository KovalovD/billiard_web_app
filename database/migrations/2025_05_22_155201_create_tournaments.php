<?php

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('regulation')->nullable(); // Регламент
            $table->text('details')->nullable(); // Детали
            $table->string('status')->default('upcoming'); // upcoming, active, completed, cancelled
            $table->foreignIdFor(Game::class)->constrained(); // Дисциплина
            $table->foreignIdFor(City::class)->nullable()->constrained(); // Город проведения
            $table->foreignIdFor(Club::class)->nullable()->constrained(); // Клуб проведения
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_participants')->nullable(); // Максимум участников
            $table->decimal('entry_fee', 8, 2)->default(0); // Взнос
            $table->decimal('prize_pool', 10, 2)->default(0); // Призовой фонд
            $table->json('prize_distribution')->nullable(); // Распределение призов
            $table->string('organizer')->nullable(); // Организатор
            $table->string('format')->nullable(); // Формат турнира
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
