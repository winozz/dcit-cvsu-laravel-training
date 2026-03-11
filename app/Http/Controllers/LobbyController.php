<?php

namespace App\Http\Controllers;

use App\Models\ChallengeGameMatch;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LobbyController extends Controller
{
    public function index(Request $request): View
    {
        $player = $this->player($request);
        $activeMatch = $this->currentMatch($player);
        $waitingMatches = ChallengeGameMatch::with(['host', 'guest'])
            ->where('status', 'waiting')
            ->latest()
            ->get();

        return view('lobby.index', compact('player', 'activeMatch', 'waitingMatches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $player = $this->player($request);
        $code = strtoupper(Str::random(6));

        $match = ChallengeGameMatch::create([
            'code' => $code,
            'host_player_id' => $player->id,
            'status' => 'waiting',
        ]);

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

        $match->update([
            'guest_player_id' => $player->id,
            'status' => 'active',
        ]);

        return redirect()->route('lobby.index')->with('status', "Joined match {$match->code}. Game on!");
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
            ->latest()
            ->first();
    }
}
