<?php

namespace App\Tournaments\Enums;

enum TournamentPlayerStatus: string
{
    case APPLIED = 'applied';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case ELIMINATED = 'eliminated';
    case DNF = 'dnf';
}
