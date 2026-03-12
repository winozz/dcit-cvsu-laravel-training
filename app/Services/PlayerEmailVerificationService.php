<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Facades\Hash;

class PlayerEmailVerificationService
{
    private const OTP_TTL_MINUTES = 10;

    public function issue(Player $player): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $player->forceFill([
            'email_verification_code' => Hash::make($otp),
            'email_verification_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ])->save();

        return $otp;
    }

    public function verify(Player $player, string $otp): bool
    {
        if (!$this->hasActiveCode($player)) {
            return false;
        }

        if (!Hash::check($otp, (string) $player->email_verification_code)) {
            return false;
        }

        $player->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();

        return true;
    }

    public function hasActiveCode(Player $player): bool
    {
        return filled($player->email_verification_code)
            && $player->email_verification_expires_at !== null
            && now()->lte($player->email_verification_expires_at);
    }

    public function ttlMinutes(): int
    {
        return self::OTP_TTL_MINUTES;
    }
}
