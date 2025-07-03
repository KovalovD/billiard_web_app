<?php

namespace App\Tournaments\Enums;

enum SeedingMethod: string
{
    case RANDOM = 'random';
    case RATING = 'rating';
    case MANUAL = 'manual';

    public function displayName(): string
    {
        return match ($this) {
            self::RANDOM => 'Random',
            self::RATING => 'Rating',
            self::MANUAL => 'Manual',
        };
    }
}
