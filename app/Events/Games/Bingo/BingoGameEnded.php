<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoGameEnded implements ShouldBroadcastNow
{
    use SerializesModels;

    public $roomId;
    public $drawId;
    public $endTime;
    public $hasWinner;

    public function __construct(int $roomId, int $drawId, bool $hasWinner = false)
    {
        Log::info('Creando evento BingoGameEnded', [
            'roomId' => $roomId,
            'drawId' => $drawId,
            'hasWinner' => $hasWinner
        ]);

        $this->roomId = $roomId;
        $this->drawId = $drawId;
        $this->hasWinner = $hasWinner;
        $this->endTime = now()->toISOString();
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'GameEnded';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'draw_id' => $this->drawId,
            'has_winner' => $this->hasWinner,
            'end_time' => $this->endTime,
        ];
    }
}
