<?php

namespace App\Services;

use App\Constants\ChallengeGameConstants;
use App\Models\ChallengeGameItem;

class ChallengeGameItemService
{
    /**
     * Return available words grouped by category, excluding already used/found words.
     * Caps to 5 words per difficulty (Easy/Medium/Hard) and 15 total.
     */
    public function availableByCategory(array $excludedWords = [], int $limitPerDifficulty = 5, int $globalLimit = 15): array
    {
        $query = ChallengeGameItem::query()
            ->select(['word', 'category', 'difficulty'])
            ->where('is_active', true);

        if ($excludedWords) {
            $query->whereNotIn('word', $excludedWords);
        }

        $items = $query->orderBy('difficulty')
            ->orderBy('category')
            ->orderBy('word')
            ->get();

        $grouped = [];
        $countsByDifficulty = [];
        $globalCount = 0;

        foreach ($items as $item) {
            if ($globalCount >= $globalLimit) break;
            $difficulty = (int) ($item->difficulty ?? 1);
            $current = $countsByDifficulty[$difficulty] ?? 0;
            if ($current >= $limitPerDifficulty) continue;

            $grouped[$item->category][] = $item->word;
            $countsByDifficulty[$difficulty] = $current + 1;
            $globalCount++;
        }

        $grouped = collect($grouped)
            ->map(fn($words) => array_values(array_unique($words)))
            ->filter()
            ->toArray();

        return $grouped;
    }

    /**
     * Fetch clue for a given category/word, falling back to constants/default.
     */
    public function clue(string $category, string $word): string
    {
        $clue = ChallengeGameItem::query()
            ->where('category', $category)
            ->where('word', $word)
            ->value('clue');

        return $clue ?? ChallengeGameConstants::DEFAULT_CLUE;
    }

    /**
     * Fetch max tries for a given item; fallback to constant.
     */
    public function maxTries(string $category, string $word): int
    {
        return (int) (ChallengeGameItem::query()
            ->where('category', $category)
            ->where('word', $word)
            ->value('max_tries') ?? ChallengeGameConstants::MAX_TRIES);
    }

    /**
     * Fetch difficulty for a given item; fallback to Easy (1).
     */
    public function difficulty(string $category, string $word): int
    {
        return (int) (ChallengeGameItem::query()
            ->where('category', $category)
            ->where('word', $word)
            ->value('difficulty') ?? 1);
    }
}
