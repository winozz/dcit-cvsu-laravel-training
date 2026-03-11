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

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <p class="text-xs text-white/60">Games Played</p>
                <p class="text-2xl font-bold text-white">{{ $player->games_played ?? 0 }}</p>
            </div>
            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <p class="text-xs text-white/60">Wins</p>
                <p class="text-2xl font-bold text-green-300">{{ $player->wins ?? 0 }}</p>
            </div>
            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <p class="text-xs text-white/60">Losses</p>
                <p class="text-2xl font-bold text-red-300">{{ $player->losses ?? 0 }}</p>
            </div>
            <div class="panel p-4 rounded bg-[#0f142a] border border-[#2f3a66]">
                <p class="text-xs text-white/60">Win Rate</p>
                <p class="text-2xl font-bold text-[var(--accent)]">{{ number_format($player->winrate ?? 0, 2) }}%</p>
            </div>
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
                    <div class="mt-2 p-3 rounded bg-[#0b1020] border border-[#1f2950]">
                        <div class="text-xs text-white/60 mb-1">Board</div>
                        <div id="opp-display" class="font-mono tracking-widest text-lg">----</div>
                        <div id="opp-meta" class="text-xs text-white/60 mt-1">Waiting...</div>
                    </div>
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
            <div id="waiting-list">
                @forelse($waitingMatches as $match)
                    <div class="waiting-row flex items-center justify-between py-2 border-b border-white/10 last:border-0">
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
    </div>

    @push('scripts')
    @if($activeMatch)
    <script>
        const opponentUrl = @json(url('matches/' . $activeMatch->code . '/opponent'));
        const oppDisplayEl = document.getElementById('opp-display');
        const oppMetaEl = document.getElementById('opp-meta');
        let poll;

        async function fetchOpponentOnce() {
            if (!opponentUrl || !oppDisplayEl || !oppMetaEl) return;
            try {
                const res = await fetch(opponentUrl, {
                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' },
                    credentials: 'same-origin',
                });
                if (res.status === 204) {
                    oppDisplayEl.textContent = '----';
                    oppMetaEl.textContent = 'Waiting...';
                    return;
                }
                if (!res.ok) return;
                const data = await res.json();
                oppDisplayEl.textContent = data.display || '----';
                const meta = [];
                if (data.tries !== undefined && data.maxTries !== undefined) meta.push(`HP: ${data.maxTries - data.tries}/${data.maxTries}`);
                if (data.foundWordsCount !== undefined) meta.push(`Found: ${data.foundWordsCount}`);
                if (data.usedWordsCount !== undefined) meta.push(`Used: ${data.usedWordsCount}`);
                if (data.won) meta.push('WIN');
                if (data.lost) meta.push('LOST');
                oppMetaEl.textContent = meta.join(' · ') || 'Waiting...';
            } catch (_) {}
        }

        fetchOpponentOnce();
        poll = setInterval(fetchOpponentOnce, 800);
    </script>
    @endif

    <script>
        const waitingUrl = @json(route('lobby.index'));
        const waitingListEl = document.getElementById('waiting-list');

        function renderWaiting(matches) {
            if (!waitingListEl) return;
            if (!matches || !matches.length) {
                waitingListEl.innerHTML = '<p class="text-sm text-white/60">No open rooms yet. Create one!</p>';
                return;
            }
            waitingListEl.innerHTML = matches.map(m => `
                <div class="waiting-row flex items-center justify-between py-2 border-b border-white/10 last:border-0">
                    <div>
                        <p class="font-semibold text-white">Room ${m.code}</p>
                        <p class="text-xs text-white/60">Host: ${m.host || 'Unknown'}</p>
                    </div>
                    <form method="POST" action="${waitingUrl}/matches/${m.code}/join">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button class="btn secondary h-8 px-3 text-[10px]" type="submit">Join</button>
                    </form>
                </div>`).join('');
        }

        async function pollWaiting() {
            if (!waitingUrl) return;
            try {
                const res = await fetch(waitingUrl, {
                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                renderWaiting(data.waiting || []);
            } catch (_) {}
        }

        pollWaiting();
        setInterval(pollWaiting, 1000);
    </script>
    @endpush
</x-app>
