<?php

namespace App\Tournaments\Enums;

enum MatchStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case IN_PROGRESS = 'in_progress';
    case VERIFICATION = 'verification';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
