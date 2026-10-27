<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoGameStarted implements ShouldBroadcastNow
{
    use SerializesModels;
    public $drawId;

    public $roomId;
    public $startTime;

    public function __construct(int $roomId, int $drawId)
    {
        Log::info('Creando evento BingoGameStarted', [
            'roomId' => $roomId,
            'drawId' => $drawId,
        ]);

        $this->roomId = $roomId;
        $this->drawId = $drawId;
        $this->startTime = now()->toISOString();
    }

    public function broadcastAs()
    {
        return 'GameStarted';
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'draw_id' => $this->drawId,
            'start_time' => $this->startTime,
        ];
    }
}
