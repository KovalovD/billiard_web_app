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
            $table->string('group_code');
            $table->integer('group_size')->default(4);
            $table->integer('advance_count')->default(2);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->unique(['tournament_id', 'group_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_groups');
    }
};
