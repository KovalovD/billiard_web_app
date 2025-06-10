<?php

namespace App\Tournaments\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->map(function ($status) {
            return [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'color' => $status->getColor(),
            ];
        })->toArray();
    }
}
