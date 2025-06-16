<?php

use App\Core\Models\Club;
use App\Core\Models\ClubTable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $b2bClub = Club::where('name', 'B2B')->first();

        if ($b2bClub) {
            $tables = [
                ['name' => 'Table 1', 'sort_order' => 1],
                ['name' => 'Table 2', 'sort_order' => 2],
                ['name' => 'Table 3', 'sort_order' => 3],
                ['name' => 'Table 4', 'sort_order' => 4],
            ];

            foreach ($tables as $table) {
                ClubTable::create([
                    'club_id'    => $b2bClub->id,
                    'name'       => $table['name'],
                    'sort_order' => $table['sort_order'],
                    'is_active'  => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        $b2bClub = Club::where('name', 'B2B')->first();

        if ($b2bClub) {
            ClubTable::where('club_id', $b2bClub->id)->delete();
        }
    }
};
