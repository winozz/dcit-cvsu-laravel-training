@props(['display','wrong','won','lost','keyboardRows','specialKeys','routeGuess','routeHome','word','correctCount','wrongCount','uniqueLetters'])
<div class="game-main" style="flex: 1 1 0; min-width: 0;">
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
        @if($won)
            <a class="btn green" href="{{ $routeGuess }}">Proceed to Next Word</a>
        @elseif($lost)
            <a class="btn green" href="{{ $routeGuess }}">Try Again</a>
        @endif
        <a class="btn secondary" href="{{ $routeHome }}">Home</a>
    </div>
</div>
