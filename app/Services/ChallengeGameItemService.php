<?php

namespace App\Services;

use App\Constants\ChallengeGameConstants;
use App\Models\ChallengeGameItem;

class ChallengeGameItemService
{
    /**
     * Return available words grouped by category, excluding already used/found words.
     */
    public function availableByCategory(array $excludedWords = []): array
    {
        $query = ChallengeGameItem::query()
            ->select(['word', 'category'])
            ->where('is_active', true);

        if ($excludedWords) {
            $query->whereNotIn('word', $excludedWords);
        }

        $grouped = $query->orderBy('category')
            ->orderBy('word')
            ->get()
            ->groupBy('category')
            ->map(fn($items) => $items->pluck('word')->all())
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
}
