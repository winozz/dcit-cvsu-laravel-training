<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'session_token',
        'password',
        'wins',
        'losses',
        'games_played',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'wins' => 'integer',
        'losses' => 'integer',
        'games_played' => 'integer',
    ];

    public function getWinrateAttribute(): float
    {
        $played = max(1, $this->games_played);
        return round(($this->wins / $played) * 100, 2);
    }
}
