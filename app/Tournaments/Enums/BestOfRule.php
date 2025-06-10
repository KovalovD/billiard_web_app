<?php

namespace App\Tournaments\Enums;

enum BestOfRule: string
{
    case BestOf1 = 'best_of_1';
    case BestOf3 = 'best_of_3';
    case BestOf5 = 'best_of_5';
    case BestOf7 = 'best_of_7';

    public function getLabel(): string
    {
        return match ($this) {
            self::BestOf1 => 'Best of 1',
            self::BestOf3 => 'Best of 3',
            self::BestOf5 => 'Best of 5',
            self::BestOf7 => 'Best of 7',
        };
    }

    public function getGamesToWin(): int
    {
        return match ($this) {
            self::BestOf1 => 1,
            self::BestOf3 => 2,
            self::BestOf5 => 3,
            self::BestOf7 => 4,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->map(function ($rule) {
            return [
                'value'        => $rule->value,
                'label'        => $rule->getLabel(),
                'games_to_win' => $rule->getGamesToWin(),
            ];
        })->toArray();
    }
}
