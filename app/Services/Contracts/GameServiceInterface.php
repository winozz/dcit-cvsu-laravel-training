<?php

namespace App\Services\Contracts;

use App\DTO\GameStateData;

interface GameServiceInterface
{
    public function handleTurn(bool $resetProgress, bool $restart, ?string $letter, string $game): GameStateData;
    public function ensureGameStarted(string $game): void;
    public function buildGameData(string $game): GameStateData;
    public function resetProgress(string $game, bool $force = false): void;
    public function restartGame(string $game): void;
}
