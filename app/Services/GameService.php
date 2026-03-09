<?php

namespace App\Services;

use App\Constants\ChallengeGameConstants;
use App\Utilities\GameLetterUtility;

class GameService
{
    public function handleTurn(bool $resetProgress, bool $restart, ?string $letter): array
    {
        if ($resetProgress) {
            $this->resetProgress();
        }

        if ($restart) {
            $this->restartGame();
        }

        $this->ensureGameStarted();

        if (!$restart && $letter !== null && $letter !== '') {
            $this->processGuess($letter);
        }

        $gameData = $this->buildGameData();
        if ($gameData['won'] || $gameData['lost']) {
            return array_merge($gameData, $this->finalizeRound($gameData['won']));
        }

        return $gameData;
    }

    public function resetProgress(): void
    {
        session()->forget([
            ...ChallengeGameConstants::SESSION_KEYS,
            ChallengeGameConstants::SESSION_USED_WORDS,
            ChallengeGameConstants::SESSION_FOUND_WORDS,
        ]);

        $this->startNewGame();
    }

    public function restartGame(): void
    {
        $this->clearGame();
        $this->startNewGame();
    }

    public function ensureGameStarted(): void
    {
        if (!session(ChallengeGameConstants::SESSION_WORD)) {
            $this->startNewGame();
        }
    }

    public function clearGame(): void
    {
        session()->forget(ChallengeGameConstants::SESSION_KEYS);
    }

    public function finalizeRound(bool $won): array
    {
        $word = (string) session(ChallengeGameConstants::SESSION_WORD, '');
        if ($won && $word !== '') {
            $this->appendUniqueSessionWord(ChallengeGameConstants::SESSION_FOUND_WORDS, $word);
        }

        $usedWords = session(ChallengeGameConstants::SESSION_USED_WORDS, []);
        $foundWords = session(ChallengeGameConstants::SESSION_FOUND_WORDS, []);

        $this->clearGame();

        return [
            'usedWords' => $usedWords,
            'foundWords' => $foundWords,
            'usedWordsCount' => count($usedWords),
            'foundWordsCount' => count($foundWords),
        ];
    }

    public function processGuess(?string $letter): void
    {
        $normalized = GameLetterUtility::normalizeGuess($letter);
        if ($normalized === null) {
            return;
        }

        $correct = session(ChallengeGameConstants::SESSION_CORRECT, []);
        $wrong = session(ChallengeGameConstants::SESSION_WRONG, []);

        if (!GameLetterUtility::isNewGuess($normalized, $correct, $wrong)) {
            return;
        }

        if (stripos((string) session(ChallengeGameConstants::SESSION_WORD), $normalized) !== false) {
            $correct[] = $normalized;
        } else {
            $wrong[] = $normalized;
        }

        session([
            ChallengeGameConstants::SESSION_CORRECT => $correct,
            ChallengeGameConstants::SESSION_WRONG => $wrong,
        ]);
    }

    public function buildGameData(): array
    {
        $word = (string) session(ChallengeGameConstants::SESSION_WORD);
        $category = (string) session(ChallengeGameConstants::SESSION_CATEGORY);
        $correct = session(ChallengeGameConstants::SESSION_CORRECT, []);
        $wrong = session(ChallengeGameConstants::SESSION_WRONG, []);
        $usedWords = session(ChallengeGameConstants::SESSION_USED_WORDS, []);
        $foundWords = session(ChallengeGameConstants::SESSION_FOUND_WORDS, []);
        $tries = count($wrong);
        $clue = ChallengeGameConstants::CLUES[$category][$word] ?? 'No clue available.';
        $display = GameLetterUtility::buildDisplay($word, $correct);

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
            'usedWords' => $usedWords,
            'foundWords' => $foundWords,
            'usedWordsCount' => count($usedWords),
            'foundWordsCount' => count($foundWords),
        ];
    }

    private function startNewGame(): void
    {
        $excludedWords = array_values(array_unique(array_merge(
            session(ChallengeGameConstants::SESSION_USED_WORDS, []),
            session(ChallengeGameConstants::SESSION_FOUND_WORDS, [])
        )));

        $availableByCategory = [];
        foreach (ChallengeGameConstants::CATEGORIES as $category => $words) {
            $remaining = array_values(array_filter(
                $words,
                static fn(string $word): bool => !in_array($word, $excludedWords, true)
            ));

            if ($remaining !== []) {
                $availableByCategory[$category] = $remaining;
            }
        }

        // If all words were consumed, reset history and start a fresh cycle.
        if ($availableByCategory === []) {
            session([
                ChallengeGameConstants::SESSION_USED_WORDS => [],
                ChallengeGameConstants::SESSION_FOUND_WORDS => [],
            ]);
            $availableByCategory = ChallengeGameConstants::CATEGORIES;
        }

        $category = array_rand($availableByCategory);
        $word = $availableByCategory[$category][array_rand($availableByCategory[$category])];

        session([
            ChallengeGameConstants::SESSION_WORD => $word,
            ChallengeGameConstants::SESSION_CATEGORY => $category,
            ChallengeGameConstants::SESSION_CORRECT => [],
            ChallengeGameConstants::SESSION_WRONG => [],
        ]);

        $this->appendUniqueSessionWord(ChallengeGameConstants::SESSION_USED_WORDS, $word);
    }

    private function appendUniqueSessionWord(string $key, string $word): void
    {
        $words = session($key, []);
        if (!in_array($word, $words, true)) {
            $words[] = $word;
            session([$key => $words]);
        }
    }
}
