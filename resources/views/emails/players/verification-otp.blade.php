<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827;">
    <h1 style="font-size: 20px; margin-bottom: 12px;">Verify your Word Quest account</h1>

    <p>Hello {{ $player->username }},</p>

    <p>Use the OTP below to verify your email address and activate your player account.</p>

    <p style="font-size: 28px; font-weight: 700; letter-spacing: 6px; margin: 20px 0;">
        {{ $otp }}
    </p>

    <p>This code expires in {{ $expiresInMinutes }} minutes.</p>
    <p>Your player ID: <strong>{{ $player->public_id }}</strong></p>
</div>
