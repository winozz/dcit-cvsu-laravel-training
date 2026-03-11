<?php

namespace App\Http\Controllers;

use App\Models\ChallengeGameMatch;
use App\Models\Player;
use App\Services\GameService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LobbyController extends Controller
{
    public function __construct(private readonly GameService $gameService) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $player = $this->player($request);
        $activeMatch = $this->currentMatch($player);
        $waitingMatches = ChallengeGameMatch::with(['host', 'guest'])
            ->where('status', 'waiting')
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'waiting' => $waitingMatches->map(fn ($m) => [
                    'code' => $m->code,
                    'host' => $m->host?->username,
                    'status' => $m->status,
                ])->values(),
                'active_match_code' => $activeMatch?->code,
            ]);
        }

        return view('lobby.index', compact('player', 'activeMatch', 'waitingMatches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $player = $this->player($request);
        // Clear any lingering progress caches from a previous match for this player
        $prevMatch = session('current_match_code');
        if ($prevMatch) {
            Cache::forget($this->progressKey($prevMatch, $player->id));
        }

        $code = strtoupper(Str::random(6));

        $match = ChallengeGameMatch::create([
            'code' => $code,
            'host_player_id' => $player->id,
            'status' => 'waiting',
            'expires_at' => null, // timer starts once an opponent joins
        ]);

        // Fresh board and history for a new match
        $this->gameService->resetProgress('word-quest', true);
        session(['current_match_code' => $code]);

        // Host immediately starts their own board; lobby entry stays joinable for opponent.
        return redirect()->route('games.show', ['game' => 'word-quest', 'match' => $match->code])
            ->with('status', "Room {$match->code} created. Waiting for opponent...");
    }

    public function join(Request $request, ChallengeGameMatch $match): RedirectResponse
    {
        $player = $this->player($request);
        if ($match->guest_player_id || $match->host_player_id === $player->id) {
            return redirect()->route('lobby.index')->withErrors(['join' => 'Match is not joinable.']);
        }

        // Clear stale cache from previous match for this player
        $prevMatch = session('current_match_code');
        if ($prevMatch) {
            Cache::forget($this->progressKey($prevMatch, $player->id));
        }

        $match->update([
            'guest_player_id' => $player->id,
            'status' => 'active',
            'expires_at' => now()->addMinutes(2), // 2-minute match timer
        ]);

        // Fresh board and history for a new match join
        $this->gameService->resetProgress('word-quest', true);
        session(['current_match_code' => $match->code]);

        return redirect()->route('games.show', ['game' => 'word-quest', 'match' => $match->code])
            ->with('status', "Joined match {$match->code}. Game on!");
    }

    private function player(Request $request): Player
    {
        return Player::findOrFail($request->session()->get('player_id'));
    }

    private function currentMatch(Player $player): ?ChallengeGameMatch
    {
        return ChallengeGameMatch::with(['host', 'guest'])
            ->where(function ($q) use ($player) {
                $q->where('host_player_id', $player->id)
                  ->orWhere('guest_player_id', $player->id);
            })
            ->whereNot('status', 'finished')
            ->latest()
            ->first();
    }

    private function progressKey(string $matchCode, int $playerId): string
    {
        return "matches.$matchCode.players.$playerId.progress";
    }
}
