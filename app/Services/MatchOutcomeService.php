<?php

namespace App\Services;

use App\Enums\ChallengeGameMatchResult;
use App\Enums\ChallengeGameMatchStatus;
use App\Models\ChallengeGameMatch;
use App\Models\Player;
use App\Services\MatchProgressService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchOutcomeService
{
    private const MATCH_EXPIRY_MINUTES = 2;

    public function __construct(private readonly MatchProgressService $progress)
    {
    }

    public function markProgress(?string $matchCode, int $playerId, bool $won, bool $lost, bool $timedOut = false): void
    {
        $match = $this->activeMatch($matchCode);
        if (!$match) return;

        $this->applyExpiry($match);

        if ($match->status === ChallengeGameMatchStatus::Finished || (!$won && !$lost)) return;

        $result = $won ? ChallengeGameMatchResult::Won : ChallengeGameMatchResult::Lost;
        $this->applyPlayerResult($match, $playerId, $result, $won || $timedOut);

        $this->finalizeIfReady($match);
    }

    public function forfeit(?string $matchCode, int $playerId): void
    {
        $match = $this->activeMatch($matchCode);
        if (!$match) return;

        $this->applyPlayerResult($match, $playerId, ChallengeGameMatchResult::Forfeit, true, true);
        $this->finalizeIfReady($match, true);
    }

    private function applyExpiry(ChallengeGameMatch $match): void
    {
        if ($match->status === ChallengeGameMatchStatus::Finished) return;

        // Start the 2-minute timer the first time both seats are filled and timer is empty.
        if (!$match->expires_at
            && $match->host_player_id
            && $match->guest_player_id
            && $match->status === ChallengeGameMatchStatus::Active) {
            $match->expires_at = Carbon::now()->addMinutes(self::MATCH_EXPIRY_MINUTES);
            $match->save();
            Log::info('Match timer started', [
                'match' => $match->code,
                'expires_at' => $match->expires_at,
            ]);
        }

        if (!$match->expires_at) return;
        if (Carbon::now()->lte(Carbon::parse($match->expires_at))) return;

        // Expired: unfinished players forfeit.
        $this->applyExpiryForParticipant($match, 'host');
        $this->applyExpiryForParticipant($match, 'guest');
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
        if ($match->status === ChallengeGameMatchStatus::Finished) return;

        $ready = $force
            || $match->host_forfeit || $match->guest_forfeit
            || ($match->host_done && $match->guest_done);

        if (!$ready) return;

        $winner = $this->determineWinner($match);
        $match->status = ChallengeGameMatchStatus::Finished;
        $match->ended_at = Carbon::now();
        $match->save();
        Log::info('Match finalized', [
            'match' => $match->code,
            'winner' => $winner,
            'host_result' => $match->host_result?->value,
            'guest_result' => $match->guest_result?->value,
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

        $hostWon = $match->host_result === ChallengeGameMatchResult::Won;
        $guestWon = $match->guest_result === ChallengeGameMatchResult::Won;

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

    private function activeMatch(?string $matchCode): ?ChallengeGameMatch
    {
        if (!$matchCode) {
            return null;
        }

        $match = ChallengeGameMatch::where('code', $matchCode)->first();
        if (!$match || $match->status === ChallengeGameMatchStatus::Finished) {
            return null;
        }

        return $match;
    }

    private function applyPlayerResult(
        ChallengeGameMatch $match,
        int $playerId,
        ChallengeGameMatchResult $result,
        bool $markDone,
        bool $markForfeit = false,
    ): void {
        $fields = $match->host_player_id === $playerId
            ? $this->participantResultFields('host', $result, $markDone, $markForfeit)
            : ($match->guest_player_id === $playerId
                ? $this->participantResultFields('guest', $result, $markDone, $markForfeit)
                : []);

        if (!$fields) {
            return;
        }

        $match->fill($fields);
        $match->save();
    }

    /**
     * @return array<string, bool|ChallengeGameMatchResult>
     */
    private function participantResultFields(
        string $participant,
        ChallengeGameMatchResult $result,
        bool $markDone,
        bool $markForfeit,
    ): array {
        return array_filter([
            "{$participant}_result" => $result,
            "{$participant}_done" => $markDone ? true : null,
            "{$participant}_forfeit" => $markForfeit ? true : null,
        ], static fn ($value) => $value !== null);
    }

    private function applyExpiryForParticipant(ChallengeGameMatch $match, string $participant): void
    {
        $doneField = "{$participant}_done";
        if ($match->{$doneField}) {
            return;
        }

        $forfeitField = "{$participant}_forfeit";
        $resultField = "{$participant}_result";
        $match->{$forfeitField} = true;
        $match->{$doneField} = true;
        $match->{$resultField} = ChallengeGameMatchResult::Forfeit;
    }
}
