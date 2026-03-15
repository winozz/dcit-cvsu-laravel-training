<?php

namespace App\Services;

use App\DTO\MatchProgressData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class MatchProgressService
{
    public function key(string $matchCode, int $playerId): string
    {
        return "matches.$matchCode.players.$playerId.progress";
    }

    public function store(string $matchCode, int $playerId, MatchProgressData $payload, int $minutes = 60): void
    {
        Cache::put($this->key($matchCode, $playerId), $payload->toArray(), Carbon::now()->addMinutes($minutes));
    }

    public function get(string $matchCode, int $playerId): ?MatchProgressData
    {
        $payload = Cache::get($this->key($matchCode, $playerId));
        if (!is_array($payload)) {
            return null;
        }

        return MatchProgressData::fromArray($payload);
    }

    public function forget(string $matchCode, ?int $playerId = null): void
    {
        if ($playerId) {
            Cache::forget($this->key($matchCode, $playerId));
            return;
        }
        // If player id not given, clear for both sides is handled by caller.
    }
}
