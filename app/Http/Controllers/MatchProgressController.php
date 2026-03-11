<?php

namespace App\Http\Controllers;

use App\Models\ChallengeGameMatch;
use App\Services\MatchOutcomeService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatchProgressController extends Controller
{
    public function __construct(private readonly MatchOutcomeService $matchOutcome) {}
    public function stream(Request $request, ChallengeGameMatch $match): StreamedResponse
    {
        // Prevent long-running SSE from hitting the default 30s PHP timeout.
        set_time_limit(0);
        $playerId = $request->session()->get('player_id');
        if (!$playerId) {
            abort(403);
        }

        $opponentId = $match->host_player_id === $playerId ? $match->guest_player_id : $match->host_player_id;
        if (!$opponentId) {
            // No opponent yet; return quickly to allow client retry.
            return response()->noContent(204);
        }

        $lastEventId = (int) ($request->header('Last-Event-ID') ?? $request->query('last', 0));
        $cacheKey = $this->progressKey($match->code, $opponentId);

        return response()->stream(function () use ($cacheKey, &$lastEventId) {
            // Run a short-lived loop (< max_execution_time); client will reconnect.
            for ($i = 0; $i < 20; $i++) {
                $progress = Cache::get($cacheKey);
                $ts = (int) ($progress['version'] ?? 0);

                if ($progress && $ts !== $lastEventId) {
                    $payload = json_encode($progress);
                    echo "id: {$ts}\n";
                    echo "event: progress\n";
                    echo "data: {$payload}\n\n";
                    @ob_flush();
                    flush();
                    $lastEventId = $ts;
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function progressKey(string $matchCode, int $playerId): string
    {
        return "matches.$matchCode.players.$playerId.progress";
    }

    public function opponent(Request $request, ChallengeGameMatch $match)
    {
        $playerId = $request->session()->get('player_id');
        if (!$playerId) abort(403);

        $opponentId = $match->host_player_id === $playerId ? $match->guest_player_id : $match->host_player_id;
        if (!$opponentId) return response()->json([], 204);

        $cacheKey = $this->progressKey($match->code, $opponentId);

        // Enforce expiry timers on each poll and reload match status
        $this->matchOutcome->markProgress($match->code, $playerId, false, false);
        $match->refresh();

        // If match is finished, clear any stale opponent cache and return empty
        if ($match->status === 'finished') {
            Cache::forget($cacheKey);
            Cache::forget($this->progressKey($match->code, $playerId));
            return response()->json([], 204);
        }

        $progress = Cache::get($cacheKey);
        return $progress ? response()->json($progress) : response()->json([], 204);
    }

    public function forfeit(Request $request, ChallengeGameMatch $match): RedirectResponse
    {
        $playerId = $request->session()->get('player_id');
        if (!$playerId) abort(403);

        $this->matchOutcome->forfeit($match->code, $playerId);

        return redirect()->route('lobby.index')->with('status', 'You forfeited this match.');
    }

    public function status(Request $request, ChallengeGameMatch $match)
    {
        $playerId = $request->session()->get('player_id');
        if (!$playerId) abort(403);

        // Apply expiry on each status check
        $this->matchOutcome->markProgress($match->code, $playerId, false, false);
        $match->refresh();

        return response()->json([
            'status' => $match->status,
            'ended_at' => $match->ended_at,
            'host_result' => $match->host_result,
            'guest_result' => $match->guest_result,
            'expires_at' => $match->expires_at,
        ]);
    }

    public function exit(Request $request, ChallengeGameMatch $match): RedirectResponse
    {
        $playerId = $request->session()->get('player_id');
        if (!$playerId) abort(403);

        if ($match->status !== 'finished') {
            $this->matchOutcome->forfeit($match->code, $playerId);
        }

        return redirect()->route('lobby.index')->with('status', 'Returned to lobby.');
    }
}
