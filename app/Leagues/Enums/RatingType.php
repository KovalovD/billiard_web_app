<?php

namespace App\Leagues\Enums;

enum RatingType: string
{
    case Elo = 'elo';
    case KillerPool = 'killer_pool';
}
