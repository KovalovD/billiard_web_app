<?php

namespace App\Tournaments\Enums;

enum TournamentStatus: string
{
    case UPCOMING = 'upcoming';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function displayValue(): string
    {
        return match ($this) {
            self::UPCOMING => 'Upcoming',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
