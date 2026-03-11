@push('head')
<style>
    h1 { margin:0 0 16px; }
    /* Use simple stacked cards (no flex/grid) */
    .grid { display:block; }
    .grid .card + .card { margin-top:16px; }
    .card { background:#1a2142; border:2px solid #2f3a66; border-radius:10px; padding:16px; box-shadow:0 10px 24px rgba(0,0,0,0.35); }
    .card h2 { margin:0 0 8px; font-size:18px; color:#ffd166; }
    .card p { margin:0 0 12px; color:#c7d6ff; }
    .card a { display:inline-block; padding:10px 14px; background:#2a6df5; color:#fff; text-decoration:none; border-radius:6px; border:2px solid #fff; box-shadow:0 4px 0 #000; font-weight:700; }
    .card a:active { transform:translateY(1px); box-shadow:0 2px 0 #000; }
    .danger-btn { padding:10px 14px;background:#b86b00;color:#fff;border:2px solid #fff;border-radius:6px;box-shadow:0 4px 0 #000;font-weight:700;cursor:pointer; }
    .danger-btn:active { transform:translateY(1px); box-shadow:0 2px 0 #000; }
</style>
@endpush

<x-app title="Games Collection">
    <x-pixel-panel title="Games Collection" class="mb-4">
        <x-flash />
        <div class="flex flex-wrap gap-2 mb-3">
            @if(empty($guestMode))
                <a class="card a" style="padding:10px 14px;background:#57f287;color:#0b1020;border:2px solid #fff;border-radius:6px;box-shadow:0 4px 0 #000;font-weight:700;text-decoration:none;" href="{{ route('games.create') }}">Create Game</a>
                <a class="card a" style="padding:10px 14px;background:#2a6df5;color:#fff;border:2px solid #fff;border-radius:6px;box-shadow:0 4px 0 #000;font-weight:700;text-decoration:none;" href="{{ route('lobby.index') }}">Try PvP Multiplayer</a>
            @else
                <a class="card a" style="padding:10px 14px;background:#2a6df5;color:#fff;border:2px solid #fff;border-radius:6px;box-shadow:0 4px 0 #000;font-weight:700;text-decoration:none;" href="{{ route('players.login.form') }}">
                    Guest mode: sign in for PvP & custom games
                </a>
            @endif
        </div>
        <div class="grid">
            @foreach($games as $game)
                @continue(($game['slug'] ?? '') === 'word-quest')
                <div class="card">
                    <h2>{{ $game['name'] }}</h2>
                    <p>{{ $game['description'] }}</p>
                    <a href="{{ empty($guestMode)
                        ? route($game['route'], ['game' => $game['slug'] ?? \Illuminate\Support\Str::slug($game['name'])])
                        : route('guest.games.show', ['game' => $game['slug'] ?? \Illuminate\Support\Str::slug($game['name'])])
                    }}">Play</a>
                </div>
            @endforeach
        </div>
        @if(empty($guestMode))
            <form method="POST" action="{{ route('games.destroyAll') }}" style="margin-top:16px;">
                @csrf
                @method('DELETE')
                <button type="submit" class="danger-btn">Clear Custom Games</button>
            </form>
        @endif
    </x-pixel-panel>
</x-app>
