<?php

namespace Tests\Feature;

use App\Mail\PlayerVerificationOtpMail;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlayerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_email_verification_before_entering_lobby(): void
    {
        Mail::fake();

        $response = $this->post(route('players.register'), [
            'username' => 'wordsmith',
            'email' => 'wordsmith@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $player = Player::firstOrFail();

        $response
            ->assertRedirect(route('players.verification.notice'))
            ->assertSessionHas('pending_player_id', $player->id);

        $this->assertSame('wordsmith@example.com', $player->email);
        $this->assertNotNull($player->public_id);
        $this->assertNull($player->email_verified_at);
        $this->assertNull(session('player_id'));

        Mail::assertSent(PlayerVerificationOtpMail::class, function (PlayerVerificationOtpMail $mail) use ($player) {
            return $mail->player->is($player) && $mail->otp !== '';
        });
    }

    public function test_player_can_verify_email_with_otp_and_is_logged_in(): void
    {
        Mail::fake();

        $this->post(route('players.register'), [
            'username' => 'quester',
            'email' => 'quester@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $player = Player::where('email', 'quester@example.com')->firstOrFail();

        $otp = null;
        Mail::assertSent(PlayerVerificationOtpMail::class, function (PlayerVerificationOtpMail $mail) use (&$otp, $player) {
            if (!$mail->player->is($player)) {
                return false;
            }

            $otp = $mail->otp;

            return true;
        });

        $response = $this->post(route('players.verification.verify'), [
            'email' => $player->email,
            'otp' => $otp,
        ]);

        $player->refresh();

        $response
            ->assertRedirect(route('lobby.index'))
            ->assertSessionHas('player_id', $player->id)
            ->assertSessionHas('player_token');

        $this->assertNotNull($player->email_verified_at);
        $this->assertNull($player->email_verification_code);
        $this->assertNull($player->email_verification_expires_at);
    }
}
