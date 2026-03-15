<?php

namespace App\Services\SvcImplem;

use App\Constants\ChallengeGameConstants;
use App\DTO\GameStateData;
use App\Models\ChallengeGameAudit;
use App\Models\ChallengeGameRun;
use App\Services\ChallengeGameItemService;
use App\Services\Contracts\GameServiceInterface;
use App\Utilities\GameLetterUtility;
use Illuminate\Support\Carbon;

class GameService implements GameServiceInterface
{
    public function __construct(
        private readonly ChallengeGameItemService $items
    ) {}

    public function handleTurn(bool $resetProgress, bool $restart, ?string $letter, string $game): GameStateData
    {
        if ((bool) session($this->key(ChallengeGameConstants::SESSION_DEPLETED, $game), false)) {
            return $this->buildGameData($game);
        }

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
        return $gameData->isFinished()
            ? $this->finalizeRound($gameData, $game)
            : $gameData;
    }

    /**
     * @return array{category:string,word:string,resetHistory:bool}|null
     */
    private function generateChallenge(string $game): ?array
    {
        $history = $this->history($game);
        $excludedWords = array_values(array_unique(array_merge(
            $history['usedWords'],
            $history['foundWords'],
        )));

        // If we've already played 15 words, mark game as depleted immediately.
        if (count($excludedWords) >= ChallengeGameConstants::MAX_ROUNDS) {
            $this->markGameDepleted($game);
            return null;
        }

        $availableByCategory = $this->items->availableByCategory($excludedWords);

        // If every active word has been used, freeze the game instead of recycling words.
        $resetHistory = false;
        if (!$availableByCategory) {
            $this->markGameDepleted($game);
            return null;
        }

        $category = array_rand($availableByCategory);
        $word = $availableByCategory[$category][array_rand($availableByCategory[$category])];

        return [
            'category' => $category,
            'word' => $word,
            'resetHistory' => $resetHistory,
        ];
    }

