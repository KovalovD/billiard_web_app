<?php

namespace App\Tournaments\Enums;

enum BracketType: string
{
    case Main = 'main';
    case Upper = 'upper';
    case Lower = 'lower';
    case Final = 'final';
    case Consolation = 'consolation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Main => 'Main Bracket',
            self::Upper => 'Upper Bracket',
            self::Lower => 'Lower Bracket',
            self::Final => 'Final',
            self::Consolation => 'Consolation',
        };
    }
}
