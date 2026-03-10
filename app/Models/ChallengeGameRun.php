<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeGameRun extends Model
{
    protected $fillable = [
        'game_slug',
        'category',
        'word',
        'tries',
        'max_tries',
        'won',
        'correct',
        'wrong',
        'used_words',
        'found_words',
    ];

    protected $casts = [
        'correct' => 'array',
        'wrong' => 'array',
        'used_words' => 'array',
        'found_words' => 'array',
        'won' => 'boolean',
    ];
}
