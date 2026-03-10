<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeGameAudit extends Model
{
    protected $fillable = [
        'game_slug',
        'status',
        'used_words',
        'found_words',
    ];

    protected $casts = [
        'used_words' => 'array',
        'found_words' => 'array',
    ];
}
