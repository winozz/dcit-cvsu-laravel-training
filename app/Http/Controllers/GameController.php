<?php

namespace App\Http\Controllers;

use App\Constants\ChallengeGameConstants;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function show(Request $request)
    {
        if ($request->boolean('restart')) {
            session()->forget(ChallengeGameConstants::SESSION_KEYS);
            $this->startNewGame();
        }

        // Start new game
        if (!session(ChallengeGameConstants::SESSION_WORD)) {
            $this->startNewGame();
        }

        // Process guess
        if (!$request->boolean('restart') && $request->filled('letter')) {
            $this->processGuess($request->input('letter'));
        }

        $gameData = $this->buildGameData();

        if ($gameData['won'] || $gameData['lost']) {
            session()->forget(ChallengeGameConstants::SESSION_KEYS);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($gameData);
        }

        return view('game.show', $gameData);
    }

    private function startNewGame(): void
    {
        $category = array_rand(ChallengeGameConstants::CATEGORIES);
        session([
            ChallengeGameConstants::SESSION_WORD => ChallengeGameConstants::CATEGORIES[$category][array_rand(ChallengeGameConstants::CATEGORIES[$category])],
            ChallengeGameConstants::SESSION_CATEGORY => $category,
            ChallengeGameConstants::SESSION_CORRECT => [],
            ChallengeGameConstants::SESSION_WRONG => [],
        ]);
    }

    private function processGuess(string $letter): void
    {
        $letter = strtolower(substr(trim($letter), 0, 1));
        if ($letter === '' || !ctype_alpha($letter)) {
            return;
        }

        $correct = session(ChallengeGameConstants::SESSION_CORRECT, []);
        $wrong = session(ChallengeGameConstants::SESSION_WRONG, []);

        if (!in_array($letter, $correct) && !in_array($letter, $wrong)) {
            stripos(session(ChallengeGameConstants::SESSION_WORD), $letter) !== false
                ? $correct[] = $letter
                : $wrong[] = $letter;
            session([
                ChallengeGameConstants::SESSION_CORRECT => $correct,
                ChallengeGameConstants::SESSION_WRONG => $wrong,
            ]);
        }
    }

    private function buildGameData(): array
    {
        $word = session(ChallengeGameConstants::SESSION_WORD);
        $category = session(ChallengeGameConstants::SESSION_CATEGORY);
        $correct = session(ChallengeGameConstants::SESSION_CORRECT, []);
        $wrong = session(ChallengeGameConstants::SESSION_WRONG, []);
        $tries = count($wrong);
        $clue = ChallengeGameConstants::CLUES[$category][$word] ?? 'No clue available.';
        $display = implode(' ', array_map(
            // Symbols (e.g. +, #) are auto-revealed and should not require guessing.
            fn($char) => ctype_alpha($char) ? (in_array(strtolower($char), $correct) ? $char : '_') : $char,
            str_split($word)
        ));

        return [
            'word' => $word,
            'category' => $category,
            'clue' => $clue,
            'display' => $display,
            'won' => !str_contains($display, '_'),
            'lost' => $tries >= ChallengeGameConstants::MAX_TRIES,
            'tries' => $tries,
            'correct' => $correct,
            'wrong' => $wrong,
            'maxTries' => ChallengeGameConstants::MAX_TRIES,
        ];
    }
}
