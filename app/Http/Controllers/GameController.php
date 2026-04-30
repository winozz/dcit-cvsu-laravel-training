<?php

namespace App\Http\Controllers;

use App\DTO\GameStateData;
use App\DTO\MatchProgressData;
use App\Events\OpponentProgressUpdated;
use App\Http\Requests\StoreGameRequest;
use App\Models\ChallengeGameMatch;
use App\Models\Player;
use App\Services\MatchOutcomeService;
use App\Services\MatchProgressService;
use App\Services\Contracts\GameCatalogInterface;
use App\Services\Contracts\GameServiceInterface;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $gameData = $this->gameService->buildGameData($game)->withReadonly(false);

        return view('game.show', array_merge($gameData->toArray(), [
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

    public function guestUpdate(Request $request, string $game)
    {
        return response()->json(
            $this->runTurn($request, $game)->toArray()
        );
    }

    public function guestNext(Request $request, string $game)
    {
        $gameData = $this->restartRound($game);

        if ($request->expectsJson()) {
            $payload = $gameData->toArray();
            unset($payload['word']);

            return response()->json($payload);
        }

        return redirect()->route('guest.games.show', ['game' => $game]);
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
        $match = $this->authorizedMatch($matchCode);

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

        $opponentProgress = $this->opponentProgress($match);
        $this->storeLiveSnapshot($gameData, $matchCode);

        return view('game.show', array_merge($gameData->toArray(), [
            'matchCode' => $matchCode,
            'opponentProgress' => $opponentProgress?->toArray(),
            'playerName' => $this->currentPlayer()?->username,
            'opponentName' => $this->opponentName($match),
            'guestMode' => false,
        ]));
    }

    public function next(Request $request, string $game)
    {
        $gameData = $this->restartRound($game);

        $this->storeLiveSnapshot($gameData, $request->query('match'));
        if ($request->expectsJson()) {
            // Avoid leaking the answer on fresh rounds.
            $payload = $gameData->toArray();
            unset($payload['word']);
            return response()->json($payload);
        }

        return redirect()->route('games.show', ['game' => $game]);
    }

    public function update(Request $request, string $game)
    {
        $gameData = $this->runTurn($request, $game);

        $this->storeLiveSnapshot($gameData, $request->query('match'));
        return response()->json($gameData->toArray());
    }

    private function restartRound(string $game): GameStateData
    {
        $this->gameService->restartGame($game);

        return $this->gameService->buildGameData($game);
    }

    private function runTurn(Request $request, string $game): GameStateData
    {
        return $this->gameService->handleTurn(
            $request->boolean('reset_progress'),
            $request->boolean('restart'),
            $request->filled('letter') ? $request->input('letter') : null,
            $game
        );
    }

    private function storeLiveSnapshot(GameStateData $gameData, ?string $matchCode): void
    {
        $playerId = session('player_id');
        if (!$matchCode || !$playerId) return;

        $payload = $gameData->toMatchProgressData(
            version: (int) (microtime(true) * 1000),
        );

        $this->matchProgress->store($matchCode, $playerId, $payload);
        try {
            event(new OpponentProgressUpdated($matchCode, $payload->toArray()));
        } catch (BroadcastException $e) {
            Log::warning('Opponent progress broadcast failed', ['error' => $e->getMessage()]);
        }

        if ($gameData->isFinished()) {
            $this->matchOutcome->markProgress(
                $matchCode,
                $playerId,
                $gameData->won,
                $gameData->lost,
                $gameData->timedOut
            );
        }
    }

    private function opponentProgress(?ChallengeGameMatch $match): ?MatchProgressData
    {
        $playerId = session('player_id');
        if (!$match || !$playerId) return null;

        $opponentId = $match->host_player_id === $playerId
            ? $match->guest_player_id
            : $match->host_player_id;

        if (!$opponentId) return null;

        return $this->matchProgress->get($match->code, $opponentId);
    }

    private function currentPlayer(): ?Player
    {
        $playerId = session('player_id');
        return $playerId ? Player::find($playerId) : null;
    }

    private function opponentName(?ChallengeGameMatch $match): ?string
    {
        $player = $this->currentPlayer();
        if (!$match || !$player) return null;

        return $match->host_player_id === $player->id
            ? $match->guest?->username
            : $match->host?->username;
    }

    private function authorizedMatch(?string $matchCode): ?ChallengeGameMatch
    {
        if (!$matchCode) {
            return null;
        }

        $match = ChallengeGameMatch::where('code', $matchCode)
            ->with(['host', 'guest'])
            ->first();

        abort_if(!$match, 404);

        $player = $this->currentPlayer();
        abort_if(!$player, 403);
        $this->authorizeForUser($player, 'view', $match);

        return $match;
    }
}
