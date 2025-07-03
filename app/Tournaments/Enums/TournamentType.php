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
    case KILLER_POOL = 'killer_pool';

    public static function eliminationTypes(): array
    {
        return [self::SINGLE_ELIMINATION->value, self::DOUBLE_ELIMINATION->value, self::DOUBLE_ELIMINATION_FULL->value];
    }

    public function displayName(): string
    {
        return match ($this) {
            self::SINGLE_ELIMINATION => 'Single Elimination',
            self::DOUBLE_ELIMINATION => 'Double Elimination',
            self::DOUBLE_ELIMINATION_FULL => 'Double Elimination All Places',
            self::ROUND_ROBIN => 'Round Robin',
            self::GROUPS => 'Groups',
            self::GROUPS_PLAYOFF => 'Groups Playoff',
            self::TEAM_GROUPS_PLAYOFF => 'Team Groups Playoff',
            self::KILLER_POOL => 'Killer Pool',
        };
    }
}
