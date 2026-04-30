<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class PlayerGoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();

            $googleId = trim((string) $googleUser->getId());
            $email = Str::lower(trim((string) $googleUser->getEmail()));

            if ($googleId === '' || $email === '') {
                return redirect()
                    ->route('players.login.form')
                    ->withErrors(['auth' => 'Google did not return a usable account email.']);
            }

            $player = $this->resolvePlayer($googleUser, $googleId, $email);
            if (!$player) {
                return redirect()
                    ->route('players.login.form')
                    ->withErrors(['auth' => 'Google account could not be linked safely.']);
            }

            $token = (string) Str::uuid();
            $player->forceFill([
                'session_token' => $token,
            ])->save();

            $request->session()->forget('pending_player_id');
            $request->session()->put('player_id', $player->id);
            $request->session()->put('player_token', $token);

            return redirect()->route('lobby.index')->with('status', 'Signed in with Google.');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('players.login.form')
                ->withErrors(['auth' => 'Google sign-in failed. Please try again.']);
        }
    }

    private function resolvePlayer(SocialiteUser $googleUser, string $googleId, string $email): ?Player
    {
        $playerByGoogle = Player::where('google_id', $googleId)->first();
        $playerByEmail = Player::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if ($playerByGoogle && $playerByEmail && !$playerByGoogle->is($playerByEmail)) {
            return null;
        }

        $player = $playerByGoogle ?? $playerByEmail;

        if ($player) {
            $player->forceFill([
                'google_id' => $googleId,
                'email' => $email,
                'email_verified_at' => $player->email_verified_at ?? now(),
                'email_verification_code' => null,
                'email_verification_expires_at' => null,
            ])->save();

            return $player;
        }

        return Player::create([
            'public_id' => (string) Str::ulid(),
            'username' => $this->uniqueUsername($googleUser),
            'email' => $email,
            'google_id' => $googleId,
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'password' => null,
            'wins' => 0,
            'losses' => 0,
            'games_played' => 0,
            'session_token' => null,
        ]);
    }

    private function uniqueUsername(SocialiteUser $googleUser): string
    {
        $seed = $googleUser->getNickname()
            ?: $googleUser->getName()
            ?: Str::before((string) $googleUser->getEmail(), '@')
            ?: 'player';

        $normalized = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($seed)) ?: 'player';
        $base = Str::lower(Str::limit($normalized, 20, ''));

        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'x');
        }

        $candidate = $base;
        $suffix = 1;

        while (Player::query()->whereRaw('lower(username) = ?', [Str::lower($candidate)])->exists()) {
            $candidate = Str::limit($base, 20 - strlen((string) $suffix), '') . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
