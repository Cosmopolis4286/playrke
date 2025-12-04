<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BingoOFF_GetRoomInfo implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $encrypted;

    public function __construct($encrypted)
    {
        $this->encrypted = $encrypted;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("bingo.{$this->encrypted}"),
        ];
    }
}
