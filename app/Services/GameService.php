<?php

namespace App\Services;

use App\Constants\ChallengeGameConstants;
use App\Services\Contracts\GameServiceContract;
use App\Services\ChallengeGameItemService;
use App\Utilities\GameLetterUtility;

class GameService implements GameServiceContract
{
    public function __construct(
        private readonly ChallengeGameItemService $items
    ) {}

    public function handleTurn(bool $resetProgress, bool $restart, ?string $letter, string $game): array
    {
        if ($resetProgress) {
            $this->resetProgress($game);
        }

        $hasGuesses = $this->hasGuesses($game);
        $restartAllowed = $restart && !$hasGuesses;
        if ($restartAllowed) {
            $this->restartGame($game);
        }

        $this->ensureGameStarted($game);

        if (!$restartAllowed && $letter) {
            $this->processGuess($letter, $game);
        }

        $gameData = $this->buildGameData($game);
        return ($gameData['won'] || $gameData['lost'])
            ? array_merge($gameData, $this->finalizeRound($gameData['won'], $game))
            : $gameData;
    }

    /**
     * Generate the next challenge (category + word) for a game without mutating state.
     *
     * @return array{category:string,word:string,resetHistory:bool}
     */
    public function generateChallenge(string $game): array
    {
        $excludedWords = array_values(array_unique(array_merge(
            session($this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game), []),
            session($this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game), [])
        )));

        $availableByCategory = $this->items->availableByCategory($excludedWords);

        $resetHistory = false;
        if (!$availableByCategory) {
            // All words consumed; restart the pool if any active items exist.
            $availableByCategory = $this->items->availableByCategory([]);
            $resetHistory = (bool) $availableByCategory;
        }

        if (!$availableByCategory) {
            throw new \RuntimeException('No active challenge items available. Seed the database first.');
        }

        $category = array_rand($availableByCategory);
        $word = $availableByCategory[$category][array_rand($availableByCategory[$category])];

        return [
            'category' => $category,
            'word' => $word,
            'resetHistory' => $resetHistory,
        ];
    }

    public function resetProgress(string $game): void
    {
        session()->forget($this->keyedSessionKeys($game));
        session()->forget([
            $this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game),
            $this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game),
        ]);
        $this->startNewGame($game);
    }

    public function restartGame(string $game): void
    {
        $this->clearGame($game);
        $this->startNewGame($game);
    }

    public function ensureGameStarted(string $game): void
    {
        if (!session($this->key(ChallengeGameConstants::SESSION_WORD, $game))) {
            $this->startNewGame($game);
        }
    }

    public function clearGame(string $game): void
    {
        session()->forget($this->keyedSessionKeys($game));
    }

    public function finalizeRound(bool $won, string $game): array
    {
        $word = (string) session($this->key(ChallengeGameConstants::SESSION_WORD, $game), '');
        if ($won && $word) {
            $this->appendUniqueSessionWord(ChallengeGameConstants::SESSION_FOUND_WORDS, $word, $game);
        }
        $usedWords = session($this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game), []);
        $foundWords = session($this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game), []);
        $this->clearGame($game);
        return [
            'usedWords' => $usedWords,
            'foundWords' => $foundWords,
            'usedWordsCount' => count($usedWords),
            'foundWordsCount' => count($foundWords),
            'restartAllowed' => false,
        ];
    }

    public function processGuess(?string $letter, string $game): void
    {
        $normalized = GameLetterUtility::normalizeGuess($letter);
        if ($normalized === null) return;

        $correct = session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []);
        $wrong = session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []);

        if (!GameLetterUtility::isNewGuess($normalized, $correct, $wrong)) return;

        $word = (string) session($this->key(ChallengeGameConstants::SESSION_WORD, $game));
        if (stripos($word, $normalized) !== false) {
            $correct[] = $normalized;
        } else {
            $wrong[] = $normalized;
        }

        session([
            $this->key(ChallengeGameConstants::SESSION_CORRECT, $game) => $correct,
            $this->key(ChallengeGameConstants::SESSION_WRONG, $game) => $wrong,
        ]);
    }

    public function buildGameData(string $game): array
    {
        $word = (string) session($this->key(ChallengeGameConstants::SESSION_WORD, $game));
        $category = (string) session($this->key(ChallengeGameConstants::SESSION_CATEGORY, $game));
        $correct = session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []);
        $wrong = session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []);
        $usedWords = session($this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game), []);
        $foundWords = session($this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game), []);
        $tries = count($wrong);
        $restartAllowed = !$this->hasGuesses($game);
        $clue = $this->items->clue($category, $word);
        $maxTries = $this->items->maxTries($category, $word);
        $display = GameLetterUtility::buildDisplay($word, $correct);

        return [
            'word' => $word,
            'category' => $category,
            'clue' => $clue,
            'display' => $display,
            'won' => !str_contains($display, '_'),
            'lost' => $tries >= $maxTries,
            'tries' => $tries,
            'correct' => $correct,
            'wrong' => $wrong,
            'maxTries' => $maxTries,
            'usedWords' => $usedWords,
            'foundWords' => $foundWords,
            'usedWordsCount' => count($usedWords),
            'foundWordsCount' => count($foundWords),
            'restartAllowed' => $restartAllowed,
            'gameSlug' => $game,
        ];
    }

    private function startNewGame(string $game): void
    {
        $challenge = $this->generateChallenge($game);

        if ($challenge['resetHistory']) {
            session([
                $this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game) => [],
                $this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game) => [],
            ]);
        }

        $category = $challenge['category'];
        $word = $challenge['word'];
        $maxTries = $this->items->maxTries($category, $word);

        session([
            $this->key(ChallengeGameConstants::SESSION_WORD, $game) => $word,
            $this->key(ChallengeGameConstants::SESSION_CATEGORY, $game) => $category,
            $this->key(ChallengeGameConstants::SESSION_CORRECT, $game) => [],
            $this->key(ChallengeGameConstants::SESSION_WRONG, $game) => [],
            $this->key('max_tries', $game) => $maxTries,
        ]);

        $this->appendUniqueSessionWord(ChallengeGameConstants::SESSION_USED_WORDS, $word, $game);
    }

    private function appendUniqueSessionWord(string $key, string $word, string $game): void
    {
        $words = session($this->key($key, $game), []);
        if (!in_array($word, $words, true)) {
            $words[] = $word;
            session([$this->key($key, $game) => $words]);
        }
    }

    private function keyedSessionKeys(string $game): array
    {
        return array_map(fn(string $k) => $this->key($k, $game), ChallengeGameConstants::SESSION_KEYS);
    }

    private function key(string $base, string $game): string
    {
        return "games.$game.$base";
    }

    private function hasGuesses(string $game): bool
    {
        return !empty(session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []))
            || !empty(session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []));
    }
}
