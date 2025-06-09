<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('user_id')->nullable()
                ->constrained()->cascadeOnDelete()
            ;

            $table->string('order_id')->unique();
            $table->decimal('amount', 11, 2);
            $table->string('currency', 3)->default('UAH');

            $table->string('status', 20)->default('success');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
