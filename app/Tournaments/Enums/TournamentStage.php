<?php

namespace App\Tournaments\Enums;

enum TournamentStage: string
{
    case REGISTRATION = 'registration';
    case SEEDING = 'seeding';
    case GROUP = 'group';
    case BRACKET = 'bracket';
    case COMPLETED = 'completed';
}
