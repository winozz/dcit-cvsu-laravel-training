<?php

namespace App\Models;

use App\Enums\ChallengeGameMatchResult;
use App\Enums\ChallengeGameMatchStatus;
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
        'host_done',
        'guest_done',
        'host_forfeit',
        'guest_forfeit',
        'host_result',
        'guest_result',
        'expires_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChallengeGameMatchStatus::class,
            'host_done' => 'boolean',
            'guest_done' => 'boolean',
            'host_forfeit' => 'boolean',
            'guest_forfeit' => 'boolean',
            'host_result' => ChallengeGameMatchResult::class,
            'guest_result' => ChallengeGameMatchResult::class,
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

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
