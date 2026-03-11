<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\PlayerAuthController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\MatchProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('lobby.index');
})->name('home');

// Player auth
// More generous throttling to avoid 429 locally
Route::get('login', [PlayerAuthController::class, 'showLogin'])->middleware(['throttle:60,1','no.concurrent.login'])->name('players.login.form');
Route::post('login', [PlayerAuthController::class, 'login'])->middleware(['throttle:60,1','no.concurrent.login'])->name('players.login');
Route::get('register', [RegistrationController::class, 'create'])->middleware(['throttle:60,1','no.concurrent.login'])->name('players.register.form');
Route::post('register', [RegistrationController::class, 'store'])->middleware(['throttle:60,1','no.concurrent.login'])->name('players.register');
Route::post('logout', [PlayerAuthController::class, 'logout'])->name('players.logout');

// Guest games (no auth)
Route::get('guest/games', [GameController::class, 'guestIndex'])->name('guest.games');
// Place the static create route before the {game} wildcard so "create" isn't captured as a slug
Route::get('guest/games/create', [GameController::class, 'guestCreate'])->name('guest.games.create');
Route::get('guest/games/{game}', [GameController::class, 'guestShow'])->name('guest.games.show');
Route::post('guest/games', [GameController::class, 'guestStore'])->name('guest.games.store');

// Lobby + matches
Route::middleware('player.session')->group(function () {
    Route::get('lobby', [LobbyController::class, 'index'])->name('lobby.index');
    Route::post('lobby/matches', [LobbyController::class, 'store'])->name('lobby.store');
    Route::post('lobby/matches/{match}/join', [LobbyController::class, 'join'])->name('lobby.join');
    Route::get('matches/{match}/stream', [MatchProgressController::class, 'stream'])->name('matches.stream');
    Route::get('matches/{match}/opponent', [MatchProgressController::class, 'opponent'])->name('matches.opponent');
    Route::post('matches/{match}/forfeit', [MatchProgressController::class, 'forfeit'])->name('matches.forfeit');
    Route::post('matches/{match}/exit', [MatchProgressController::class, 'exit'])->name('matches.exit');
    Route::get('matches/{match}/status', [MatchProgressController::class, 'status'])->name('matches.status');

    // Game routes (protected)
    Route::resource('games', GameController::class)->except(['edit']);
    Route::post('games/{game}/next', [GameController::class, 'next'])->name('games.next');
    Route::delete('games', [GameController::class, 'destroyAll'])->name('games.destroyAll');
});
