<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Game routes (no edit form)
Route::resource('games', GameController::class)->except(['edit']);
Route::post('games/{game}/next', [GameController::class, 'next'])->name('games.next');
Route::delete('games', [GameController::class, 'destroyAll'])->name('games.destroyAll');
