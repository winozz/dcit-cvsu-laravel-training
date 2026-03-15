<?php

namespace App\Enums;

enum ChallengeGameMatchStatus: string
{
    case Waiting = 'waiting';
    case Active = 'active';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Active => 'Active',
            self::Finished => 'Finished',
        };
    }
}
