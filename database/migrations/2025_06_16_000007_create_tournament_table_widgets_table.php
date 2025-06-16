<?php

use App\Tournaments\Models\Tournament;
use App\Core\Models\ClubTable;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_table_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ClubTable::class)->constrained('club_tables')->cascadeOnDelete();
            $table->foreignId('current_match_id')->nullable()->constrained('tournament_matches')->nullOnDelete();
            $table->string('widget_url')->nullable();
            $table->string('player_widget_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tournament_id', 'club_table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_table_widgets');
    }
};
