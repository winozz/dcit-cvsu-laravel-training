<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/guess', [GameController::class, 'show'])->name('guess');
Route::put('/guess', [GameController::class, 'update'])->name('guess.put');
