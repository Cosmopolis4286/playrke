<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoGameEnded implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly int    $roomId,
        public readonly int    $drawId,
        public readonly bool   $hasWinner,
        public readonly int    $totalDrawn,
        public readonly array  $winners,   // [{user_id, user_name, card_id, card_serial, prize}]
        public readonly float  $prize,
        public readonly string $endTime,
    ) {
        Log::info('BingoGameEnded', [
            'roomId'     => $roomId,
            'hasWinner'  => $hasWinner,
            'totalDrawn' => $totalDrawn,
            'winners'    => count($winners),
        ]);
    }

    public function broadcastOn(): Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'GameEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'     => $this->roomId,
            'draw_id'     => $this->drawId,
            'has_winner'  => $this->hasWinner,
            'total_drawn' => $this->totalDrawn,
            'winners'     => $this->winners,
            'prize'       => $this->prize,
            'end_time'    => $this->endTime,
        ];
    }
}
