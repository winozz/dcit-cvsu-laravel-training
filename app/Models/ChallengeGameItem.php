<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeGameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'category',
        'clue',
        'difficulty',
        'max_tries',
        'is_active',
        'times_played',
        'times_solved',
    ];
}
