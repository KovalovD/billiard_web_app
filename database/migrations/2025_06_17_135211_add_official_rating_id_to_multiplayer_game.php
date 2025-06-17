<?php

use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::table('multiplayer_games', static function (Blueprint $table) {
			$table->foreignIdFor(OfficialRating::class)->nullable()->after('id')->constrained();
		});
	}

	public function down(): void
	{
		Schema::table('multiplayer_games', function (Blueprint $table) {
			$table->dropForeign(['official_rating_id']);
			$table->dropColumn(['official_rating_id']);
		});
	}
};