    public function resetProgress(string $game, bool $force = false): void
    {
        if (!$force && (bool) session($this->key(ChallengeGameConstants::SESSION_DEPLETED, $game), false)) {
            return;
        }
        session()->forget($this->keyedSessionKeys($game));
        session()->forget([
            $this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game),
            $this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game),
            $this->key(ChallengeGameConstants::SESSION_DEPLETED, $game),
        ]);
        $this->startNewGame($game);
    }

    public function restartGame(string $game): void
    {
        if ((bool) session($this->key(ChallengeGameConstants::SESSION_DEPLETED, $game), false)) {
            return;
        }
        $this->clearGame($game);
        $this->startNewGame($game);
    }

    public function ensureGameStarted(string $game): void
    {
        if (!session($this->key(ChallengeGameConstants::SESSION_WORD, $game))) {
            $this->startNewGame($game);
        }
    }

    private function clearGame(string $game): void
    {
        session()->forget($this->keyedSessionKeys($game));
    }

    private function finalizeRound(GameStateData $gameData, string $game): GameStateData
    {
        $word = (string) session($this->key(ChallengeGameConstants::SESSION_WORD, $game), '');
        if ($gameData->won && $word) {
            $this->appendUniqueSessionWord(ChallengeGameConstants::SESSION_FOUND_WORDS, $word, $game);
        }
        $usedWords = session($this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game), []);
        $foundWords = session($this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game), []);
        $correct = session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []);
        $wrong = session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []);
        $this->clearGame($game);
        $this->persistRun($game, $word, $foundWords, $usedWords, $correct, $wrong, $gameData->won);

        return $gameData->withRoundSummary($usedWords, $foundWords);
    }

    private function processGuess(?string $letter, string $game): void
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

    public function buildGameData(string $game): GameStateData
    {
        $isDepleted = (bool) session($this->key(ChallengeGameConstants::SESSION_DEPLETED, $game), false);
        $difficulty = (int) session($this->key(ChallengeGameConstants::SESSION_DIFFICULTY, $game), 1);
        $difficultyLabel = $this->difficultyLabel($difficulty);
        $startedAt = session($this->key(ChallengeGameConstants::SESSION_TIMER_STARTED, $game)) ?: Carbon::now();
        session([$this->key(ChallengeGameConstants::SESSION_TIMER_STARTED, $game) => $startedAt]);
        $expiresAt = Carbon::parse($startedAt)->addMinutes(ChallengeGameConstants::ROUND_TIMER_MINUTES);
        $remainingSeconds = max(0, Carbon::now()->diffInSeconds($expiresAt, false));
        $timedOut = Carbon::now()->gte($expiresAt);
        if ($isDepleted) {
            $history = $this->history($game);
            return new GameStateData(
                word: '',
                category: '',
                clue: 'All challenges completed.',
                display: '----',
                won: false,
                lost: false,
                tries: 0,
                correct: [],
                wrong: [],
                maxTries: 0,
                usedWords: $history['usedWords'],
                foundWords: $history['foundWords'],
                usedWordsCount: count($history['usedWords']),
                foundWordsCount: count($history['foundWords']),
                restartAllowed: false,
                gameSlug: $game,
                readonly: true,
                difficulty: $difficulty,
                difficultyLabel: $difficultyLabel,
                timedOut: $timedOut,
                timerRemaining: 0,
            );
        }

        $word = (string) session($this->key(ChallengeGameConstants::SESSION_WORD, $game));
        $category = (string) session($this->key(ChallengeGameConstants::SESSION_CATEGORY, $game));
        $correct = session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []);
        $wrong = session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []);
        $history = $this->history($game);
        $tries = count($wrong);
        $restartAllowed = !$this->hasGuesses($game);
        $clue = $this->items->clue($category, $word);
        $maxTries = $this->items->maxTries($category, $word);
        $difficulty = (int) session($this->key(ChallengeGameConstants::SESSION_DIFFICULTY, $game), 1);
        $difficultyLabel = $this->difficultyLabel($difficulty);
        $display = GameLetterUtility::buildDisplay($word, $correct);
        if ($timedOut) {
            // force a loss when time runs out
            $tries = $maxTries;
        }

        return new GameStateData(
            word: $word,
            category: $category,
            clue: $clue,
            display: $display,
            won: !$timedOut && !str_contains($display, '_'),
            lost: $timedOut || $tries >= $maxTries,
            tries: $tries,
            correct: $correct,
            wrong: $wrong,
            maxTries: $maxTries,
            usedWords: $history['usedWords'],
            foundWords: $history['foundWords'],
            usedWordsCount: count($history['usedWords']),
            foundWordsCount: count($history['foundWords']),
            restartAllowed: $restartAllowed,
            gameSlug: $game,
            readonly: false,
            difficulty: $difficulty,
            difficultyLabel: $difficultyLabel,
            timedOut: $timedOut,
            timerRemaining: $timedOut ? 0 : $remainingSeconds,
        );
    }

    private function startNewGame(string $game): void
    {
        $challenge = $this->generateChallenge($game);

        if ($challenge === null) {
            return;
        }

        if ($challenge['resetHistory']) {
            session([
                $this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game) => [],
                $this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game) => [],
            ]);
        }

        $category = $challenge['category'];
        $word = $challenge['word'];
        $maxTries = $this->items->maxTries($category, $word);
        $difficulty = $this->items->difficulty($category, $word);

        session([
            $this->key(ChallengeGameConstants::SESSION_WORD, $game) => $word,
            $this->key(ChallengeGameConstants::SESSION_CATEGORY, $game) => $category,
            $this->key(ChallengeGameConstants::SESSION_CORRECT, $game) => [],
            $this->key(ChallengeGameConstants::SESSION_WRONG, $game) => [],
            $this->key(ChallengeGameConstants::SESSION_MAX_TRIES, $game) => $maxTries,
            $this->key(ChallengeGameConstants::SESSION_DIFFICULTY, $game) => $difficulty,
            $this->key(ChallengeGameConstants::SESSION_TIMER_STARTED, $game) => Carbon::now(),
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

    private function markGameDepleted(string $game): void
    {
        $this->persistAudit($game, ChallengeGameConstants::AUDIT_STATUS_DEPLETED);
        session([$this->key(ChallengeGameConstants::SESSION_DEPLETED, $game) => true]);
    }

    private function persistRun(string $game, string $word, array $foundWords, array $usedWords, array $correct, array $wrong, bool $won): void
    {
        if ($word === '') return;

        $maxTries = (int) session($this->key(ChallengeGameConstants::SESSION_MAX_TRIES, $game), ChallengeGameConstants::MAX_TRIES);
        $category = (string) session($this->key(ChallengeGameConstants::SESSION_CATEGORY, $game), '');

        ChallengeGameRun::create([
            'game_slug' => $game,
            'category' => $category,
            'word' => $word,
            'tries' => count($wrong),
            'max_tries' => $maxTries,
            'won' => $won,
            'correct' => $correct,
            'wrong' => $wrong,
            'used_words' => $usedWords,
            'found_words' => $foundWords,
        ]);
    }


    private function persistAudit(string $game, string $status): void
    {
        $history = $this->history($game);

        ChallengeGameAudit::create([
            'game_slug' => $game,
            'status' => $status,
            'used_words' => $history['usedWords'],
            'found_words' => $history['foundWords'],
        ]);
    }

    private function keyedSessionKeys(string $game): array
    {
        return array_map(fn(string $k) => $this->key($k, $game), ChallengeGameConstants::SESSION_KEYS);
    }

    private function key(string $base, string $game): string
    {
        // Scope session data per player when logged in, so each player owns an isolated board.
        $playerId = session('player_id');
        $prefix = $playerId ? "players.$playerId." : '';

        return "{$prefix}games.$game.$base";
    }

    private function difficultyLabel(int $difficulty): string
    {
        return ChallengeGameConstants::DIFFICULTY_LABELS[$difficulty] ?? ChallengeGameConstants::DIFFICULTY_LABELS[1];
    }

    /**
     * @return array{usedWords: array<int, string>, foundWords: array<int, string>}
     */
    private function history(string $game): array
    {
        return [
            'usedWords' => session($this->key(ChallengeGameConstants::SESSION_USED_WORDS, $game), []),
            'foundWords' => session($this->key(ChallengeGameConstants::SESSION_FOUND_WORDS, $game), []),
        ];
    }

    private function hasGuesses(string $game): bool
    {
        return !empty(session($this->key(ChallengeGameConstants::SESSION_CORRECT, $game), []))
            || !empty(session($this->key(ChallengeGameConstants::SESSION_WRONG, $game), []));
    }
}
