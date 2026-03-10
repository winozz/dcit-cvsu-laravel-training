<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Services\Contracts\GameCatalogContract;
use App\Services\Contracts\GameServiceContract;
use Illuminate\Http\Request;

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

        return view('game.show', $gameData);
    }

    public function next(Request $request, string $game)
    {
        $this->gameService->restartGame($game);
        $gameData = $this->gameService->buildGameData($game);

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

        return response()->json($gameData);
    }
}
