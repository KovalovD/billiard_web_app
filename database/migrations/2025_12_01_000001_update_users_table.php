<?php

use App\Core\Models\City;
use App\Core\Models\Club;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->foreignIdFor(City::class, 'home_city_id')->nullable()->constrained();
            $table->foreignIdFor(Club::class, 'home_club_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropForeign(['home_city_id']);
            $table->dropForeign(['home_club_id']);
            $table->dropColumn(['home_city_id', 'home_club_id']);
        });
    }
};
