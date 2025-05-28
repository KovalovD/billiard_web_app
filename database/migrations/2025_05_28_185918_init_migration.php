<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (app()->environment('production')) {
            Artisan::call('db:seed');
            Artisan::call('import:tournaments public/import.xlsx');
        }
    }
};
