<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('games', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('rules')->nullable();
            $table->string('type');
            $table->boolean('is_multiplayer')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
