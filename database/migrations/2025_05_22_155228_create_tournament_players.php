<?php

use App\Core\Models\User;
use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained();
            $table->integer('position')->nullable(); // Занятое место
            $table->integer('rating_points')->default(0); // Заработанные рейтинговые очки
            $table->decimal('prize_amount', 8, 2)->default(0); // Полученный приз
            $table->string('status')->default('registered'); // registered, confirmed, eliminated, dnf
            $table->timestamp('registered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_players');
    }
};
