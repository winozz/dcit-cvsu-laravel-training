<?php

namespace App\Http\Controllers;

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
        $request->validate([
            'username' => 'required|string|min:3|max:32',
            'password' => 'required|string|min:8',
        ]);

        $username = trim($request->string('username'));
        $player = Player::where('username', $username)->first();

        if (!$player) {
            return redirect()->route('players.register.form')->withErrors(['username' => 'Account not found. Please create one.']);
        }

        if (!$player->password || !Hash::check($request->input('password'), $player->password)) {
            return back()->withErrors(['password' => 'Invalid credentials.']);
        }

        $token = (string) Str::uuid();
        $player->update(['session_token' => $token]);

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
