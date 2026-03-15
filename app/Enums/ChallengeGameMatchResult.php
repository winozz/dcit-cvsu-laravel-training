<?php

namespace App\Enums;

enum ChallengeGameMatchResult: string
{
    case Won = 'won';
    case Lost = 'lost';
    case Forfeit = 'forfeit';

    public function label(): string
    {
        return match ($this) {
            self::Won => 'Won',
            self::Lost => 'Lost',
            self::Forfeit => 'Forfeit',
        };
    }
}
