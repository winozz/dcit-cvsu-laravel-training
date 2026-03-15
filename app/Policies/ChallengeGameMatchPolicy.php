<?php

namespace App\Policies;

use App\Enums\ChallengeGameMatchStatus;
use App\Models\ChallengeGameMatch;
use App\Models\Player;

class ChallengeGameMatchPolicy
{
    /**
     * Allow match actions when the player is either host or guest.
     */
    public function view(Player $player, ChallengeGameMatch $match): bool
    {
        return $match->host_player_id === $player->id || $match->guest_player_id === $player->id;
    }

    public function join(Player $player, ChallengeGameMatch $match): bool
    {
        return $match->status === ChallengeGameMatchStatus::Waiting
            && $match->guest_player_id === null
            && $match->host_player_id !== $player->id;
    }
}
