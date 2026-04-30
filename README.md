# Word Quest

A real-time competitive word-guessing game where two players race each other to reveal a hidden word before the 2-minute timer runs out. Wrong letters cost HP — run out and you lose the round.

## Features

- **1v1 Multiplayer** — Create or join rooms with a 6-character code. Both players see each other's board update live every 500ms.
- **Word Guessing with HP** — Guess letters to reveal the hidden word. Each wrong guess costs 1 of your 6 HP.
- **Up to 15 Rounds** — Players cycle through words per session; previously seen words are never repeated.
- **Email Auth + OTP Verification** — Players register with username/email/password and verify via a 6-digit OTP (10-minute expiry).
- **Google SSO** — Sign in with Google for instant verified access.
- **Stats Tracking** — Wins, losses, games played, and win rate per player.
- **Guest Mode** — Play solo without an account to try the mechanics.

## Tech Stack

- **Laravel 12** — Routing, controllers, Eloquent ORM, events, queues
- **SQLite** — Default local database
- **Laravel Socialite** — Google OAuth 2.0
- **Soketi** — WebSocket server for real-time broadcasting
- **Mailpit** — Local email catch-all for OTP testing
- **Docker** — ServersideUp PHP stack via `compose.yml`

## Local Setup

### Without Docker

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed          # seeds word/category data
npm install && npm run dev
php artisan serve
```

App runs at `http://localhost:8000`. Update your `.env`:

```env
APP_URL=http://localhost:8000
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### With Docker

```bash
cp .env.example .env
# Fill in APP_KEY, GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET in .env
docker compose up --build
```

App runs at `http://localhost:8002`. The compose file overrides `APP_URL` automatically.

Run migrations inside the container:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## Environment Variables

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel app key — generate with `php artisan key:generate` |
| `APP_URL` | Base URL the app is served from |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `GOOGLE_REDIRECT_URI` | Must match an authorized URI in Google Cloud Console |
| `MAIL_MAILER` | Set to `smtp` + Mailpit config for local OTP email testing |

## Google OAuth Setup

1. Create a project in [Google Cloud Console](https://console.cloud.google.com).
2. Enable the **Google+ API** or **Google Identity** service.
3. Under **Credentials → OAuth 2.0 Client IDs**, add your redirect URI:
   - Local: `http://localhost:8000/auth/google/callback`
   - Docker: `http://localhost:8002/auth/google/callback`
4. Copy the client ID and secret into `.env`.

## Game Flow

```
Register / Login / Google SSO
        ↓
  Email OTP Verification
        ↓
      Lobby
   ┌────┴────┐
Create     Join
 Room      Room
   └────┬────┘
   Match Starts
  (2-min timer)
        ↓
  Guess Letters
  Watch Opponent Live
        ↓
  Win / Lose Round
  → Next Word (up to 15)
        ↓
  Match Result
  Stats Updated
        ↓
     Lobby
```

## Project Structure

```
app/
  Http/Controllers/     # Auth, game, lobby, match controllers
  Services/
    SvcImplem/          # GameService, GameCatalogService
    MatchOutcomeService.php
    MatchProgressService.php
    PlayerEmailVerificationService.php
  Events/               # PlayerVerificationRequested
  Listeners/            # SendPlayerVerificationOtp
  Models/               # Player, ChallengeGameMatch, ChallengeGameRun
resources/views/
  players/              # Login, register, verify
  lobby/                # Lobby index
  game/                 # Game board (show.blade.php)
  games/                # Game create/index
docs/
  auth/                 # Auth flow docs
  multiplayer/          # Match flow docs
```
