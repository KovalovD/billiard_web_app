<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            // Update status enum to include application statuses
            $table->string('status')->default('applied')->change(); // applied, confirmed, rejected, eliminated, dnf

            // Add application and confirmation timestamps
            $table->timestamp('applied_at')->nullable()->after('registered_at');
            $table->timestamp('confirmed_at')->nullable()->after('applied_at');
            $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_players', static function (Blueprint $table) {
            $table->string('status')->default('registered')->change();
            $table->dropColumn(['applied_at', 'confirmed_at', 'rejected_at']);
        });
    }
};
