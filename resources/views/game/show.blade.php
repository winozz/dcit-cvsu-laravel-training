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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Press Start 2P", monospace;
            background:
                radial-gradient(circle at 20% 20%, #2a3a7a 0%, transparent 35%),
                radial-gradient(circle at 80% 15%, #24536f 0%, transparent 30%),
                linear-gradient(180deg, var(--bg-2), var(--bg-1));
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .game-wrap {
            width: min(860px, 100%);
            background: var(--panel);
            border: 4px solid #ffffff;
            box-shadow: 0 0 0 4px #000000, 0 18px 0 #000000;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .game-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: repeating-linear-gradient(
                to bottom,
                rgba(255, 255, 255, 0.05) 0,
                rgba(255, 255, 255, 0.05) 2px,
                transparent 2px,
                transparent 6px
            );
            opacity: 0.25;
        }

        .title {
            margin: 0 0 18px;
            font-size: clamp(14px, 2.4vw, 24px);
            line-height: 1.5;
            color: var(--warn);
            text-shadow: 2px 2px 0 #000;
        }

        .hud {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 16px;
            font-size: 11px;
            line-height: 1.8;
        }

        .hud strong {
            color: var(--blue);
        }

        .tries-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hp-bar {
            display: inline-flex;
            gap: 6px;
            padding: 6px;
            border: 2px solid #fff;
            background: #0f1735;
            box-shadow: inset 0 0 0 2px #000;
        }

        .hp-cell {
            width: 14px;
            height: 14px;
            background: var(--accent);
            box-shadow: 2px 2px 0 #000;
        }

        .hp-cell.lost {
            background: #4a5578;
        }

        .clue {
            color: #dff8ff;
        }

        #display {
            margin: 20px 0;
            padding: 16px;
            border: 3px solid #fff;
            background: #0f1735;
            font-size: clamp(18px, 3vw, 32px);
            letter-spacing: 8px;
            text-align: center;
            min-height: 78px;
            display: grid;
            place-items: center;
            box-shadow: inset 0 0 0 3px #000;
        }

        .status {
            min-height: 38px;
            margin-bottom: 8px;
            font-size: 11px;
            line-height: 1.9;
        }

        .status.win {
            color: var(--accent);
        }

        .status.lose {
            color: var(--danger);
        }

        #wrong-letters {
            color: var(--danger);
            min-height: 20px;
            font-size: 11px;
            line-height: 1.8;
        }

        .btn {
            height: 52px;
            border: 3px solid #fff;
            background: #2a6df5;
            color: #fff;
            font-family: "Press Start 2P", monospace;
            font-size: 11px;
            padding: 0 18px;
            text-decoration: none;
            display: inline-grid;
            place-items: center;
            box-shadow: 0 4px 0 #000;
            cursor: pointer;
        }

        .btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0 #000;
        }

        .btn.secondary {
            background: #44507f;
        }

        .btn.green {
            background: #1f9d55;
        }

        .btn.warn {
            background: #b86b00;
        }

        .keyboard {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        .keyboard-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .key {
            width: 42px;
            height: 42px;
            border: 3px solid #fff;
            background: #2a6df5;
            color: #fff;
            font-family: "Press Start 2P", monospace;
            font-size: 12px;
            display: grid;
            place-items: center;
            cursor: pointer;
            box-shadow: 0 3px 0 #000;
            user-select: none;
        }

        .key:hover:not(:disabled) {
            background: #4a8dff;
        }

        .key:active:not(:disabled) {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #000;
        }

        .key:disabled {
            background: #3a455a;
            border-color: #5a6580;
            color: #7a8095;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .key.correct {
            background: #1f9d55;
            border-color: #fff;
        }

        .key.wrong {
            background: #8b2e3a;
            border-color: #fff;
        }

        .key.key-special {
            background: #5a4a7f;
            width: 52px;
        }

        .key.key-special:hover:not(:disabled) {
            background: #7a6a9f;
        }

        .key.key-special.auto-revealed {
            background: #6a8a4f;
            border-color: #fff;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-item strong {
            color: var(--blue);
        }

        .links {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .game-wrap {
                padding: 16px;
            }

            .title {
                line-height: 1.7;
            }

            .key {
                width: 36px;
                height: 36px;
                font-size: 10px;
            }

            .key.key-special {
                width: 44px;
            }

            .btn {
                height: 46px;
                font-size: 10px;
                padding: 0 12px;
            }
        }
    </style>
</head>
<body>
    <main class="game-wrap">
        <h2 class="title">WORD QUEST</h2>

        @php
            $correctCount = count(session('correct', []));
            $wrongCount = count($wrong);
            $uniqueLetters = count(array_unique(array_filter(str_split(strtolower($word)), function($c) { return ctype_alpha($c); })));
            $keyboardRows = [
                ['q','w','e','r','t','y','u','i','o','p'],
                ['a','s','d','f','g','h','j','k','l'],
                ['z','x','c','v','b','n','m'],
            ];
            $specialKeys = ['+','#'];
        @endphp
        <section class="hud">
            <div><strong>Category:</strong> <span id="category">{{ ucfirst(str_replace('_', ' ', $category)) }}</span></div>
            <div class="clue"><strong>Clue:</strong> <span id="clue">{{ $clue }}</span></div>
            <div class="tries-wrap">
                <strong>HP:</strong>
                <div id="hp-bar" class="hp-bar" aria-label="Tries health bar">
                    @for($i = 0; $i < $maxTries; $i++)
                        <span class="hp-cell @if($i >= ($maxTries - $tries)) lost @endif"></span>
                    @endfor
                </div>
                <span id="tries-remaining">{{ $maxTries - $tries }}</span>
            </div>
            <div class="stats-row">
                <div class="stat-item"><strong>Progress:</strong> <span id="progress-count" class="accent">{{ $correctCount }}</span>/<span id="total-letters">{{ $uniqueLetters }}</span></div>
                <div class="stat-item"><strong>Guesses:</strong> <span id="total-guesses" class="blue">{{ $correctCount + $wrongCount }}</span></div>
            </div>
        </section>

        <div id="display">{{ $display }}</div>

        <div id="status" class="status">
            @if($won)
                <span class="status win">VICTORY! The word is: {{ $word }}<br>Stats: {{ $correctCount + $wrongCount }} guesses to find {{ $uniqueLetters }} letters</span>
            @elseif($lost)
                <span class="status lose">GAME OVER! The word was: {{ $word }}<br>You made {{ $correctCount + $wrongCount }} guesses</span>
            @endif
        </div>

        <p id="wrong-letters">
            @if(!empty($wrong) && !$won && !$lost)
                Wrong: {{ strtoupper(implode(', ', $wrong)) }}
            @endif
        </p>

        <div id="keyboard" class="keyboard" @if($won || $lost) style="display:none;" @endif>
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

        <div class="links">
            <button id="restart-btn" class="btn warn" type="button">Restart</button>
            @if($won)
                <a class="btn green" href="{{ route('guess') }}">Play Again</a>
            @elseif($lost)
                <a class="btn green" href="{{ route('guess') }}">Try Again</a>
            @endif
            <a class="btn secondary" href="{{ route('home') }}">Home</a>
        </div>
    </main>

    <script>
        // DOM Elements
        const keyboard = document.getElementById('keyboard');
        const displayEl = document.getElementById('display');
        const categoryEl = document.getElementById('category');
        const clueEl = document.getElementById('clue');
        const hpBarEl = document.getElementById('hp-bar');
        const triesRemainingEl = document.getElementById('tries-remaining');
        const wrongLettersEl = document.getElementById('wrong-letters');
        const statusEl = document.getElementById('status');
        const restartBtn = document.getElementById('restart-btn');
        const progressCountEl = document.getElementById('progress-count');
        const totalLettersEl = document.getElementById('total-letters');
        const totalGuessesEl = document.getElementById('total-guesses');
        
        // State tracking
        let previousWrongCount = {{ count($wrong) }};
        let previousCorrectCount = {{ count(session('correct', [])) }};
        let previousWon = {{ $won ? 'true' : 'false' }};
        let audioContext;

        // === AUDIO FUNCTIONS ===

        function play8BitAlert() {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            if (!audioContext) {
                audioContext = new AudioCtx();
            }

            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

            const now = audioContext.currentTime;
            const master = audioContext.createGain();
            master.gain.setValueAtTime(0.001, now);
            master.gain.exponentialRampToValueAtTime(0.18, now + 0.01);
            master.gain.exponentialRampToValueAtTime(0.001, now + 0.23);
            master.connect(audioContext.destination);

            const o1 = audioContext.createOscillator();
            o1.type = 'square';
            o1.frequency.setValueAtTime(880, now);
            o1.frequency.linearRampToValueAtTime(520, now + 0.08);
            o1.connect(master);
            o1.start(now);
            o1.stop(now + 0.09);

            const o2 = audioContext.createOscillator();
            o2.type = 'square';
            o2.frequency.setValueAtTime(420, now + 0.1);
            o2.frequency.linearRampToValueAtTime(260, now + 0.2);
            o2.connect(master);
            o2.start(now + 0.1);
            o2.stop(now + 0.22);
        }

        function play8BitVictory() {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            if (!audioContext) {
                audioContext = new AudioCtx();
            }

            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

            const now = audioContext.currentTime;
            const master = audioContext.createGain();
            master.gain.setValueAtTime(0.001, now);
            master.gain.exponentialRampToValueAtTime(0.2, now + 0.01);
            master.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
            master.connect(audioContext.destination);

            const notes = [523.25, 659.25, 783.99, 1046.5];
            notes.forEach((freq, index) => {
                const osc = audioContext.createOscillator();
                osc.type = 'square';
                osc.frequency.setValueAtTime(freq, now + index * 0.12);
                osc.connect(master);
                osc.start(now + index * 0.12);
                osc.stop(now + index * 0.12 + 0.11);
            });
        }

        function play8BitCorrect() {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            if (!audioContext) {
                audioContext = new AudioCtx();
            }

            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

            const now = audioContext.currentTime;
            const master = audioContext.createGain();
            master.gain.setValueAtTime(0.001, now);
            master.gain.exponentialRampToValueAtTime(0.12, now + 0.005);
            master.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
            master.connect(audioContext.destination);

            const osc = audioContext.createOscillator();
            osc.type = 'square';
            osc.frequency.setValueAtTime(880, now);
            osc.connect(master);
            osc.start(now);
            osc.stop(now + 0.11);
        }

        // === UTILITY FUNCTIONS ===

        function getUniqueLetters(word) {
            return [...new Set(word.toLowerCase().split('').filter(c => /[a-z]/.test(c)))];
        }

        function calculateStats(data) {
            const uniqueLetters = getUniqueLetters(data.word).length;
            const totalGuesses = data.correct.length + data.wrong.length;
            return { uniqueLetters, totalGuesses };
        }

        function updateKeyboardState(allKeys, data) {
            allKeys.forEach(key => {
                const letter = key.dataset.letter;
                
                // Handle special characters
                if (letter === '+' || letter === '#') {
                    const isInWord = data.word && data.word.includes(letter);
                    key.disabled = true;
                    key.classList.toggle('auto-revealed', isInWord);
                    key.style.opacity = isInWord ? '1' : '0.3';
                    return;
                }
                
                // Handle letter keys
                if (data.correct && data.correct.includes(letter)) {
                    key.disabled = true;
                    key.classList.add('correct');
                    key.classList.remove('wrong');
                } else if (data.wrong && data.wrong.includes(letter)) {
                    key.disabled = true;
                    key.classList.add('wrong');
                    key.classList.remove('correct');
                } else {
                    key.disabled = false;
                    key.classList.remove('correct', 'wrong');
                }
            });
        }

        // === RENDER FUNCTIONS ===

        function renderHpBar(maxTries, triesUsed) {
            if (!hpBarEl) {
                return;
            }

            const remaining = maxTries - triesUsed;
            let cells = '';
            for (let i = 0; i < maxTries; i++) {
                const lostClass = i >= remaining ? 'lost' : '';
                cells += `<span class="hp-cell ${lostClass}"></span>`;
            }
            hpBarEl.innerHTML = cells;
        }

        function renderEndButtons(type) {
            const links = document.querySelector('.links');
            if (!links) {
                return;
            }

            const actionText = type === 'won' ? 'Play Again' : 'Try Again';
            links.innerHTML = `<button id="restart-btn" class="btn warn" type="button">Restart</button><a class="btn green" href="{{ route('guess') }}">${actionText}</a><a class="btn secondary" href="{{ route('home') }}">Home</a>`;
            bindRestart();
        }

        function updateProgressCounters(data) {
            const stats = calculateStats(data);
            
            if (progressCountEl) {
                progressCountEl.textContent = data.correct.length;
            }
            if (totalLettersEl) {
                totalLettersEl.textContent = stats.uniqueLetters;
            }
            if (totalGuessesEl) {
                totalGuessesEl.textContent = stats.totalGuesses;
            }
        }

        function applyState(data) {
            displayEl.textContent = data.display;
            
            // Update category
            if (data.category && categoryEl) {
                const formattedCategory = data.category.charAt(0).toUpperCase() + 
                    data.category.slice(1).replace(/_/g, ' ');
                categoryEl.textContent = formattedCategory;
            }
            
            clueEl.textContent = data.clue;
            triesRemainingEl.textContent = data.maxTries - data.tries;
            renderHpBar(data.maxTries, data.tries);

            // Update progress counters
            if (data.word && data.correct) {
                updateProgressCounters(data);
            }

            // Play sounds based on state changes

            // Play sounds based on state changes
            if (data.wrong.length > previousWrongCount) {
                play8BitAlert();
            }
            previousWrongCount = data.wrong.length;

            if (data.correct && data.correct.length > previousCorrectCount) {
                play8BitCorrect();
            }
            previousCorrectCount = data.correct ? data.correct.length : 0;

            if (data.won && !previousWon) {
                play8BitVictory();
            }
            previousWon = data.won;

            // Update UI based on game state

            // Update UI based on game state
            if (data.wrong.length > 0 && !data.won && !data.lost) {
                wrongLettersEl.textContent = 'Wrong: ' + data.wrong.join(', ').toUpperCase();
            } else {
                wrongLettersEl.textContent = '';
            }

            if (data.won) {
                const stats = calculateStats(data);
                statusEl.className = 'status win';
                statusEl.innerHTML = `VICTORY! The word is: ${data.word}<br>Stats: ${stats.totalGuesses} guesses to find ${stats.uniqueLetters} letters`;
                renderEndButtons('won');
                keyboard.style.display = 'none';
            } else if (data.lost) {
                const stats = calculateStats(data);
                statusEl.className = 'status lose';
                statusEl.innerHTML = `GAME OVER! The word was: ${data.word}<br>You made ${stats.totalGuesses} guesses`;
                renderEndButtons('lost');
                keyboard.style.display = 'none';
            } else {
                statusEl.className = 'status';
                statusEl.textContent = '';
                keyboard.style.display = 'flex';
            }

            // Update keyboard state
            updateKeyboardState(document.querySelectorAll('.key'), data);
        }

        // === API FUNCTIONS ===

        // === API FUNCTIONS ===

        async function requestGame(params) {
            const response = await fetch(`{{ route('guess') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                return null;
            }

            return response.json();
        }

        // === EVENT HANDLERS ===

        function bindRestart() {
            const restart = document.getElementById('restart-btn');
            if (!restart) {
                return;
            }

            restart.addEventListener('click', async function () {
                const data = await requestGame(new URLSearchParams({ restart: '1' }));
                if (!data) {
                    return;
                }

                applyState(data);
            });
        }

        // Click keyboard handler
        if (keyboard) {
            keyboard.addEventListener('click', async function (event) {
                if (!event.target.classList.contains('key') || event.target.disabled) {
                    return;
                }

                const letter = event.target.dataset.letter;
                if (!letter) {
                    return;
                }

                const data = await requestGame(new URLSearchParams({ letter }));
                if (!data) {
                    return;
                }

                applyState(data);
            });
        }

        bindRestart();

        // === PHYSICAL KEYBOARD SUPPORT ===

        // Keyboard typing support
        document.addEventListener('keydown', async function(event) {
            // Ignore if game is over
            if (!keyboard || keyboard.style.display === 'none') {
                return;
            }

            // Only handle letter keys (a-z)
            const key = event.key.toLowerCase();
            if (key.length !== 1 || !key.match(/[a-z]/)) {
                return;
            }

            // Find the corresponding on-screen key
            const keyButton = document.querySelector(`.key[data-letter="${key}"]`);
            if (!keyButton || keyButton.disabled) {
                return;
            }

            // Prevent default behavior
            event.preventDefault();

            // Make the guess
            const data = await requestGame(new URLSearchParams({ letter: key }));
            if (!data) {
                return;
            }

            applyState(data);
        });

        // === INITIALIZATION ===

        // Initialize keyboard state on page load
        (function initKeyboard() {
            const correct = @json(session('correct', []));
            const wrong = @json($wrong);
            const word = @json($word);
            
            updateKeyboardState(document.querySelectorAll('.key'), {
                word: word,
                correct: correct,
                wrong: wrong
            });
        })();
    </script>
</body>
</html>
