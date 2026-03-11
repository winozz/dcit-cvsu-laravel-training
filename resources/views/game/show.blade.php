<x-app title="Word Quest">
    <main class="game-layout">
        @php
            $correctCount = count($correct);
            $wrongCount = count($wrong);
            $uniqueLetters = count(array_unique(array_filter(str_split(strtolower($word)), fn($c) => ctype_alpha($c))));
            $keyboardRows = [
                ['q','w','e','r','t','y','u','i','o','p'],
                ['a','s','d','f','g','h','j','k','l'],
                ['z','x','c','v','b','n','m'],
            ];
            $specialKeys = ['+','#'];
            $usedWordsCount = $usedWordsCount ?? count($usedWords ?? []);
            $foundWordsCount = $foundWordsCount ?? count($foundWords ?? []);
            $restartAllowed = $restartAllowed ?? (empty($wrong) && empty($correct));
            $gameSlug = $gameSlug ?? 'word-quest';
            $readonly = $readonly ?? false;
            $matchCode = $matchCode ?? request('match');
            $opponentProgress = $opponentProgress ?? null;
        @endphp

        <section class="surface flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <p class="chip">Arcade · Word Quest</p>
                <h2 class="text-2xl sm:text-3xl font-bold text-white drop-shadow-[3px_3px_0_#000]">WORD QUEST</h2>
                <p class="text-xs text-white/70 max-w-2xl">Crack the hidden word. Each miss burns HP—stay accurate and keep the streak alive.</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-start md:justify-end">
                <span class="chip" aria-label="Category">Category:
                    <span id="category" class="text-white font-semibold">{{ ucfirst(str_replace('_', ' ', $category)) }}</span>
                </span>
                <span class="chip bg-[var(--accent)] text-[#0b1020] border-transparent">
                    Clue: <span id="clue" class="font-bold">{{ $clue }}</span>
                </span>
            </div>
        </section>

        <section class="hud-grid">
            <div class="stat-card"><span class="stat-label">Used Words</span><span id="used-words-count" class="stat-value">{{ $usedWordsCount }}</span></div>
            <div class="stat-card"><span class="stat-label">Found Words</span><span id="found-words-count" class="stat-value">{{ $foundWordsCount }}</span></div>
            <div class="stat-card"><span class="stat-label">Guesses</span><span id="total-guesses" class="stat-value text-[var(--blue)]">{{ $correctCount + $wrongCount }}</span></div>
            <div class="stat-card"><span class="stat-label">Progress</span><span class="stat-value"><span id="progress-count">{{ $correctCount }}</span>/<span id="total-letters">{{ $uniqueLetters }}</span></span></div>
            <div class="stat-card col-span-full md:col-span-2 xl:col-span-1">
                <div class="flex items-center gap-3 w-full">
                    <span class="stat-label">HP</span>
                    <div id="hp-bar" class="hp-bar flex-1" aria-label="Tries health bar">
                        @for($i = 0; $i < $maxTries; $i++)
                            <span class="hp-cell @if($i >= ($maxTries - $tries)) lost @endif"></span>
                        @endfor
                    </div>
                    <span id="tries-remaining" class="text-white text-sm">{{ $maxTries - $tries }}</span>
                </div>
            </div>
            <div class="stat-card flex-wrap gap-2">
                <button id="show-modal-btn" class="btn secondary h-9 px-3 text-[10px]">Word Lists</button>
                @if(!$readonly)
                    <button id="reset-progress-btn" class="btn secondary h-9 px-3 text-[10px]">Reset Progress</button>
                @endif
            </div>
        </section>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="display-panel">
                <div class="flex items-center justify-between mb-2">
                    <div class="section-title mb-0">Your Board</div>
                    @if($matchCode)
                        <span class="chip">Room {{ $matchCode }}</span>
                    @endif
                </div>
                <div id="display" class="display-text">{{ $display }}</div>
            </div>

            <div class="display-panel">
                <div class="flex items-center justify-between mb-2">
                    <div class="section-title mb-0">Opponent Board</div>
                    @if($opponentProgress)
                        <span class="chip">Live</span>
                    @endif
                </div>
                <div id="opponent-board" class="text-lg font-mono tracking-wider">
                    {{ $opponentProgress['display'] ?? '----' }}
                </div>
                <p id="opponent-meta" class="text-white/70 text-xs mt-2">
                    @if($opponentProgress)
                        HP: {{ ($opponentProgress['maxTries'] ?? 0) - ($opponentProgress['tries'] ?? 0) }}/{{ $opponentProgress['maxTries'] ?? '?' }} ·
                        Found: {{ $opponentProgress['foundWordsCount'] ?? 0 }} ·
                        Used: {{ $opponentProgress['usedWordsCount'] ?? 0 }}
                    @else
                        Waiting for opponent progress...
                    @endif
                </p>
            </div>
        </div>

        <div id="status" class="status-bar @if($readonly) readonly @elseif($won) win @elseif($lost) lose @endif">
            @if($readonly)
                All challenges completed. Game is now read-only.
            @elseif($won)
                VICTORY! The word is: {{ $word }} — {{ $correctCount + $wrongCount }} guesses for {{ $uniqueLetters }} letters
            @elseif($lost)
                GAME OVER! The word was: {{ $word }} — {{ $correctCount + $wrongCount }} guesses
            @else
                <span class="text-white/70">Stay sharp — wrong letters cost HP.</span>
            @endif
        </div>

        <p id="wrong-letters" class="text-[11px] leading-7 text-[var(--danger)] min-h-[20px]">
            @if(!empty($wrong) && !$won && !$lost)
                Wrong: {{ strtoupper(implode(', ', $wrong)) }}
            @endif
        </p>

        <div class="keyboard-shell">
            <div class="flex items-center justify-between mb-2">
                <div class="section-title mb-0">Keyboard</div>
                <div class="chip text-white/80">Enter = next round</div>
            </div>
            <div id="keyboard" class="keyboard @if($won || $lost || $readonly) hidden @endif">
                @foreach ($keyboardRows as $row)
                    <div class="keyboard-row">
                        @foreach ($row as $letter)
                            <button class="key" data-letter="{{ $letter }}">{{ strtoupper($letter) }}</button>
                        @endforeach
                    </div>
                @endforeach
                <div class="keyboard-row">
                    @foreach ($specialKeys as $special)
                        <button class="key key-special" data-letter="{{ $special }}">{{ $special }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $nextLabel = $won ? 'Proceed to Next Word' : ($lost ? 'Try Again' : null);
        @endphp
        <div class="surface-ghost flex flex-wrap gap-2 items-center">
            @if(!$readonly)
                <button id="restart-btn" class="btn warn h-9 px-3 text-[10px]" @if(!$restartAllowed) disabled aria-disabled="true" title="Restart disabled after guesses begin" @endif>Restart</button>
                <form id="next-form" class="{{ $nextLabel ? '' : 'hidden' }} inline-block" method="POST" action="{{ route('games.next', ['game' => $gameSlug]) }}">
                    @csrf
                    <button id="next-btn" class="btn green h-9 px-3 text-[10px]" type="submit">{{ $nextLabel ?? 'Next Word' }}</button>
                </form>
            @endif
            <a class="btn secondary h-9 px-3 text-[10px]" href="{{ route('games.index') }}">Home</a>
        </div>

        <div id="word-modal" class="modal hidden">
            <div class="modal-card">
                <h3>Word Lists</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="list-title">Used Words</p>
                        <ul id="used-words-list" class="list-panel list-disc list-inside">
                            @forelse($usedWords ?? [] as $uw)
                                <li>{{ $uw }}</li>
                            @empty
                                <li><em>None</em></li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <p class="list-title">Found Words</p>
                        <ul id="found-words-list" class="list-panel list-disc list-inside">
                            @forelse($foundWords ?? [] as $fw)
                                <li>{{ $fw }}</li>
                            @empty
                                <li><em>None</em></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button id="close-modal-btn" class="btn warn h-9 px-3 text-[10px]" type="button">Close</button>
                </div>
            </div>
        </div>
    </main>

    @push('scripts')
    <script>
        const csrfToken = '{{ csrf_token() }}';
        const matchCode = '{{ $matchCode }}';
        const updateUrl = '{{ route('games.update', ['game' => $gameSlug]) }}' + (matchCode ? `?match=${matchCode}` : '');
        const nextUrl = '{{ route('games.next', ['game' => $gameSlug]) }}' + (matchCode ? `?match=${matchCode}` : '');
        const streamUrl = matchCode ? `{{ url('matches') }}/${matchCode}/stream` : null;
        const gameSlug = '{{ $gameSlug }}';

        const displayEl = document.getElementById('display');
        const categoryEl = document.getElementById('category');
        const clueEl = document.getElementById('clue');
        const triesRemainingEl = document.getElementById('tries-remaining');
        const hpBarEl = document.getElementById('hp-bar');
        const progressCountEl = document.getElementById('progress-count');
        const totalLettersEl = document.getElementById('total-letters');
        const totalGuessesEl = document.getElementById('total-guesses');
        const usedWordsCountEl = document.getElementById('used-words-count');
        const foundWordsCountEl = document.getElementById('found-words-count');
        const statusEl = document.getElementById('status');
        const wrongLettersEl = document.getElementById('wrong-letters');
        const opponentBoardEl = document.getElementById('opponent-board');
        const opponentMetaEl = document.getElementById('opponent-meta');
        const keyboard = document.getElementById('keyboard');
        const resetProgressBtn = document.getElementById('reset-progress-btn');
        const restartBtn = document.getElementById('restart-btn');
        const showModalBtn = document.getElementById('show-modal-btn');
        const modal = document.getElementById('word-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const usedWordsListEl = document.getElementById('used-words-list');
        const foundWordsListEl = document.getElementById('found-words-list');
        const nextBtn = document.getElementById('next-btn');
        const nextForm = document.getElementById('next-form');

        const state = {
            restartAllowed: {{ $restartAllowed ? 'true' : 'false' }},
            nextLabel: @json($nextLabel),
            gameEnded: {{ ($won || $lost) ? 'true' : 'false' }},
            isReadonly: {{ ($readonly ?? false) ? 'true' : 'false' }},
        };
        if (state.isReadonly) state.gameEnded = true;

        const setState = (patch = {}) => Object.assign(state, patch);

        function renderHpBar(maxTries, triesUsed) {
            if (!hpBarEl) return;
            const remaining = maxTries - triesUsed;
            hpBarEl.innerHTML = Array.from({ length: maxTries }, (_, i) => {
                const lost = i >= remaining ? 'lost' : '';
                return `<span class="hp-cell ${lost}"></span>`;
            }).join('');
        }

        function renderWordList(listEl, words) {
            if (!listEl) return;
            const safe = Array.isArray(words) ? words : [];
            listEl.innerHTML = safe.length ? safe.map(w => `<li>${w}</li>`).join('') : '<li><em>None</em></li>';
        }

        function updateKeyboardState(allKeys, data) {
            allKeys.forEach(key => {
                const letter = key.dataset.letter;
                if (!letter) return;
                if (data.correct?.includes(letter)) {
                    key.disabled = true;
                    key.classList.add('correct');
                    key.classList.remove('wrong');
                } else if (data.wrong?.includes(letter)) {
                    key.disabled = true;
                    key.classList.add('wrong');
                    key.classList.remove('correct');
                } else {
                    key.disabled = false;
                    key.classList.remove('correct', 'wrong');
                }
            });
        }

        function applyState(data) {
            setState({
                restartAllowed: data.restartAllowed ?? state.restartAllowed,
                isReadonly: !!data.readonly,
            });

            displayEl.textContent = data.display;
            categoryEl.textContent = data.category.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase());
            clueEl.textContent = data.clue;
            triesRemainingEl.textContent = data.maxTries - data.tries;
            renderHpBar(data.maxTries, data.tries);

            progressCountEl.textContent = data.correct.length;
            totalLettersEl.textContent = [...new Set((data.word || '').toLowerCase().match(/[a-z]/g) || [])].length;
            totalGuessesEl.textContent = data.correct.length + data.wrong.length;
            usedWordsCountEl.textContent = data.usedWordsCount ?? data.usedWords?.length ?? 0;
            foundWordsCountEl.textContent = data.foundWordsCount ?? data.foundWords?.length ?? 0;

            renderWordList(usedWordsListEl, data.usedWords);
            renderWordList(foundWordsListEl, data.foundWords);

            wrongLettersEl.textContent = (!data.won && !data.lost && data.wrong.length)
                ? 'Wrong: ' + data.wrong.join(', ').toUpperCase()
                : '';

            if (data.readonly) {
                statusEl.className = 'status-bar readonly';
                statusEl.textContent = 'All challenges completed. Game is now read-only.';
                keyboard.classList.add('hidden');
                updateNextButtonLabel(null);
                setState({ gameEnded: true, isReadonly: true });
            } else if (data.won) {
                statusEl.className = 'status-bar win';
                statusEl.innerHTML = `VICTORY! The word is: ${data.word}`;
                keyboard.classList.add('hidden');
                updateNextButtonLabel('Proceed to Next Word');
                setState({ gameEnded: true, isReadonly: false });
            } else if (data.lost) {
                statusEl.className = 'status-bar lose';
                statusEl.innerHTML = `GAME OVER! The word was: ${data.word}`;
                keyboard.classList.add('hidden');
                updateNextButtonLabel('Try Again');
                setState({ gameEnded: true, isReadonly: false });
            } else {
                statusEl.className = 'status-bar';
                statusEl.textContent = 'Stay sharp — wrong letters cost HP.';
                keyboard.classList.remove('hidden');
                updateNextButtonLabel(null); // hide label reset
                setState({ gameEnded: false, isReadonly: false });
            }

            updateKeyboardState(document.querySelectorAll('.key'), data);
            syncRestartButton();
        }

        // Opponent stream (SSE)
        function applyOpponent(data) {
            if (!opponentBoardEl || !data) return;
            opponentBoardEl.textContent = data.display || '----';
            const meta = [];
            if (data.tries !== undefined && data.maxTries !== undefined) meta.push(`HP: ${data.maxTries - data.tries}/${data.maxTries}`);
            if (data.foundWordsCount !== undefined) meta.push(`Found: ${data.foundWordsCount}`);
            if (data.usedWordsCount !== undefined) meta.push(`Used: ${data.usedWordsCount}`);
            opponentMetaEl.textContent = meta.join(' · ') || 'Waiting...';

            opponentBoardEl.classList.toggle('win', !!data.won);
            opponentBoardEl.classList.toggle('lose', !!data.lost);
        }

        function startOpponentStream() {
            if (!streamUrl || !window.EventSource) return;
            const es = new EventSource(streamUrl);
            es.addEventListener('progress', (event) => {
                try {
                    const data = JSON.parse(event.data);
                    applyOpponent(data);
                } catch (_) {}
            });
            es.onerror = () => {
                es.close();
                setTimeout(startOpponentStream, 2000); // retry
            };
        }

        function updateNextButtonLabel(label) {
            if (!nextBtn || !nextForm) return;
            if (label) {
                nextBtn.textContent = label;
                nextForm.classList.remove('hidden');
            } else {
                nextForm.classList.add('hidden');
            }
        }

        function syncRestartButton() {
            if (!restartBtn) return;
            if (state.restartAllowed) {
                restartBtn.removeAttribute('disabled');
                restartBtn.removeAttribute('aria-disabled');
                restartBtn.title = '';
            } else {
                restartBtn.setAttribute('disabled', 'disabled');
                restartBtn.setAttribute('aria-disabled', 'true');
                restartBtn.title = 'Restart disabled after guesses begin';
            }
        }

        const jsonHeaders = () => ({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        });

        async function requestGame(params) {
            const body = params.toString();
            const headers = jsonHeaders();
            let response;
            try {
                response = await fetch(updateUrl, { method: 'PUT', headers, body });
            } catch (e) {
                response = null;
            }
            if (!response || response.status === 405 || response.status === 501) {
                response = await fetch(updateUrl, { method: 'POST', headers, body: `${body}&_method=PUT` });
            }
            if (!response || !response.ok) return null;
            return response.json();
        }

        // Events
        if (resetProgressBtn) {
            resetProgressBtn.addEventListener('click', async () => {
                if (state.isReadonly) return;
                if (!confirm('Reset all progress and start over with a new word?')) return;
                const data = await requestGame(new URLSearchParams({ reset_progress: '1' }));
                if (data) applyState(data);
            });
        }

        if (restartBtn) {
            restartBtn.addEventListener('click', async () => {
                if (!state.restartAllowed || state.isReadonly) return;
                const data = await requestGame(new URLSearchParams({ restart: '1' }));
                if (data) applyState(data);
            });
        }

        if (nextBtn && nextForm) {
            nextForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await triggerNext();
            });
        }

        async function triggerNext() {
            let response;
            try {
                response = await fetch(nextUrl, { method: 'POST', headers: jsonHeaders(), body: '' });
            } catch (err) {
                response = null;
            }
            if (!response || !response.ok) {
                // fallback: normal form submit
                nextForm?.submit();
                return;
            }
            const data = await response.json();
            applyState(data);
        }

        if (keyboard) {
            keyboard.addEventListener('click', async (event) => {
                if (!event.target.classList.contains('key') || event.target.disabled) return;
                if (statusEl.classList.contains('readonly') || state.isReadonly) return;
                const letter = event.target.dataset.letter;
                if (!letter) return;
                const data = await requestGame(new URLSearchParams({ letter }));
                if (data) applyState(data);
            });
        }

        document.addEventListener('keydown', async (event) => {
            const key = event.key.toLowerCase();

            // Enter: after win/loss, start next round
            if (key === 'enter') {
                if (state.gameEnded && !state.isReadonly) {
                    event.preventDefault();
                    await triggerNext();
                }
                return;
            }

            // Only handle letter keys during active play
            if (!keyboard || keyboard.classList.contains('hidden') || state.isReadonly) return;
            if (key.length !== 1 || !/[a-z]/.test(key)) return;
            const btn = document.querySelector(`.key[data-letter=\"${key}\"]`);
            if (!btn || btn.disabled) return;
            event.preventDefault();
            const data = await requestGame(new URLSearchParams({ letter: key }));
            if (data) applyState(data);
        });

        const openModal = () => {
            modal?.classList.add('open');
            modal?.classList.remove('hidden');
        };
        const closeModal = () => {
            modal?.classList.remove('open');
            modal?.classList.add('hidden');
        };

        if (showModalBtn && modal) showModalBtn.addEventListener('click', openModal);
        if (closeModalBtn && modal) closeModalBtn.addEventListener('click', closeModal);
        if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        // initial keyboard state
        updateKeyboardState(document.querySelectorAll('.key'), {
            word: @json($word),
            correct: @json($correct),
            wrong: @json($wrong),
        });
        syncRestartButton();
        updateNextButtonLabel(state.nextLabel);

        if (matchCode) {
            startOpponentStream();
        }
    </script>
    @endpush
</x-app>
