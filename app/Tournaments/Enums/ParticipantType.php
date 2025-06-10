<?php

namespace App\Tournaments\Enums;

enum ParticipantType: string
{
    case Player = 'player';
    case Team = 'team';

    public function getLabel(): string
    {
        return match ($this) {
            self::Player => 'Player',
            self::Team => 'Team',
        };
    }
}
