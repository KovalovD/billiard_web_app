<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', static function (Blueprint $table) {
            // Change date fields to datetime
            $table->datetime('start_date')->change();
            $table->datetime('end_date')->change();

            // Add application deadline
            $table->datetime('application_deadline')->nullable()->after('end_date');

            // Add flags for application management
            $table->boolean('requires_application')->default(true)->after('application_deadline');
            $table->boolean('auto_approve_applications')->default(false)->after('requires_application');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', static function (Blueprint $table) {
            // Revert datetime back to date
            $table->date('start_date')->change();
            $table->date('end_date')->change();

            // Remove added columns
            $table->dropColumn(['application_deadline', 'requires_application', 'auto_approve_applications']);
        });
    }
};
