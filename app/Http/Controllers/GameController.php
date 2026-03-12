<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Models\ChallengeGameMatch;
use App\Services\MatchOutcomeService;
use App\Services\MatchProgressService;
use App\Services\Contracts\GameCatalogInterface;
use App\Services\Contracts\GameServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\BroadcastException;
use App\Events\OpponentProgressUpdated;

class GameController extends Controller
{
    public function __construct(
        private readonly GameServiceInterface $gameService,
        private readonly GameCatalogInterface $catalogService,
        private readonly MatchOutcomeService $matchOutcome,
        private readonly MatchProgressService $matchProgress,
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

    // Guest paths (no auth middleware)
    public function guestIndex()
    {
        return view('games.index', [
            'games' => $this->catalogService->all(includeDefaults: false, guest: true),
            'guestMode' => true,
        ]);
    }

    public function guestShow(Request $request, string $game = 'word-quest')
    {
        // Guests reuse the normal game flow but without match/multiplayer state
        $this->gameService->ensureGameStarted($game);
        $gameData = $this->gameService->buildGameData($game);
        $gameData['readonly'] = false;

        return view('game.show', array_merge($gameData, [
            'matchCode' => null,
            'opponentProgress' => null,
            'playerName' => 'Guest',
            'opponentName' => 'Opponent',
            'guestMode' => true,
        ]));
    }

    public function guestCreate()
    {
        return view('games.create', ['guestMode' => true]);
    }

    public function guestStore(StoreGameRequest $request)
    {
        $validated = $request->validated();
        $result = $this->catalogService->add($validated, guest: true);
        if (!$result['ok']) {
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        return redirect()->route('guest.games')->with('status', 'Game added to session list.');
    }

    public function index()
    {
        return view('games.index', ['games' => $this->catalogService->all()]);
    }

    public function show(Request $request, string $game = 'word-quest')
    {
        $matchCode = $request->query('match');

        // If switching to a different match code, clear prior progress so used/found words don't leak.
        $prevMatch = session('current_match_code');
        if ($matchCode && $matchCode !== $prevMatch) {
            $playerId = session('player_id');
            if ($playerId && $prevMatch) {
                $this->matchProgress->forget($prevMatch, $playerId);
            }
            $this->gameService->resetProgress($game);
            session(['current_match_code' => $matchCode]);
        }

        $this->gameService->ensureGameStarted($game);
        $gameData = $this->gameService->buildGameData($game);

        $opponentProgress = $this->opponentProgress($matchCode);
        $this->storeLiveSnapshot($gameData, $matchCode);

        return view('game.show', array_merge($gameData, [
            'matchCode' => $matchCode,
            'opponentProgress' => $opponentProgress,
            'playerName' => $this->currentPlayer()?->username,
            'opponentName' => $this->opponentName($matchCode),
            'guestMode' => false,
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

        $this->matchProgress->store($matchCode, $playerId, $payload);
        try {
            event(new OpponentProgressUpdated($matchCode, $payload));
        } catch (BroadcastException $e) {
            Log::warning('Opponent progress broadcast failed', ['error' => $e->getMessage()]);
        }

        if (($gameData['won'] ?? false) || ($gameData['lost'] ?? false)) {
            $this->matchOutcome->markProgress(
                $matchCode,
                $playerId,
                $gameData['won'] ?? false,
                $gameData['lost'] ?? false,
                $gameData['timedOut'] ?? false
            );
        }
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

        return $this->matchProgress->get($matchCode, $opponentId);
    }

    private function currentPlayer()
    {
        $playerId = session('player_id');
        return $playerId ? \App\Models\Player::find($playerId) : null;
    }

    private function opponentName(?string $matchCode): ?string
    {
        $player = $this->currentPlayer();
        if (!$matchCode || !$player) return null;

        $match = ChallengeGameMatch::where('code', $matchCode)->with(['host', 'guest'])->first();
        if (!$match) return null;

        return $match->host_player_id === $player->id
            ? $match->guest?->username
            : $match->host?->username;
    }
}
