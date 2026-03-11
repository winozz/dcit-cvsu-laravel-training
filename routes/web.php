<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\PlayerAuthController;
use App\Http\Controllers\MatchProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Player auth (username only)
Route::get('login', [PlayerAuthController::class, 'showLogin'])->name('players.login.form');
Route::post('login', [PlayerAuthController::class, 'login'])->name('players.login');
Route::post('logout', [PlayerAuthController::class, 'logout'])->name('players.logout');

// Lobby + matches
Route::middleware('player.session')->group(function () {
    Route::get('lobby', [LobbyController::class, 'index'])->name('lobby.index');
    Route::post('lobby/matches', [LobbyController::class, 'store'])->name('lobby.store');
    Route::post('lobby/matches/{match}/join', [LobbyController::class, 'join'])->name('lobby.join');
    Route::get('matches/{match}/stream', [MatchProgressController::class, 'stream'])->name('matches.stream');
    Route::get('matches/{match}/opponent', [MatchProgressController::class, 'opponent'])->name('matches.opponent');
});

// Game routes (no edit form)
Route::resource('games', GameController::class)->except(['edit']);
Route::post('games/{game}/next', [GameController::class, 'next'])->name('games.next');
Route::delete('games', [GameController::class, 'destroyAll'])->name('games.destroyAll');
