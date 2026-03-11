<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('players.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:32|alpha_num|unique:players,username',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $player = Player::create([
            'username' => trim($request->string('username')),
            'password' => Hash::make($request->string('password')),
            'wins' => 0,
            'losses' => 0,
            'games_played' => 0,
            'session_token' => (string) Str::uuid(),
        ]);

        $request->session()->put('player_id', $player->id);
        $request->session()->put('player_token', $player->session_token);

        return redirect()->route('lobby.index');
    }
}
