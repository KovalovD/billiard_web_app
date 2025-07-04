<?php

namespace App\Tournaments\Enums;

enum TournamentStage: string
{
    case REGISTRATION = 'registration';
    case SEEDING = 'seeding';
    case GROUP = 'group';
    case BRACKET = 'bracket';
    case COMPLETED = 'completed';

    public function displayValue(): string
    {
        return match ($this) {
            self::REGISTRATION => 'Registration',
            self::SEEDING => 'Seeding',
            self::GROUP => 'Group',
            self::BRACKET => 'Bracket',
            self::COMPLETED => 'Completed',
        };
    }
}
