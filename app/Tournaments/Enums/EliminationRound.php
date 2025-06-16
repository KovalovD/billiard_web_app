<?php

namespace App\Tournaments\Enums;

enum EliminationRound: string
{
    case GROUPS = 'groups';
    case ROUND_128 = 'round_128';
    case ROUND_64 = 'round_64';
    case ROUND_32 = 'round_32';
    case ROUND_16 = 'round_16';
    case QUARTERFINALS = 'quarterfinals';
    case SEMIFINALS = 'semifinals';
    case FINALS = 'finals';
    case THIRD_PLACE = 'third_place';
}
