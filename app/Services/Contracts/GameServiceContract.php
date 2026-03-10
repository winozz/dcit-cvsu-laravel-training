<?php

namespace App\Services\Contracts;

interface GameServiceContract
{
    public function handleTurn(bool $resetProgress, bool $restart, ?string $letter, string $game): array;
    public function ensureGameStarted(string $game): void;
    public function buildGameData(string $game): array;
    public function resetProgress(string $game): void;
    public function restartGame(string $game): void;
    /**
     * Provide a generated challenge (category + word) for a given game slug without mutating state.
     *
     * @return array{category:string,word:string,resetHistory:bool}
     */
    public function generateChallenge(string $game): array;
}
