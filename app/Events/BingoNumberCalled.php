<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoNumberCalled implements ShouldBroadcastNow
{
    use SerializesModels;

    public $number;
    public $roomId;

    public function __construct(int $number, int $roomId)
    {
        Log::info('Creando evento BingoNumberCalled', ['number' => $number, 'roomId' => $roomId]);

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
