<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'username',
        'email',
        'google_id',
        'email_verified_at',
        'email_verification_code',
        'email_verification_expires_at',
        'session_token',
        'password',
        'wins',
        'losses',
        'games_played',
    ];

    protected $hidden = ['password', 'email_verification_code'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires_at' => 'datetime',
        'wins' => 'integer',
        'losses' => 'integer',
        'games_played' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Player $player): void {
            $player->public_id ??= (string) Str::ulid();
        });
    }

    public function getWinrateAttribute(): float
    {
        $played = max(1, $this->games_played);
        return round(($this->wins / $played) * 100, 2);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function requiresEmailVerification(): bool
    {
        return filled($this->email) && !$this->hasVerifiedEmail();
    }
}
