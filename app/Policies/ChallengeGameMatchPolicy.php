<?php

namespace App\Policies;

use App\Models\ChallengeGameMatch;
use App\Models\Player;

class ChallengeGameMatchPolicy
{
    /**
     * Allow access to a match when the player is either host or guest.
     */
    public function access(Player $player, ChallengeGameMatch $match): bool
    {
        return $match->host_player_id === $player->id || $match->guest_player_id === $player->id;
    }
}
