<?php

namespace App\Http\Controllers;

use App\Enums\ChallengeGameMatchStatus;
use App\Models\ChallengeGameMatch;
use App\Models\Player;
use App\Services\MatchOutcomeService;
use App\Services\MatchProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatchProgressController extends Controller
{
    public function __construct(
        private readonly MatchOutcomeService $matchOutcome,
        private readonly MatchProgressService $matchProgress,
    ) {}
    public function stream(Request $request, ChallengeGameMatch $match): StreamedResponse
    {
        // Prevent long-running SSE from hitting the default 30s PHP timeout.
        set_time_limit(0);
        $player = $this->authorizeMatchAccess($request, $match);

        $opponentId = $this->resolveOpponentId($match, $player);
        if (!$opponentId) {
            // No opponent yet; return quickly to allow client retry.
            return response()->noContent(204);
        }

        $lastEventId = (int) ($request->header('Last-Event-ID') ?? $request->query('last', 0));
        $matchCode = $match->code;

        return response()->stream(function () use ($matchCode, $opponentId, &$lastEventId) {
            // Run a short-lived loop (< max_execution_time); client will reconnect.
            for ($i = 0; $i < 20; $i++) {
                $progress = $this->matchProgress->get($matchCode, $opponentId);
                $ts = $progress?->version ?? 0;

                if ($progress && $ts !== $lastEventId) {
                    $payload = json_encode($progress->toArray());
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

    public function opponent(Request $request, ChallengeGameMatch $match): JsonResponse
    {
        $player = $this->authorizeMatchAccess($request, $match);

        $opponentId = $this->resolveOpponentId($match, $player);
        if (!$opponentId) return response()->json([], 204);

        // Enforce expiry timers on each poll and reload match status
        $this->matchOutcome->markProgress($match->code, $player->id, false, false);
        $match->refresh();

        // If match is finished, clear any stale opponent cache and return empty
        if ($match->status === ChallengeGameMatchStatus::Finished) {
            $this->matchProgress->forget($match->code, $opponentId);
            $this->matchProgress->forget($match->code, $player->id);
            return response()->json([], 204);
        }

        $progress = $this->matchProgress->get($match->code, $opponentId);
        return $progress ? response()->json($progress->toArray()) : response()->json([], 204);
    }

    public function forfeit(Request $request, ChallengeGameMatch $match): RedirectResponse
    {
        $player = $this->authorizeMatchAccess($request, $match);

        $this->matchOutcome->forfeit($match->code, $player->id);

        return redirect()->route('lobby.index')->with('status', 'You forfeited this match.');
    }

    public function status(Request $request, ChallengeGameMatch $match): JsonResponse
    {
        $player = $this->authorizeMatchAccess($request, $match);

        // Apply expiry on each status check
        $this->matchOutcome->markProgress($match->code, $player->id, false, false);
        $match->refresh();

        return response()->json([
            'status' => $match->status->value,
            'ended_at' => $match->ended_at,
            'host_result' => $match->host_result?->value,
            'guest_result' => $match->guest_result?->value,
            'expires_at' => $match->expires_at,
        ]);
    }

    public function exit(Request $request, ChallengeGameMatch $match): RedirectResponse
    {
        $player = $this->authorizeMatchAccess($request, $match);

        // Only count as forfeit if an opponent exists and match is active
        $hasOpponent = $match->host_player_id !== null && $match->guest_player_id !== null;
        if ($match->status !== ChallengeGameMatchStatus::Finished && $hasOpponent) {
            $this->matchOutcome->forfeit($match->code, $player->id);
        }

        return redirect()->route('lobby.index')->with('status', 'Returned to lobby.');
    }

    private function authorizeMatchAccess(Request $request, ChallengeGameMatch $match): Player
    {
        $player = $this->playerFromSession($request);
        $this->authorizeForUser($player, 'view', $match);

        return $player;
    }

    private function playerFromSession(Request $request): Player
    {
        $playerId = $request->session()->get('player_id');
        abort_if(!$playerId, 403);

        return Player::findOrFail($playerId);
    }

    private function resolveOpponentId(ChallengeGameMatch $match, Player $player): ?int
    {
        return $match->host_player_id === $player->id
            ? $match->guest_player_id
            : $match->host_player_id;
    }
}
