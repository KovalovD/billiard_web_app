<?php

namespace database\seeders;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Country;
use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Matches\Enums\GameType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'email'             => 'kovalov@b2bleague.com',
            'firstname'         => 'Dmytro',
            'lastname'          => 'Kovalov',
            'email_verified_at' => now(),
            'phone'             => '+380984438205',
            'password'          => bcrypt('nudik_number_one'),
            'is_admin'          => true,
        ]);

        User::create([
            'email'    => 'nudyk@b2bleague.com',
            'firstname'         => 'Volodymyr',
            'lastname' => 'Nudyk',
            'email_verified_at' => now(),
            'phone'             => '+380984438204',
            'password'          => bcrypt('koval_number_one'),
            'is_admin'          => true,
        ]);

        Game::insert([
            [
                'name'           => 'Пул 10',
                'type'           => GameType::Pool,
                'is_multiplayer' => 0,
            ],
            [
                'name'           => 'Пул 9',
                'type'           => GameType::Pool,
                'is_multiplayer' => 0,
            ],
            [
                'name'           => 'Пул 8',
                'type'           => GameType::Pool,
                'is_multiplayer' => 0,
            ],
            [
                'name'           => 'Пул 14+1',
                'type'           => GameType::Pool,
                'is_multiplayer' => 0,
            ],
            [
                'name'           => 'Killer pool',
                'type'           => GameType::Pool,
                'is_multiplayer' => 1,
            ],
        ]);

        Country::create([
            'name' => 'Україна',
        ]);

        City::insert([
            ['name' => 'Київ', 'country_id' => 1],
            ['name' => 'Харків', 'country_id' => 1],
            ['name' => 'Одеса', 'country_id' => 1],
            ['name' => 'Дніпро', 'country_id' => 1],
            ['name' => 'Донецьк', 'country_id' => 1],
            ['name' => 'Запоріжжя', 'country_id' => 1],
            ['name' => 'Львів', 'country_id' => 1],
            ['name' => 'Кривий Ріг', 'country_id' => 1],
            ['name' => 'Миколаїв', 'country_id' => 1],
            ['name' => 'Маріуполь', 'country_id' => 1],
            ['name' => 'Луганськ', 'country_id' => 1],
            ['name' => 'Вінниця', 'country_id' => 1],
            ['name' => 'Херсон', 'country_id' => 1],
            ['name' => 'Полтава', 'country_id' => 1],
            ['name' => 'Чернігів', 'country_id' => 1],
            ['name' => 'Черкаси', 'country_id' => 1],
            ['name' => 'Хмельницький', 'country_id' => 1],
            ['name' => 'Житомир', 'country_id' => 1],
            ['name' => 'Суми', 'country_id' => 1],
            ['name' => 'Рівне', 'country_id' => 1],
            ['name' => 'Івано-Франківськ', 'country_id' => 1],
            ['name' => 'Тернопіль', 'country_id' => 1],
            ['name' => 'Луцьк', 'country_id' => 1],
            ['name' => 'Ужгород', 'country_id' => 1],
            ['name' => 'Чернівці', 'country_id' => 1],
        ]);

        League::insert([
            [
                'name'        => 'B2B Pool League',
                'has_rating'  => true,
                'game_id'     => 3,
                'rating_type' => RatingType::Elo,
            ],
            [
                'name'        => 'B2B Killer Pool League',
                'has_rating'  => true,
                'game_id'     => 5,
                'rating_type' => RatingType::Elo,
            ],
        ]);

        League::query()->update([
            'rating_change_for_winners_rule' => [
                ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
                ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
                ['range' => [101, 200], 'strong' => 15, 'weak' => 35],
                ['range' => [201, 1000000], 'strong' => 10, 'weak' => 40],
            ],
            'rating_change_for_losers_rule'  => [
                ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
                ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
                ['range' => [101, 200], 'strong' => -15, 'weak' => -35],
                ['range' => [201, 1000000], 'strong' => -10, 'weak' => -40],
            ],
        ]);

        Club::create([
            'name'    => 'B2B',
            'city_id' => City::firstWhere('name', 'Львів')->id,
        ]);
    }
}
