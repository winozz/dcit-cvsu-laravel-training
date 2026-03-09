@props(['category', 'clue', 'maxTries', 'tries', 'correctCount', 'wrongCount', 'uniqueLetters'])
<div class="status-block" style="margin-bottom:16px;background:#232a4a;border-radius:12px;padding:18px 20px;box-shadow:0 4px 0 #000;display:flex;flex-direction:column;gap:14px;max-width:520px;">
    <div style="font-size:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <strong style="color:#ffd166;font-size:15px;">Category:</strong>
        <span id="category" style="color:#62d0ff;font-size:15px;">{{ ucfirst(str_replace('_', ' ', $category)) }}</span>
    </div>
    <div class="clue" style="font-size:14px;">
        <strong style="color:#57f287;font-size:15px;">Clue:</strong>
        <span id="clue" style="color:#ecf4ff;font-size:15px;">{{ $clue }}</span>
    </div>
    <div class="tries-wrap" style="font-size:13px;align-items:center;gap:10px;flex-wrap:wrap;">
        <strong style="color:#62d0ff;font-size:14px;">HP:</strong>
        <div id="hp-bar" class="hp-bar" aria-label="Tries health bar" style="margin-left:4px;">
            @for($i = 0; $i < $maxTries; $i++)
                <span class="hp-cell @if($i >= ($maxTries - $tries)) lost @endif"></span>
            @endfor
        </div>
        <span id="tries-remaining" style="color:#ffd166;font-size:14px;">{{ $maxTries - $tries }}</span>
        <span style="color:#62d0ff;font-size:13px;">Guesses: <span id="total-guesses" class="blue">{{ $correctCount + $wrongCount }}</span></span>
        <span style="color:#57f287;font-size:13px;">Progress: <span id="progress-count" class="accent">{{ $correctCount }}</span>/<span id="total-letters">{{ $uniqueLetters }}</span></span>
    </div>
</div>
