<?php

namespace App\DTO;

final readonly class MatchProgressData
{
    public function __construct(
        public int $version,
        public string $display,
        public int $tries,
        public int $maxTries,
        public int $usedWordsCount,
        public int $foundWordsCount,
        public bool $won,
        public bool $lost,
        public bool $readonly,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            version: (int) ($payload['version'] ?? 0),
            display: (string) ($payload['display'] ?? ''),
            tries: (int) ($payload['tries'] ?? 0),
            maxTries: (int) ($payload['maxTries'] ?? 0),
            usedWordsCount: (int) ($payload['usedWordsCount'] ?? 0),
            foundWordsCount: (int) ($payload['foundWordsCount'] ?? 0),
            won: (bool) ($payload['won'] ?? false),
            lost: (bool) ($payload['lost'] ?? false),
            readonly: (bool) ($payload['readonly'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'display' => $this->display,
            'tries' => $this->tries,
            'maxTries' => $this->maxTries,
            'usedWordsCount' => $this->usedWordsCount,
            'foundWordsCount' => $this->foundWordsCount,
            'won' => $this->won,
            'lost' => $this->lost,
            'readonly' => $this->readonly,
        ];
    }
}
