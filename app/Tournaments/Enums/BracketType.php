<?php

namespace App\Tournaments\Enums;

enum BracketType: string
{
    case SINGLE = 'single';
    case DOUBLE_UPPER = 'double_upper';
    case DOUBLE_LOWER = 'double_lower';
}
