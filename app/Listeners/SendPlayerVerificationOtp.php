<?php

namespace App\Listeners;

use App\Events\PlayerVerificationRequested;
use App\Mail\PlayerVerificationOtpMail;
use App\Services\PlayerEmailVerificationService;
use Illuminate\Support\Facades\Mail;

class SendPlayerVerificationOtp
{
    public function __construct(private readonly PlayerEmailVerificationService $verification)
    {
    }

    public function handle(PlayerVerificationRequested $event): void
    {
        $player = $event->player;

        if (!$player->email) {
            return;
        }

        $otp = $this->verification->issue($player);

        Mail::to($player->email)->send(
            new PlayerVerificationOtpMail($player->fresh(), $otp, $this->verification->ttlMinutes())
        );
    }
}
