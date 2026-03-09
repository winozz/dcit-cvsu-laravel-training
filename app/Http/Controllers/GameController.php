<?php

namespace App\Http\Controllers;

use App\Services\GameService;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(private readonly GameService $gameService)
    {
    }

    public function show(Request $request)
    {
        $this->gameService->ensureGameStarted();
        $gameData = $this->gameService->buildGameData();

        return view('game.show', $gameData);
    }

    public function update(Request $request)
    {
        $restart = $request->boolean('restart');
        $letter = $request->filled('letter') ? $request->input('letter') : null;
        $gameData = $this->gameService->handleTurn(
            $request->boolean('reset_progress'),
            $restart,
            $letter
        );

        return response()->json($gameData);
    }
}
