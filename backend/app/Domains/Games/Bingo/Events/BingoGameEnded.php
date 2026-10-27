<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoGameEnded implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly int $roomId,
        public readonly int $drawId,
        public readonly bool $hasWinner,
        public readonly int $totalDrawn,
        public readonly array $winners,
        public readonly float $prize,
        public readonly string $endTime,
    ) {
        Log::info('BingoGameEnded', [
            'roomId' => $roomId,
            'hasWinner' => $hasWinner,
            'totalDrawn' => $totalDrawn,
            'winners' => count($winners),
        ]);
    }

    public function broadcastAs() : string
    {
        return 'GameEnded';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    /**
     * This channel is public. Only broadcast non-identifying game metadata;
     * authorized clients fetch detailed results through the room-scoped HTTP
     * endpoint (or the external-player link-scoped endpoint).
     */
    public function broadcastWith() : array
    {
        return [
            'room_id' => $this->roomId,
            'draw_id' => $this->drawId,
            'has_winner' => $this->hasWinner,
            'total_drawn' => $this->totalDrawn,
            'end_time' => $this->endTime,
            'results_available' => true,
        ];
    }
}
