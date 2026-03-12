# Google SSO Flow

This document describes the Google single sign-on flow implemented with Laravel Socialite.

## Purpose

- Allow players to sign in or register with Google
- Reuse existing player accounts when the Google email matches an existing email
- Mark Google-based accounts as verified without OTP

## Routes

- `GET /auth/google/redirect` -> `players.google.redirect`
- `GET /auth/google/callback` -> `players.google.callback`

## Core Files

- `app/Http/Controllers/PlayerGoogleAuthController.php`
- `app/Models/Player.php`
- `config/services.php`
- `routes/web.php`
- `database/migrations/2026_03_12_130000_add_google_id_to_players_table.php`

## Data Model

The `players` table now stores:

- `google_id`: unique Google account identifier

## Flow

1. User clicks `Continue with Google`
2. The app redirects to Google using Socialite
3. Google redirects back to `/auth/google/callback`
4. The callback resolves the Google user profile
5. The app tries to match the player in this order:
   - by `google_id`
   - by `email`
6. If no player exists, a new player is created with:
   - generated `public_id`
   - generated unique username
   - verified email
   - null password
   - linked `google_id`
7. A local `session_token` is created and the player is signed in

## Account Linking Rules

- If `google_id` already exists, that player is used
- If email exists and `google_id` is empty, the existing player is linked to Google
- If `google_id` and email point to different player records, login is rejected to avoid unsafe linking

## Operational Notes

- Google-created accounts do not require OTP verification
- Google-created accounts can have `password = null`
- Password login will reject those accounts and direct the user to Google sign-in
- Google credentials are configured in `.env` and read via `config/services.php`
