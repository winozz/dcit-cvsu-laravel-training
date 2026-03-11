<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Models\ChallengeGameMatch;
use App\Services\Contracts\GameCatalogContract;
use App\Services\Contracts\GameServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameController extends Controller
{
    public function __construct(
        private readonly GameServiceContract $gameService,
        private readonly GameCatalogContract $catalogService,
    )
    {
    }

    public function create()
    {
        // Simple placeholder form; you can expand with validation/persistence later.
        return view('games.create');
    }

    public function store(StoreGameRequest $request)
    {
        $validated = $request->validated();
        $result = $this->catalogService->add($validated);
        if (!$result['ok']) {
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        return redirect()->route('games.index')->with('status', 'Game added to session list.');
    }

    public function destroyAll()
    {
        $this->catalogService->reset();
        return redirect()->route('games.index')->with('status', 'Custom games cleared.');
    }

    public function index()
    {
        return view('games.index', ['games' => $this->catalogService->all()]);
    }

    public function show(Request $request, string $game = 'word-quest')
    {
        $this->gameService->ensureGameStarted($game);
        $gameData = $this->gameService->buildGameData($game);

        $matchCode = $request->query('match');
        $opponentProgress = $this->opponentProgress($matchCode);
        $this->storeLiveSnapshot($gameData, $matchCode);

        return view('game.show', array_merge($gameData, [
            'matchCode' => $matchCode,
            'opponentProgress' => $opponentProgress,
        ]));
    }

    public function next(Request $request, string $game)
    {
        $this->gameService->restartGame($game);
        $gameData = $this->gameService->buildGameData($game);

        $this->storeLiveSnapshot($gameData, $request->query('match'));
        if ($request->expectsJson()) {
            // Avoid leaking the answer on fresh rounds.
            unset($gameData['word']);
            return response()->json($gameData);
        }

        return redirect()->route('games.show', ['game' => $game]);
    }

    public function update(Request $request, string $game)
    {
        $restart = $request->boolean('restart');
        $letter = $request->filled('letter') ? $request->input('letter') : null;
        $gameData = $this->gameService->handleTurn(
            $request->boolean('reset_progress'),
            $restart,
            $letter,
            $game
        );

        $this->storeLiveSnapshot($gameData, $request->query('match'));
        return response()->json($gameData);
    }

    private function storeLiveSnapshot(array $gameData, ?string $matchCode): void
    {
        $playerId = session('player_id');
        if (!$matchCode || !$playerId) return;

        $payload = [
            'version' => (int) (microtime(true) * 1000), // ms precision to avoid same-second collisions
            'display' => $gameData['display'] ?? '',
            'tries' => $gameData['tries'] ?? 0,
            'maxTries' => $gameData['maxTries'] ?? 0,
            'usedWordsCount' => $gameData['usedWordsCount'] ?? 0,
            'foundWordsCount' => $gameData['foundWordsCount'] ?? 0,
            'won' => $gameData['won'] ?? false,
            'lost' => $gameData['lost'] ?? false,
            'readonly' => $gameData['readonly'] ?? false,
        ];

        Cache::put($this->progressKey($matchCode, $playerId), $payload, now()->addMinutes(60));
    }

    private function opponentProgress(?string $matchCode): ?array
    {
        $playerId = session('player_id');
        if (!$matchCode || !$playerId) return null;

        $match = ChallengeGameMatch::where('code', $matchCode)->first();
        if (!$match) return null;

        $opponentId = $match->host_player_id === $playerId
            ? $match->guest_player_id
            : $match->host_player_id;

        if (!$opponentId) return null;

        return Cache::get($this->progressKey($matchCode, $opponentId));
    }

    private function progressKey(string $matchCode, int $playerId): string
    {
        return "matches.$matchCode.players.$playerId.progress";
    }
}
