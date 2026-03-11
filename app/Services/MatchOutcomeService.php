<?php

namespace App\Services;

use App\Models\ChallengeGameMatch;
use App\Models\Player;
use Illuminate\Support\Carbon;
use App\Services\MatchProgressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchOutcomeService
{
    public function __construct(private readonly MatchProgressService $progress)
    {
    }

    public function markProgress(?string $matchCode, int $playerId, bool $won, bool $lost, bool $timedOut = false): void
    {
        if (!$matchCode) return;
        $match = ChallengeGameMatch::where('code', $matchCode)->first();
        if (!$match || $match->status === 'finished') return;

        $this->applyExpiry($match);

        if ($match->status === 'finished' || (!$won && !$lost)) return;

        $result = $won ? 'won' : 'lost';
        $fields = [];
        if ($match->host_player_id === $playerId) {
            $fields['host_result'] = $result;
            // Mark done on win or timeout-triggered loss
            if (($won || $timedOut) && !$match->host_done) {
                $fields['host_done'] = true;
            }
        }
        if ($match->guest_player_id === $playerId) {
            $fields['guest_result'] = $result;
            if (($won || $timedOut) && !$match->guest_done) {
                $fields['guest_done'] = true;
            }
        }
        if ($fields) {
            $match->fill($fields);
            $match->save();
        }

        $this->finalizeIfReady($match);
    }

    public function forfeit(?string $matchCode, int $playerId): void
    {
        if (!$matchCode) return;
        $match = ChallengeGameMatch::where('code', $matchCode)->first();
        if (!$match || $match->status === 'finished') return;

        if ($match->host_player_id === $playerId) {
            $match->host_forfeit = true;
            $match->host_done = true;
            $match->host_result = 'forfeit';
        }
        if ($match->guest_player_id === $playerId) {
            $match->guest_forfeit = true;
            $match->guest_done = true;
            $match->guest_result = 'forfeit';
        }
        $match->save();
        $this->finalizeIfReady($match, true);
    }

    private function applyExpiry(ChallengeGameMatch $match): void
    {
        if ($match->status === 'finished') return;

        // Start the 2-minute timer the first time both seats are filled and timer is empty.
        if (!$match->expires_at && $match->host_player_id && $match->guest_player_id && $match->status === 'active') {
            $match->expires_at = Carbon::now()->addMinutes(2);
            $match->save();
            Log::info('Match timer started', [
                'match' => $match->code,
                'expires_at' => $match->expires_at,
            ]);
        }

        if (!$match->expires_at) return;
        if (Carbon::now()->lte(Carbon::parse($match->expires_at))) return;

        // Expired: unfinished players forfeit.
        if (!$match->host_done) {
            $match->host_forfeit = true;
            $match->host_done = true;
            $match->host_result = 'forfeit';
        }
        if (!$match->guest_done) {
            $match->guest_forfeit = true;
            $match->guest_done = true;
            $match->guest_result = 'forfeit';
        }
        $match->save();
        Log::warning('Match expired after timer', [
            'match' => $match->code,
            'expires_at' => $match->expires_at,
            'host_done' => $match->host_done,
            'guest_done' => $match->guest_done,
        ]);

        // Finalize immediately after expiry to avoid repeated expiry logs/polls.
        $this->finalizeIfReady($match, true);
    }

    private function finalizeIfReady(ChallengeGameMatch $match, bool $force = false): void
    {
        if ($match->status === 'finished') return;

        $ready = $force
            || $match->host_forfeit || $match->guest_forfeit
            || ($match->host_done && $match->guest_done);

        if (!$ready) return;

        $winner = $this->determineWinner($match);
        $match->status = 'finished';
        $match->ended_at = Carbon::now();
        $match->save();
        Log::info('Match finalized', [
            'match' => $match->code,
            'winner' => $winner,
            'host_result' => $match->host_result,
            'guest_result' => $match->guest_result,
            'host_forfeit' => $match->host_forfeit,
            'guest_forfeit' => $match->guest_forfeit,
            'force' => $force,
        ]);

        $this->applyStats($match, $winner);
        $this->clearProgressCaches($match);
    }

    private function determineWinner(ChallengeGameMatch $match): ?string
    {
        if ($match->host_forfeit && !$match->guest_forfeit) return 'guest';
        if ($match->guest_forfeit && !$match->host_forfeit) return 'host';

        $hostWon = $match->host_result === 'won';
        $guestWon = $match->guest_result === 'won';

        if ($hostWon && !$guestWon) return 'host';
        if ($guestWon && !$hostWon) return 'guest';

        // Both won or both lost/unknown -> draw (no winner)
        return null;
    }

    private function applyStats(ChallengeGameMatch $match, ?string $winner): void
    {
        $host = Player::find($match->host_player_id);
        $guest = Player::find($match->guest_player_id);

        if ($host) $host->increment('games_played');
        if ($guest) $guest->increment('games_played');

        if ($winner === 'host') {
            if ($host) $host->increment('wins');
            if ($guest) $guest->increment('losses');
        } elseif ($winner === 'guest') {
            if ($guest) $guest->increment('wins');
            if ($host) $host->increment('losses');
        }
    }

    private function clearProgressCaches(ChallengeGameMatch $match): void
    {
        if ($match->host_player_id) {
            Cache::forget($this->progress->key($match->code, $match->host_player_id));
        }
        if ($match->guest_player_id) {
            Cache::forget($this->progress->key($match->code, $match->guest_player_id));
        }
    }
}
