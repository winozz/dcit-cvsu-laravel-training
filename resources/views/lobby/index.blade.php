<x-app title="Word Quest Lobby">
    <div class="surface flex flex-col gap-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <p class="chip">Signed in as</p>
                <h2 class="text-2xl font-bold text-white">{{ $player->username }}</h2>
            </div>
            <form method="POST" action="{{ route('players.logout') }}">
                @csrf
                <button class="btn secondary h-9 px-3 text-[10px]" type="submit">Logout</button>
            </form>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Your POV</h3>
                    @if($activeMatch)
                        <span class="chip">Match {{ $activeMatch->code }}</span>
                    @endif
                </div>
                @if($activeMatch)
                    <p class="text-sm text-white/70 mb-2">Status: {{ ucfirst($activeMatch->status) }}</p>
                    <p class="text-sm text-white/70 mb-2">Opponent: {{ optional($activeMatch->host_player_id === $player->id ? $activeMatch->guest : $activeMatch->host)->username ?? 'Waiting...' }}</p>
                    <a class="btn green w-full" href="{{ route('games.show', ['game' => 'word-quest', 'match' => $activeMatch->code]) }}">Play Your Board</a>
                @else
                    <p class="text-sm text-white/70 mb-3">Create a room and wait for an opponent.</p>
                    <form method="POST" action="{{ route('lobby.store') }}">
                        @csrf
                        <button class="btn green w-full" type="submit">Create 1v1 Room</button>
                    </form>
                @endif
            </div>

            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold">Opponent POV</h3>
                </div>
                @if($activeMatch && $activeMatch->guest && $activeMatch->host)
                    <p class="text-sm text-white/70">Opponent: {{ $activeMatch->host_player_id === $player->id ? $activeMatch->guest->username : $activeMatch->host->username }}</p>
                    <p class="text-sm text-white/50">They play on their own board. Track status on refresh for now.</p>
                @elseif($activeMatch)
                    <p class="text-sm text-white/70">Waiting for someone to join your room.</p>
                @else
                    <p class="text-sm text-white/70">Join an open room to battle.</p>
                @endif
            </div>
        </div>

        <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Joinable Rooms</h3>
                @if(session('status'))
                    <span class="chip bg-[var(--accent)] text-[#0b1020]">{{ session('status') }}</span>
                @endif
                @error('join')
                    <span class="chip bg-[var(--danger)]">{{ $message }}</span>
                @enderror
            </div>
            @forelse($waitingMatches as $match)
                <div class="flex items-center justify-between py-2 border-b border-white/10 last:border-0">
                    <div>
                        <p class="font-semibold text-white">Room {{ $match->code }}</p>
                        <p class="text-xs text-white/60">Host: {{ $match->host->username }}</p>
                    </div>
                    <form method="POST" action="{{ route('lobby.join', $match) }}">
                        @csrf
                        <button class="btn secondary h-8 px-3 text-[10px]" type="submit">Join</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-white/60">No open rooms yet. Create one!</p>
            @endforelse
        </div>
    </div>
</x-app>
