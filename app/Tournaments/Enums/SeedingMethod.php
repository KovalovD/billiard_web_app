<?php

namespace App\Tournaments\Enums;

enum SeedingMethod: string
{
    case RANDOM = 'random';
    case RATING = 'rating';
    case MANUAL = 'manual';
}
