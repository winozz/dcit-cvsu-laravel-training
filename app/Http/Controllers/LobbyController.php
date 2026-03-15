<?php

namespace App\Http\Controllers;

use App\Enums\ChallengeGameMatchStatus;
use App\Models\ChallengeGameMatch;
use App\Models\Player;
use App\Services\Contracts\GameServiceInterface;
use App\Services\MatchProgressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LobbyController extends Controller
{
    public function __construct(
        private readonly GameServiceInterface $gameService,
        private readonly MatchProgressService $matchProgress,
    ) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $player = $this->player($request);
        $activeMatch = $this->currentMatch($player);
        $waitingMatches = ChallengeGameMatch::with(['host', 'guest'])
            ->where('status', ChallengeGameMatchStatus::Waiting->value)
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'waiting' => $waitingMatches->map(fn ($m) => [
                    'code' => $m->code,
                    'host' => $m->host?->username,
                    'status' => $m->status->value,
                ])->values(),
                'active_match_code' => $activeMatch?->code,
            ]);
        }

        return view('lobby.index', compact('player', 'activeMatch', 'waitingMatches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $player = $this->player($request);
        $this->clearPreviousMatchProgress($player);

        $code = strtoupper(Str::random(6));

        $match = ChallengeGameMatch::create([
            'code' => $code,
            'host_player_id' => $player->id,
            'status' => ChallengeGameMatchStatus::Waiting,
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
        if (!Gate::forUser($player)->allows('join', $match)) {
            return redirect()->route('lobby.index')->withErrors(['join' => 'Match is not joinable.']);
        }

        $this->clearPreviousMatchProgress($player);

        $match->update([
            'guest_player_id' => $player->id,
            'status' => ChallengeGameMatchStatus::Active,
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

    private function clearPreviousMatchProgress(Player $player): void
    {
        $previousMatchCode = session('current_match_code');
        if (!$previousMatchCode) {
            return;
        }

        Cache::forget($this->matchProgress->key($previousMatchCode, $player->id));
    }

    private function currentMatch(Player $player): ?ChallengeGameMatch
    {
        return ChallengeGameMatch::with(['host', 'guest'])
            ->where(function ($q) use ($player) {
                $q->where('host_player_id', $player->id)
                  ->orWhere('guest_player_id', $player->id);
            })
            ->whereNot('status', ChallengeGameMatchStatus::Finished->value)
            ->latest()
            ->first();
    }

}
