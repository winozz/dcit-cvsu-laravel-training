<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class PlayerGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_creates_a_verified_player_and_logs_them_in(): void
    {
        $provider = Mockery::mock();
        $socialiteUser = Mockery::mock(SocialiteUser::class);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        $socialiteUser->shouldReceive('getId')->andReturn('google-user-1');
        $socialiteUser->shouldReceive('getEmail')->andReturn('quester@gmail.com');
        $socialiteUser->shouldReceive('getNickname')->andReturn('quester');
        $socialiteUser->shouldReceive('getName')->andReturn('Quester');

        $response = $this->get(route('players.google.callback'));

        $player = Player::firstOrFail();

        $response
            ->assertRedirect(route('lobby.index'))
            ->assertSessionHas('player_id', $player->id)
            ->assertSessionHas('player_token');

        $this->assertSame('google-user-1', $player->google_id);
        $this->assertSame('quester@gmail.com', $player->email);
        $this->assertNotNull($player->email_verified_at);
        $this->assertNull($player->email_verification_code);
    }

    public function test_google_callback_links_an_existing_email_account(): void
    {
        $player = Player::create([
            'public_id' => (string) Str::ulid(),
            'username' => 'wordsmith',
            'email' => 'wordsmith@example.com',
            'password' => null,
            'wins' => 0,
            'losses' => 0,
            'games_played' => 0,
            'session_token' => null,
        ]);

        $provider = Mockery::mock();
        $socialiteUser = Mockery::mock(SocialiteUser::class);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        $socialiteUser->shouldReceive('getId')->andReturn('google-user-2');
        $socialiteUser->shouldReceive('getEmail')->andReturn('wordsmith@example.com');
        $socialiteUser->shouldReceive('getNickname')->andReturn('wordsmith');
        $socialiteUser->shouldReceive('getName')->andReturn('Word Smith');

        $response = $this->get(route('players.google.callback'));

        $player->refresh();

        $response
            ->assertRedirect(route('lobby.index'))
            ->assertSessionHas('player_id', $player->id);

        $this->assertSame('google-user-2', $player->google_id);
        $this->assertNotNull($player->email_verified_at);
    }
}
