<?php

namespace App\Http\Controllers;

use App\Events\PlayerVerificationRequested;
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
        $username = trim((string) $request->string('username'));
        $email = Str::lower(trim((string) $request->string('email')));

        $request->merge([
            'username' => $username,
            'email' => $email,
        ]);

        $request->validate([
            'username' => 'required|string|min:3|max:32|alpha_num|unique:players,username',
            'email' => 'required|string|email|max:255|unique:players,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $existingPlayer = Player::query()
            ->whereRaw('lower(username) = ?', [Str::lower($username)])
            ->orWhereRaw('lower(email) = ?', [$email])
            ->first();

        if ($existingPlayer) {
            $field = strcasecmp((string) $existingPlayer->email, $email) === 0 ? 'email' : 'username';

            return back()
                ->withErrors([$field => 'An account with this ' . $field . ' already exists.'])
                ->withInput();
        }

        $player = Player::create([
            'public_id' => (string) Str::ulid(),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make((string) $request->string('password')),
            'wins' => 0,
            'losses' => 0,
            'games_played' => 0,
            'session_token' => null,
        ]);

        $request->session()->forget(['player_id', 'player_token']);
        $request->session()->put('pending_player_id', $player->id);

        event(new PlayerVerificationRequested($player));

        return redirect()->route('players.verification.notice')
            ->with('status', 'We sent a 6-digit verification code to your email address.');
    }
}
