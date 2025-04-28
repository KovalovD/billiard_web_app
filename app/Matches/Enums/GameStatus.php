<?php

namespace App\Matches\Enums;

enum GameStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public static function notAllowedToInviteStatuses(): array
    {
        return [self::PENDING, self::IN_PROGRESS];
    }
}
