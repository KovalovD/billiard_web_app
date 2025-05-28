<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Artisan::call('db:seed');
        Artisan::call('import:tournaments public/import.xlsx');
    }
};
