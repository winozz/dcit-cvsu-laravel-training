<?php

namespace App\Constants;

final class ChallengeGameConstants
{
    public const MAX_TRIES = 5;
    public const MAX_ROUNDS = 15;
    public const ROUND_TIMER_MINUTES = 2;

    public const SESSION_WORD = 'word';
    public const SESSION_CATEGORY = 'category';
    public const SESSION_CORRECT = 'correct';
    public const SESSION_WRONG = 'wrong';
    public const SESSION_USED_WORDS = 'used_words';
    public const SESSION_FOUND_WORDS = 'found_words';
    public const SESSION_DIFFICULTY = 'difficulty';
    public const SESSION_MAX_TRIES = 'max_tries';
    public const SESSION_DEPLETED = 'depleted';
    public const SESSION_TIMER_STARTED = 'timer_started';
    public const DEFAULT_CLUE = 'No clue available.';
    public const AUDIT_STATUS_DEPLETED = 'depleted';
    public const DIFFICULTY_LABELS = [
        1 => 'Easy',
        2 => 'Medium',
        3 => 'Hard',
    ];

    public const SESSION_KEYS = [
        self::SESSION_WORD,
        self::SESSION_CATEGORY,
        self::SESSION_CORRECT,
        self::SESSION_WRONG,
        self::SESSION_DIFFICULTY,
        self::SESSION_MAX_TRIES,
        self::SESSION_TIMER_STARTED,
    ];
}
