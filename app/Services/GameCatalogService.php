<?php

namespace App\Services;

use App\Services\Contracts\GameCatalogContract;
use Illuminate\Support\Str;

class GameCatalogService implements GameCatalogContract
{
    private const SESSION_KEY = 'custom_games';

    private array $defaultGames = [
        [
            'slug' => 'word-quest',
            'name' => 'Word Quest',
            'description' => 'Classic letter guessing game with categories and clues.',
            'route' => 'games.show',
        ],
    ];

    /** @return array<int,array> */
    public function all(): array
    {
        return array_merge($this->defaultGames, session(self::SESSION_KEY, []));
    }

    /**
     * @param array{name?:string,slug?:string|null,description?:string|null} $data
     * @return array{ok:bool,message?:string,game?:array}
     */
    public function add(array $data): array
    {
        $slug = $data['slug'] ?? '';
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';

        if ($slug === '') {
            $slug = Str::slug($name);
        }

        if ($slug === '') {
            return ['ok' => false, 'message' => 'Slug could not be generated.'];
        }

        if ($this->existsBySlug($slug)) {
            return ['ok' => false, 'message' => 'Slug is already in use.'];
        }

        if ($this->existsByName($name)) {
            return ['ok' => false, 'message' => 'Name is already in use.'];
        }

        $game = [
            'slug' => $slug,
            'name' => $name,
            'description' => $description,
            'route' => 'games.show',
        ];

        $custom = session(self::SESSION_KEY, []);
        $custom[] = $game;
        session([self::SESSION_KEY => $custom]);

        return ['ok' => true, 'game' => $game];
    }

    public function reset(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function existsBySlug(string $slug): bool
    {
        return collect($this->all())
            ->map(fn($game) => $game['slug'] ?? '')
            ->contains($slug);
    }

    private function existsByName(string $name): bool
    {
        return collect($this->all())
            ->map(fn($game) => $game['name'] ?? '')
            ->filter(fn($gameName) => strcasecmp($gameName, $name) === 0)
            ->isNotEmpty();
    }
}
