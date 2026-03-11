<?php

namespace App\Http\Middleware;

use App\Models\Player;
use Closure;
use Illuminate\Http\Request;

class EnsurePlayerSession
{
    public function handle(Request $request, Closure $next)
    {
        $playerId = $request->session()->get('player_id');
        $sessionToken = $request->session()->get('player_token');

        if (!$playerId || !$sessionToken) {
            return redirect()->route('players.login.form');
        }

        $player = Player::find($playerId);
        if (!$player || $player->session_token !== $sessionToken) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('players.login.form')->withErrors(['auth' => 'Session expired. Please sign in again.']);
        }

        return $next($request);
    }
}
