<?php

namespace App\Tournaments\Enums;

enum TeamRole: string
{
    case Captain = 'captain';
    case Player = 'player';
    case Substitute = 'substitute';

    public function getLabel(): string
    {
        return match ($this) {
            self::Captain => 'Captain',
            self::Player => 'Player',
            self::Substitute => 'Substitute',
        };
    }

    public function isActiveRole(): bool
    {
        return in_array($this, [self::Captain, self::Player]);
    }

    public static function activeRoles(): array
    {
        return [self::Captain, self::Player];
    }

    public static function options(): array
    {
        return collect(self::cases())->map(function ($role) {
            return [
                'value'     => $role->value,
                'label'     => $role->getLabel(),
                'is_active' => $role->isActiveRole(),
            ];
        })->toArray();
    }
}
