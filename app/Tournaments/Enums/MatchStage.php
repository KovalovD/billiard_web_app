<?php

namespace App\Tournaments\Enums;

enum MatchStage: string
{
    case BRACKET = 'bracket';
    case GROUP = 'group';
    case THIRD_PLACE = 'third_place';
}
