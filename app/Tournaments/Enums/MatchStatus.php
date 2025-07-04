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

    public function displayValue(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::READY => 'Ready',
            self::IN_PROGRESS => 'In Progress',
            self::VERIFICATION => 'Verification',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
