<?php

use App\Core\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clubs', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(City::class)->constrained();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
