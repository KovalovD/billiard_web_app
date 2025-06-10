<?php

namespace App\Tournaments\Enums;

enum TournamentFormat: string
{
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case GroupStage = 'group_stage';
    case GroupPlayoff = 'group_playoff';
    case RoundRobin = 'round_robin';

    public function getLabel(): string
    {
        return match ($this) {
            self::SingleElimination => 'Single Elimination',
            self::DoubleElimination => 'Double Elimination',
            self::GroupStage => 'Group Stage (Round Robin)',
            self::GroupPlayoff => 'Group Stage + Playoffs',
            self::RoundRobin => 'Round Robin',
        };
    }

    public function hasGroups(): bool
    {
        return in_array($this, [self::GroupStage, self::GroupPlayoff]);
    }

    public function hasBrackets(): bool
    {
        return in_array($this, [self::SingleElimination, self::DoubleElimination, self::GroupPlayoff]);
    }

    public static function options(): array
    {
        return collect(self::cases())->map(function ($format) {
            return [
                'value'        => $format->value,
                'label'        => $format->getLabel(),
                'has_groups'   => $format->hasGroups(),
                'has_brackets' => $format->hasBrackets(),
            ];
        })->toArray();
    }
}
