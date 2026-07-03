<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BingoNumberCalled implements ShouldBroadcastNow
{
    use SerializesModels;

    public $number;
    public $roomId;

    public function __construct(int $number, int $roomId)
    {
        $this->number = $number;
        $this->roomId = $roomId;
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'BingoNumberCalled';
    }
}
