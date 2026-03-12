<?php

namespace App\Http\Controllers;

use App\Events\PlayerVerificationRequested;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PlayerAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('players.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $username = trim((string) $request->string('username'));

        $request->merge([
            'username' => $username,
        ]);

        $request->validate([
            'username' => 'required|string|min:3|max:32',
            'password' => 'required|string|min:8',
        ]);

        $player = Player::query()
            ->whereRaw('lower(username) = ?', [Str::lower($username)])
            ->first();

        if (!$player) {
            return redirect()->route('players.register.form')->withErrors(['username' => 'Account not found. Please create one.']);
        }

        if (!$player->password) {
            return back()->withErrors(['password' => 'This account uses Google sign-in. Use Continue with Google.']);
        }

        if (!Hash::check($request->input('password'), $player->password)) {
            return back()->withErrors(['password' => 'Invalid credentials.']);
        }

        if ($player->requiresEmailVerification()) {
            $request->session()->forget(['player_id', 'player_token']);
            $request->session()->put('pending_player_id', $player->id);
            event(new PlayerVerificationRequested($player));

            return redirect()->route('players.verification.notice')
                ->with('status', 'Verify your email first. We sent a fresh OTP to your inbox.');
        }

        $token = (string) Str::uuid();
        $player->update(['session_token' => $token]);

        $request->session()->forget('pending_player_id');
        $request->session()->put('player_id', $player->id);
        $request->session()->put('player_token', $token);

        return redirect()->route('lobby.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('players.login.form');
    }
}
