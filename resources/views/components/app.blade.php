@props([
    'category', 'clue', 'maxTries', 'tries', 'display', 'wrong', 'won', 'lost', 'keyboardRows', 'specialKeys',
    'usedWordsCount', 'foundWordsCount', 'skipCount', 'usedWords', 'foundWords', 'skippedWords', 'skippedWordsCount',
    'correctCount', 'wrongCount', 'uniqueLetters', 'word', 'routeGuess', 'routeGuessPut', 'routeHome', 'csrfToken'
])
<!DOCTYPE html>
<html>
<head>
    <title>Guess the Word</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0b1020;
            --bg-2: #131a34;
            --panel: #1a2142;
            --ink: #ecf4ff;
            --accent: #57f287;
            --danger: #ff5f6d;
            --warn: #ffd166;
            --blue: #62d0ff;
        }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; color:var(--ink); font-family:"Press Start 2P", monospace;
            background: radial-gradient(circle at 20% 20%, #2a3a7a 0%, transparent 35%),
                        radial-gradient(circle at 80% 15%, #24536f 0%, transparent 30%),
                        linear-gradient(180deg, var(--bg-2), var(--bg-1));
            display:grid; place-items:center; padding:24px; }
        .game-wrap { width:min(960px,100%); background:var(--panel); border:4px solid #fff;
            box-shadow:0 0 0 4px #000, 0 18px 0 #000; padding:22px; position:relative; overflow:hidden; display:grid; gap:20px; }
        .game-wrap::after { content:""; position:absolute; inset:0; pointer-events:none;
            background:repeating-linear-gradient(to bottom, rgba(255,255,255,0.05) 0, rgba(255,255,255,0.05) 2px, transparent 2px, transparent 6px); opacity:0.25; }
        .title { margin:0 0 18px; font-size:clamp(14px,2.4vw,24px); line-height:1.5; color:var(--warn); text-shadow:2px 2px 0 #000; }
        .stats-row { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .tries-wrap { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .hp-bar { display:inline-flex; gap:6px; padding:6px; border:2px solid #fff; background:#0f1735; box-shadow:inset 0 0 0 2px #000; }
        .hp-cell { width:14px; height:14px; background:var(--accent); box-shadow:2px 2px 0 #000; }
        .hp-cell.lost { background:#4a5578; }
        #display { margin:16px 0; padding:16px; border:3px solid #fff; background:#0f1735; font-size:clamp(18px,3vw,32px); letter-spacing:8px;
            text-align:center; min-height:78px; display:grid; place-items:center; box-shadow:inset 0 0 0 3px #000; }
        .status { min-height:38px; margin-bottom:8px; font-size:11px; line-height:1.9; }
        .status.win { color:var(--accent); } .status.lose { color:var(--danger); }
        #wrong-letters { color:var(--danger); min-height:20px; font-size:11px; line-height:1.8; }
        .btn { height:46px; border:3px solid #fff; background:#2a6df5; color:#fff; font-family:"Press Start 2P", monospace; font-size:11px; padding:0 14px;
            text-decoration:none; display:inline-grid; place-items:center; box-shadow:0 4px 0 #000; cursor:pointer; }
        .btn:active { transform:translateY(2px); box-shadow:0 2px 0 #000; }
        .btn.secondary { background:#44507f; } .btn.green { background:#1f9d55; } .btn.warn { background:#b86b00; }
        .keyboard { margin-top:12px; display:flex; flex-direction:column; gap:8px; align-items:center; }
        .keyboard-row { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; }
        .key { width:42px; height:42px; border:3px solid #fff; background:#2a6df5; color:#fff; font-family:"Press Start 2P", monospace; font-size:12px;
            display:grid; place-items:center; cursor:pointer; box-shadow:0 3px 0 #000; user-select:none; }
        .key:hover:not(:disabled) { background:#4a8dff; }
        .key:active:not(:disabled) { transform:translateY(2px); box-shadow:0 1px 0 #000; }
        .key:disabled { background:#3a455a; border-color:#5a6580; color:#7a8095; cursor:not-allowed; opacity:0.6; }
        .key.correct { background:#1f9d55; border-color:#fff; }
        .key.wrong { background:#8b2e3a; border-color:#fff; }
        .key.key-special { background:#5a4a7f; width:52px; }
        .key.key-special:hover:not(:disabled) { background:#7a6a9f; }
        .key.key-special.auto-revealed { background:#6a8a4f; border-color:#fff; }
        .links { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; }
        .list-block { background:#0f1735; border:3px solid #fff; padding:12px; box-shadow:inset 0 0 0 3px #000; }
        @media (max-width:640px){ .game-wrap{padding:16px;} .key{width:36px;height:36px;font-size:10px;} .key.key-special{width:44px;} .btn{height:42px;font-size:10px;padding:0 12px;} }
    </style>
</head>
<body>
    <main class="game-wrap">
        <section>
            <h2 class="title">WORD QUEST</h2>
            <div class="stats-row" style="gap:16px;">
                <div class="stat-item"><strong>Used:</strong> <span id="used-words-count" class="blue">{{ $usedWordsCount }}</span></div>
                <div class="stat-item"><strong>Found:</strong> <span id="found-words-count" class="accent">{{ $foundWordsCount }}</span></div>
                <div class="stat-item"><strong>Skipped:</strong> <span id="skipped-words-count" class="warn">{{ $skippedWordsCount }}</span></div>
                <div class="stat-item"><strong>Skips:</strong> <span id="skip-count" class="warn">{{ $skipCount }}</span></div>
            </div>
            <div class="stats-row" style="gap:10px; margin-bottom:8px;">
                <button id="show-modal-btn" class="btn secondary" type="button" style="height:32px;font-size:10px;padding:0 10px;">Word Lists</button>
                <button id="show-skipped-modal-btn" class="btn secondary" type="button" style="height:32px;font-size:10px;padding:0 10px;">Skipped</button>
                <button id="reset-progress-btn" class="btn secondary" type="button" style="height:32px;font-size:10px;padding:0 10px;">Reset</button>
                <button id="skip-btn" class="btn warn" type="button" style="height:32px;font-size:10px;padding:0 10px;">Skip</button>
            </div>

            <x-game-status
                :category="$category"
                :clue="$clue"
                :maxTries="$maxTries"
                :tries="$tries"
                :correctCount="$correctCount"
                :wrongCount="$wrongCount"
                :uniqueLetters="$uniqueLetters"
            />

            <x-game-main
                :display="$display"
                :wrong="$wrong"
                :won="$won"
                :lost="$lost"
                :keyboardRows="$keyboardRows"
                :specialKeys="$specialKeys"
                :routeGuess="$routeGuess"
                :routeHome="$routeHome"
                :word="$word"
                :correctCount="$correctCount"
                :wrongCount="$wrongCount"
                :uniqueLetters="$uniqueLetters"
            />

            <div class="list-block">
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <div>
                        <strong style="color:var(--blue);">Used Words</strong>
                        <ul id="used-words-list" style="margin:6px 0 0;padding-left:18px;max-height:120px;overflow:auto;">
                            @forelse($usedWords as $uw)
                                <li>{{ $uw }}</li>
                            @empty
                                <li><em>None</em></li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <strong style="color:var(--accent);">Found Words</strong>
                        <ul id="found-words-list" style="margin:6px 0 0;padding-left:18px;max-height:120px;overflow:auto;">
                            @forelse($foundWords as $fw)
                                <li>{{ $fw }}</li>
                            @empty
                                <li><em>None</em></li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <strong style="color:var(--warn);">Skipped Words</strong>
                        <ul id="skipped-words-list" style="margin:6px 0 0;padding-left:18px;max-height:120px;overflow:auto;">
                            @forelse($skippedWords as $sw)
                                <li>{{ $sw }}</li>
                            @empty
                                <li><em>None</em></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{ $slot }}
        </section>
    </main>

    <script>
        const csrfToken = '{{ $csrfToken }}';
        const routeGuess = '{{ $routeGuess }}';
        const routeGuessPut = '{{ $routeGuessPut }}';

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
        const skippedWordsCountEl = document.getElementById('skipped-words-count');
        const skipCountEl = document.getElementById('skip-count');
        const usedWordsListEl = document.getElementById('used-words-list');
        const foundWordsListEl = document.getElementById('found-words-list');
        const skippedWordsListEl = document.getElementById('skipped-words-list');
        const statusEl = document.getElementById('status');
        const wrongLettersEl = document.getElementById('wrong-letters');
        const keyboard = document.getElementById('keyboard');
        const showModalBtn = document.getElementById('show-modal-btn');
        const showSkippedModalBtn = document.getElementById('show-skipped-modal-btn');
        const resetProgressBtn = document.getElementById('reset-progress-btn');
        const skipBtn = document.getElementById('skip-btn');

        // Modal setup
        const wordModal = document.createElement('div');
        wordModal.id = 'word-modal';
        wordModal.className = 'modal';
        wordModal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(20,20,40,0.85);z-index:1000;align-items:center;justify-content:center;';
        wordModal.innerHTML = `
            <div class="modal-content" style="background:#1a2142;border:4px solid #fff;box-shadow:0 0 0 4px #000;padding:24px;max-width:420px;width:90vw;color:#ecf4ff;font-family:'Press Start 2P',monospace;">
                <h3 style="color:#ffd166;text-shadow:2px 2px 0 #000;margin-top:0;">Word Lists</h3>
                <div style="margin-bottom:18px;">
                    <strong style="color:#62d0ff;">Used Words:</strong>
                    <ul id="modal-used" style="margin:8px 0 0 0;padding-left:18px;max-height:120px;overflow:auto;"></ul>
                </div>
                <div style="margin-bottom:18px;">
                    <strong style="color:#57f287;">Found Words:</strong>
                    <ul id="modal-found" style="margin:8px 0 0 0;padding-left:18px;max-height:120px;overflow:auto;"></ul>
                </div>
                <button id="close-modal-btn" class="btn warn" type="button" style="height:32px;font-size:10px;padding:0 8px;">Close</button>
            </div>`;
        document.body.appendChild(wordModal);

        const skippedModal = document.createElement('div');
        skippedModal.id = 'skipped-modal';
        skippedModal.className = 'modal';
        skippedModal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(20,20,40,0.85);z-index:1000;align-items:center;justify-content:center;';
        skippedModal.innerHTML = `
            <div class="modal-content" style="background:#1a2142;border:4px solid #fff;box-shadow:0 0 0 4px #000;padding:24px;max-width:420px;width:90vw;color:#ecf4ff;font-family:'Press Start 2P',monospace;">
                <h3 style="color:#ffd166;text-shadow:2px 2px 0 #000;margin-top:0;">Skipped Words</h3>
                <ul id="modal-skipped" style="margin:8px 0 0 0;padding-left:18px;max-height:160px;overflow:auto;"></ul>
                <button id="close-skipped-modal-btn" class="btn warn" type="button" style="height:32px;font-size:10px;padding:0 8px;">Close</button>
            </div>`;
        document.body.appendChild(skippedModal);

        if (showModalBtn) showModalBtn.addEventListener('click', () => wordModal.style.display = 'flex');
        document.getElementById('close-modal-btn').addEventListener('click', () => wordModal.style.display = 'none');
        wordModal.addEventListener('click', e => { if (e.target === wordModal) wordModal.style.display = 'none'; });

        if (showSkippedModalBtn) showSkippedModalBtn.addEventListener('click', () => skippedModal.style.display = 'flex');
        document.getElementById('close-skipped-modal-btn').addEventListener('click', () => skippedModal.style.display = 'none');
        skippedModal.addEventListener('click', e => { if (e.target === skippedModal) skippedModal.style.display = 'none'; });

        let previousWrongCount = {{ count($wrong) }};
        let previousCorrectCount = {{ count(session('correct', [])) }};
        let previousWon = {{ $won ? 'true' : 'false' }};
        let audioContext;

        function play8BitAlert() { /* sound functions unchanged */ }
        function play8BitVictory() { /* ... */ }
        function play8BitCorrect() { /* ... */ }

        function getUniqueLetters(word) { return [...new Set(word.toLowerCase().split('').filter(c => /[a-z]/.test(c)))]; }
        function calculateStats(data) { const uniqueLetters = getUniqueLetters(data.word || '').length; const totalGuesses = (data.correct?.length || 0) + (data.wrong?.length || 0); return { uniqueLetters, totalGuesses }; }

        function updateKeyboardState(allKeys, data) {
            allKeys.forEach(key => {
                const letter = key.dataset.letter;
                if (letter === '+' || letter === '#') {
                    const isInWord = data.word && data.word.includes(letter);
                    key.disabled = true;
                    key.classList.toggle('auto-revealed', isInWord);
                    key.style.opacity = isInWord ? '1' : '0.3';
                    return;
                }
                if (data.correct?.includes(letter)) { key.disabled = true; key.classList.add('correct'); key.classList.remove('wrong'); }
                else if (data.wrong?.includes(letter)) { key.disabled = true; key.classList.add('wrong'); key.classList.remove('correct'); }
                else { key.disabled = false; key.classList.remove('correct','wrong'); }
            });
        }

        function renderHpBar(maxTries, triesUsed) {
            if (!hpBarEl) return;
            const remaining = maxTries - triesUsed;
            let cells = '';
            for (let i = 0; i < maxTries; i++) {
                const lostClass = i >= remaining ? 'lost' : '';
                cells += `<span class="hp-cell ${lostClass}"></span>`;
            }
            hpBarEl.innerHTML = cells;
        }

        function renderWordList(listEl, words) {
            if (!listEl) return;
            const safeWords = Array.isArray(words) ? words : [];
            listEl.innerHTML = safeWords.length ? safeWords.map(word => `<li>${word}</li>`).join('') : '<li><em>None</em></li>';
        }

        function applyState(data) {
            displayEl.textContent = data.display;
            if (data.category && categoryEl) {
                const formattedCategory = data.category.charAt(0).toUpperCase() + data.category.slice(1).replace(/_/g, ' ');
                categoryEl.textContent = formattedCategory;
            }
            clueEl.textContent = data.clue;
            triesRemainingEl.textContent = data.maxTries - data.tries;
            renderHpBar(data.maxTries, data.tries);

            if (data.word && data.correct) {
                const stats = calculateStats(data);
                progressCountEl.textContent = data.correct.length;
                totalLettersEl.textContent = stats.uniqueLetters;
                totalGuessesEl.textContent = stats.totalGuesses;
            }

            if (typeof data.usedWordsCount === 'number') usedWordsCountEl.textContent = data.usedWordsCount;
            if (typeof data.foundWordsCount === 'number') foundWordsCountEl.textContent = data.foundWordsCount;
            if (typeof data.skippedWordsCount === 'number') skippedWordsCountEl.textContent = data.skippedWordsCount;
            if (typeof data.skipCount === 'number') skipCountEl.textContent = data.skipCount;

            renderWordList(usedWordsListEl, data.usedWords);
            renderWordList(foundWordsListEl, data.foundWords);
            renderWordList(skippedWordsListEl, data.skippedWords);
            renderWordList(document.getElementById('modal-used'), data.usedWords);
            renderWordList(document.getElementById('modal-found'), data.foundWords);
            renderWordList(document.getElementById('modal-skipped'), data.skippedWords);

            if (data.wrong.length > previousWrongCount) { play8BitAlert(); }
            previousWrongCount = data.wrong.length;

            if (data.correct && data.correct.length > previousCorrectCount) { play8BitCorrect(); }
            previousCorrectCount = data.correct ? data.correct.length : 0;

            if (data.won && !previousWon) { play8BitVictory(); }
            previousWon = data.won;

            if (data.wrong.length > 0 && !data.won && !data.lost) {
                wrongLettersEl.textContent = 'Wrong: ' + data.wrong.join(', ').toUpperCase();
            } else {
                wrongLettersEl.textContent = '';
            }

            const stats = calculateStats(data);
            if (data.won) {
                statusEl.className = 'status win';
                statusEl.innerHTML = `VICTORY! The word is: ${data.word}<br>Stats: ${stats.totalGuesses} guesses to find ${stats.uniqueLetters} letters`;
                keyboard.style.display = 'none';
            } else if (data.lost) {
                statusEl.className = 'status lose';
                statusEl.innerHTML = `GAME OVER! The word was: ${data.word}<br>You made ${stats.totalGuesses} guesses`;
                keyboard.style.display = 'none';
            } else {
                statusEl.className = 'status';
                statusEl.textContent = '';
                keyboard.style.display = 'flex';
            }

            updateKeyboardState(document.querySelectorAll('.key'), data);
        }

        async function requestGame(params) {
            const requestBody = params.toString();
            const commonHeaders = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            };

            let response;
            try { response = await fetch(routeGuessPut, { method: 'PUT', headers: commonHeaders, body: requestBody }); }
            catch { response = null; }

            if (!response || response.status === 405 || response.status === 501) {
                response = await fetch(routeGuessPut, { method: 'POST', headers: commonHeaders, body: `${requestBody}&_method=PUT` });
            }
            if (!response || !response.ok) return null;
            return response.json();
        }

        if (resetProgressBtn) {
            resetProgressBtn.addEventListener('click', async () => {
                if (!window.confirm('Reset all progress and start over with a new word?')) return;
                const data = await requestGame(new URLSearchParams({ reset_progress: '1' }));
                if (data) applyState(data);
            });
        }

        if (skipBtn) {
            skipBtn.addEventListener('click', async () => {
                const data = await requestGame(new URLSearchParams({ skip: '1' }));
                if (data) applyState(data);
            });
        }

        if (keyboard) {
            keyboard.addEventListener('click', async event => {
                if (!event.target.classList.contains('key') || event.target.disabled) return;
                const letter = event.target.dataset.letter;
                if (!letter) return;
                const data = await requestGame(new URLSearchParams({ letter }));
                if (data) applyState(data);
            });
        }

        document.addEventListener('keydown', async event => {
            if (!keyboard || keyboard.style.display === 'none') return;
            const key = event.key.toLowerCase();
            if (key.length !== 1 || !key.match(/[a-z]/)) return;
            const keyButton = document.querySelector(`.key[data-letter="${key}"]`);
            if (!keyButton || keyButton.disabled) return;
            event.preventDefault();
            const data = await requestGame(new URLSearchParams({ letter: key }));
            if (data) applyState(data);
        });

        (function initKeyboard() {
            const correct = @json(session('correct', []));
            const wrong = @json($wrong);
            const word = @json($word);
            updateKeyboardState(document.querySelectorAll('.key'), { word, correct, wrong });
        })();
    </script>
</body>
</html>
