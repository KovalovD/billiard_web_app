<?php

namespace App\Tournaments\Enums;

enum TournamentType: string
{
    case SINGLE_ELIMINATION = 'single_elimination';
    case DOUBLE_ELIMINATION = 'double_elimination';
    case DOUBLE_ELIMINATION_FULL = 'double_elimination_full';
    case ROUND_ROBIN = 'round_robin';
    case GROUPS = 'groups';
    case GROUPS_PLAYOFF = 'groups_playoff';
    case TEAM_GROUPS_PLAYOFF = 'team_groups_playoff';

    public static function eliminationTypes(): array
    {
        return [self::SINGLE_ELIMINATION->value, self::DOUBLE_ELIMINATION->value, self::DOUBLE_ELIMINATION_FULL->value];
    }
}
