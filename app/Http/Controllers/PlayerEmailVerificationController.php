<?php

namespace App\Http\Controllers;

use App\Events\PlayerVerificationRequested;
use App\Models\Player;
use App\Services\PlayerEmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlayerEmailVerificationController extends Controller
{
    public function __construct(private readonly PlayerEmailVerificationService $verification)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $player = $this->pendingPlayer($request);

        if (!$player) {
            return redirect()->route('players.login.form');
        }

        if (!$player->requiresEmailVerification()) {
            return redirect()->route('players.login.form')->with('status', 'Your email is already verified. Please sign in.');
        }

        return view('players.verify', [
            'player' => $player,
            'ttlMinutes' => $this->verification->ttlMinutes(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $player = Player::where('email', Str::lower(trim($validated['email'])))->first();
        if (!$player || !$player->requiresEmailVerification()) {
            return redirect()->route('players.login.form')
                ->withErrors(['email' => 'This account is already verified or does not exist.']);
        }

        if (!$this->verification->verify($player, $validated['otp'])) {
            return back()
                ->withErrors(['otp' => 'Invalid or expired verification code.'])
                ->onlyInput('email');
        }

        $token = (string) Str::uuid();
        $player->forceFill(['session_token' => $token])->save();

        $request->session()->forget('pending_player_id');
        $request->session()->put('player_id', $player->id);
        $request->session()->put('player_token', $token);

        return redirect()->route('lobby.index')->with('status', 'Email verified. Welcome to the lobby.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $player = Player::where('email', Str::lower(trim($validated['email'])))->first();
        if (!$player || !$player->requiresEmailVerification()) {
            return redirect()->route('players.login.form')
                ->withErrors(['email' => 'This account is already verified or does not exist.']);
        }

        $request->session()->put('pending_player_id', $player->id);
        event(new PlayerVerificationRequested($player));

        return back()->with('status', 'A new verification code was sent to your email address.');
    }

    private function pendingPlayer(Request $request): ?Player
    {
        $pendingPlayerId = $request->session()->get('pending_player_id');

        if (!$pendingPlayerId) {
            return null;
        }

        return Player::find($pendingPlayerId);
    }
}
