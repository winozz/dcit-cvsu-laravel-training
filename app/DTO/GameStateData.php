<?php

namespace App\DTO;

final readonly class GameStateData
{
    /**
     * @param array<int, string> $correct
     * @param array<int, string> $wrong
     * @param array<int, string> $usedWords
     * @param array<int, string> $foundWords
     */
    public function __construct(
        public string $word,
        public string $category,
        public string $clue,
        public string $display,
        public bool $won,
        public bool $lost,
        public int $tries,
        public array $correct,
        public array $wrong,
        public int $maxTries,
        public array $usedWords,
        public array $foundWords,
        public int $usedWordsCount,
        public int $foundWordsCount,
        public bool $restartAllowed,
        public string $gameSlug,
        public bool $readonly,
        public int $difficulty,
        public string $difficultyLabel,
        public bool $timedOut,
        public int $timerRemaining,
    ) {
    }

    public function isFinished(): bool
    {
        return $this->won || $this->lost;
    }

    /**
     * @param array<int, string> $usedWords
     * @param array<int, string> $foundWords
     */
    public function withRoundSummary(array $usedWords, array $foundWords): self
    {
        return $this->copy(
            usedWords: $usedWords,
            foundWords: $foundWords,
            usedWordsCount: count($usedWords),
            foundWordsCount: count($foundWords),
            restartAllowed: false,
        );
    }

    public function withReadonly(bool $readonly): self
    {
        return $this->copy(readonly: $readonly);
    }

    public function toMatchProgressData(int $version): MatchProgressData
    {
        return new MatchProgressData(
            version: $version,
            display: $this->display,
            tries: $this->tries,
            maxTries: $this->maxTries,
            usedWordsCount: $this->usedWordsCount,
            foundWordsCount: $this->foundWordsCount,
            won: $this->won,
            lost: $this->lost,
            readonly: $this->readonly,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'word' => $this->word,
            'category' => $this->category,
            'clue' => $this->clue,
            'display' => $this->display,
            'won' => $this->won,
            'lost' => $this->lost,
            'tries' => $this->tries,
            'correct' => $this->correct,
            'wrong' => $this->wrong,
            'maxTries' => $this->maxTries,
            'usedWords' => $this->usedWords,
            'foundWords' => $this->foundWords,
            'usedWordsCount' => $this->usedWordsCount,
            'foundWordsCount' => $this->foundWordsCount,
            'restartAllowed' => $this->restartAllowed,
            'gameSlug' => $this->gameSlug,
            'readonly' => $this->readonly,
            'difficulty' => $this->difficulty,
            'difficultyLabel' => $this->difficultyLabel,
            'timedOut' => $this->timedOut,
            'timerRemaining' => $this->timerRemaining,
        ];
    }

    /**
     * @param array<int, string>|null $correct
     * @param array<int, string>|null $wrong
     * @param array<int, string>|null $usedWords
     * @param array<int, string>|null $foundWords
     */
    private function copy(
        ?string $word = null,
        ?string $category = null,
        ?string $clue = null,
        ?string $display = null,
        ?bool $won = null,
        ?bool $lost = null,
        ?int $tries = null,
        ?array $correct = null,
        ?array $wrong = null,
        ?int $maxTries = null,
        ?array $usedWords = null,
        ?array $foundWords = null,
        ?int $usedWordsCount = null,
        ?int $foundWordsCount = null,
        ?bool $restartAllowed = null,
        ?string $gameSlug = null,
        ?bool $readonly = null,
        ?int $difficulty = null,
        ?string $difficultyLabel = null,
        ?bool $timedOut = null,
        ?int $timerRemaining = null,
    ): self {
        return new self(
            word: $word ?? $this->word,
            category: $category ?? $this->category,
            clue: $clue ?? $this->clue,
            display: $display ?? $this->display,
            won: $won ?? $this->won,
            lost: $lost ?? $this->lost,
            tries: $tries ?? $this->tries,
            correct: $correct ?? $this->correct,
            wrong: $wrong ?? $this->wrong,
            maxTries: $maxTries ?? $this->maxTries,
            usedWords: $usedWords ?? $this->usedWords,
            foundWords: $foundWords ?? $this->foundWords,
            usedWordsCount: $usedWordsCount ?? $this->usedWordsCount,
            foundWordsCount: $foundWordsCount ?? $this->foundWordsCount,
            restartAllowed: $restartAllowed ?? $this->restartAllowed,
            gameSlug: $gameSlug ?? $this->gameSlug,
            readonly: $readonly ?? $this->readonly,
            difficulty: $difficulty ?? $this->difficulty,
            difficultyLabel: $difficultyLabel ?? $this->difficultyLabel,
            timedOut: $timedOut ?? $this->timedOut,
            timerRemaining: $timerRemaining ?? $this->timerRemaining,
        );
    }
}
