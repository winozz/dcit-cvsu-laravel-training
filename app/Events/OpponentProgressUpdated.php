<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OpponentProgressUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public string $matchCode, public array $payload)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('match.' . $this->matchCode);
    }
}
