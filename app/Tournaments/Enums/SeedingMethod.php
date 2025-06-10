<?php

namespace App\Tournaments\Enums;

enum SeedingMethod: string
{
    case Manual = 'manual';
    case Random = 'random';
    case RatingBased = 'rating_based';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual Seeding',
            self::Random => 'Random Shuffle',
            self::RatingBased => 'Rating-Based',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->map(function ($method) {
            return [
                'value' => $method->value,
                'label' => $method->getLabel(),
            ];
        })->toArray();
    }
}
