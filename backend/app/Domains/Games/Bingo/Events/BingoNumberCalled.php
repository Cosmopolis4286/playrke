<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BingoNumberCalled implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly int $number,
        public readonly int $roomId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'BingoNumberCalled';
    }

    public function broadcastWith(): array
    {
        return ['number' => $this->number];
    }
}
