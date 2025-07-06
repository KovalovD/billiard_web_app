<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('sex');
            $table->string('tournament_picture')->nullable()->after('picture');
            $table->text('description')->nullable()->after('tournament_picture');
            $table->json('equipment')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['birthdate', 'tournament_picture', 'description', 'equipment']);
        });
    }
};
