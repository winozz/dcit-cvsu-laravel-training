<?php

namespace App\Services;

use App\Services\Contracts\GameCatalogInterface;
use Illuminate\Support\Str;

class GameCatalogService implements GameCatalogInterface
{
    private const SESSION_KEY = 'custom_games';
    private const GUEST_SESSION_KEY = 'guest_custom_games';

    private array $defaultGames = [
        [
            'slug' => 'word-quest',
            'name' => 'Word Quest',
            'description' => 'Classic letter guessing game with categories and clues.',
            'route' => 'games.show',
        ],
    ];

    /** @return array<int,array> */
    public function all(bool $includeDefaults = true, bool $guest = false): array
    {
        $sessionKey = $guest ? self::GUEST_SESSION_KEY : self::SESSION_KEY;
        $custom = session($sessionKey, []);

        return $includeDefaults
            ? array_merge($this->defaultGames, $custom)
            : $custom;
    }

    /**
     * @param array{name?:string,slug?:string|null,description?:string|null} $data
     * @return array{ok:bool,message?:string,game?:array}
     */
    public function add(array $data, bool $guest = false): array
    {
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';

        // Always use a UUID as the route/key identifier to avoid slug collisions.
        $uuid = (string) Str::uuid();

        if ($this->existsBySlug($uuid, $guest)) {
            return ['ok' => false, 'message' => 'ID collision occurred; try again.'];
        }

        if ($this->existsByName($name, $guest)) {
            return ['ok' => false, 'message' => 'Name is already in use.'];
        }

        $game = [
            'slug' => $uuid, // kept as "slug" for routing compatibility, but value is a UUID
            'uuid' => $uuid,
            'name' => $name,
            'description' => $description,
            'route' => 'games.show',
        ];

        $sessionKey = $guest ? self::GUEST_SESSION_KEY : self::SESSION_KEY;
        $custom = session($sessionKey, []);
        $custom[] = $game;
        session([$sessionKey => $custom]);

        return ['ok' => true, 'game' => $game];
    }

    public function reset(bool $guest = false): void
    {
        $sessionKey = $guest ? self::GUEST_SESSION_KEY : self::SESSION_KEY;
        session()->forget($sessionKey);
    }

    private function existsBySlug(string $slug, bool $guest = false): bool
    {
        return collect($this->all(guest: $guest))
            ->map(fn($game) => $game['slug'] ?? '')
            ->contains($slug);
    }

    private function existsByName(string $name, bool $guest = false): bool
    {
        return collect($this->all(guest: $guest))
            ->map(fn($game) => $game['name'] ?? '')
            ->filter(fn($gameName) => strcasecmp($gameName, $name) === 0)
            ->isNotEmpty();
    }
}
