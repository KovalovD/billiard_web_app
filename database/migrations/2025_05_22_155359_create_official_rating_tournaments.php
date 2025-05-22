<?php

use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Models\Tournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_rating_tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(OfficialRating::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Tournament::class)->constrained()->cascadeOnDelete();
            $table->decimal('rating_coefficient', 3, 2)->default(1.0); // Коэффициент турнира
            $table->boolean('is_counting')->default(true); // Засчитывается ли в рейтинг
            $table->timestamps();

            $table->unique('official_rating_id', 'official_rating_id_uniq');
            $table->unique('tournament_id', 'tournament_id_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_rating_tournaments');
    }
};
