<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeGameMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'host_player_id',
        'guest_player_id',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        // Allow implicit route-model binding by match code instead of numeric id.
        return 'code';
    }

    public function host()
    {
        return $this->belongsTo(Player::class, 'host_player_id');
    }

    public function guest()
    {
        return $this->belongsTo(Player::class, 'guest_player_id');
    }
}
