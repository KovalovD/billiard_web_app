<?php

namespace App\Tournaments\Enums;

enum TournamentStatus: string
{
    case UPCOMING = 'upcoming';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
