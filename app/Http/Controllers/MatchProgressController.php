<?php

namespace App\Http\Controllers;

use App\Models\ChallengeGameMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatchProgressController extends Controller
{
    public function stream(Request $request, ChallengeGameMatch $match): StreamedResponse
    {
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
            // Run a short-lived loop; client will reconnect.
            for ($i = 0; $i < 60; $i++) {
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

        $progress = Cache::get($this->progressKey($match->code, $opponentId));
        return $progress ? response()->json($progress) : response()->json([], 204);
    }
}
