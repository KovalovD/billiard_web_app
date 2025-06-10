<?php

namespace App\Tournaments\Enums;

enum MatchType: string
{
    case Group = 'group';
    case Bracket = 'bracket';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Group => 'Group Stage',
            self::Bracket => 'Bracket',
            self::Final => 'Final',
        };
    }
}
