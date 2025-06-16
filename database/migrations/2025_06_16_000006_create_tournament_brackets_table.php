<?php

use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->string('bracket_type');
            $table->integer('total_rounds');
            $table->integer('players_count');
            $table->json('bracket_structure')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'bracket_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_brackets');
    }
};
