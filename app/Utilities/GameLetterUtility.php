<?php

namespace App\Utilities;

final class GameLetterUtility
{
    public static function normalizeGuess(?string $letter): ?string
    {
        $normalized = strtolower(substr(trim((string) $letter), 0, 1));
        if ($normalized === '' || !ctype_alpha($normalized)) {
            return null;
        }

        return $normalized;
    }

    public static function isNewGuess(string $letter, array $correct, array $wrong): bool
    {
        return !in_array($letter, $correct, true) && !in_array($letter, $wrong, true);
    }

    public static function buildDisplay(string $word, array $correct): string
    {
        return implode(' ', array_map(
            // Symbols (e.g. +, #) are auto-revealed and should not require guessing.
            static fn($char) => ctype_alpha($char) ? (in_array(strtolower($char), $correct, true) ? $char : '_') : $char,
            str_split($word)
        ));
    }
}
