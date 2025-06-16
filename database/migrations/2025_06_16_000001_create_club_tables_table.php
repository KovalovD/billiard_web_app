<?php

use App\Core\Models\Club;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('club_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Club::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('stream_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_tables');
    }
};
