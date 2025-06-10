<?php

use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->string('name'); // Group A, Group B, etc.
            $table->string('display_name')->nullable(); // Custom group name
            $table->integer('group_number'); // 1, 2, 3, etc.
            $table->integer('max_participants');
            $table->integer('advance_count')->default(2); // How many advance from this group
            $table->boolean('is_completed')->default(false);
            $table->json('standings_cache')->nullable(); // Cache calculated standings
            $table->timestamps();

            $table->unique(['tournament_id', 'group_number']);
            $table->index(['tournament_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_groups');
    }
};
