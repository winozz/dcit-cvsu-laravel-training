<?php

namespace App\Constants;

final class ChallengeGameConstants
{
    public const MAX_TRIES = 5;

    public const SESSION_WORD = 'word';
    public const SESSION_CATEGORY = 'category';
    public const SESSION_CORRECT = 'correct';
    public const SESSION_WRONG = 'wrong';
    public const SESSION_USED_WORDS = 'used_words';
    public const SESSION_FOUND_WORDS = 'found_words';
    public const SESSION_DIFFICULTY = 'difficulty';
    public const DEFAULT_CLUE = 'No clue available.';

    public const SESSION_KEYS = [
        self::SESSION_WORD,
        self::SESSION_CATEGORY,
        self::SESSION_CORRECT,
        self::SESSION_WRONG,
        self::SESSION_DIFFICULTY,
    ];
}
