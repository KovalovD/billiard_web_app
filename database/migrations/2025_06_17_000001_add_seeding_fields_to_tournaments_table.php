// database/migrations/2024_01_18_000001_add_seeding_fields_to_tournaments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('seeding_completed')->default(false)->after('seeding_completed_at');
            $table->boolean('brackets_generated')->default(false)->after('seeding_completed');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['seeding_completed', 'brackets_generated']);
        });
    }
};
