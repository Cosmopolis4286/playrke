<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoWinnerDeclared implements ShouldBroadcastNow
{
    use SerializesModels;

    public $roomId;
    public $drawId;
    public $winnerId;
    public $winnerName;
    public $cardId;
    public $pattern;
    public $prize;

    public function __construct(
        int $roomId,
        int $drawId,
        int $winnerId,
        string $winnerName,
        int $cardId,
        string $pattern,
        float $prize
    ) {
        // Do not write personal winner/card data to logs unnecessarily.
        Log::info('Creando evento BingoWinnerDeclared', [
            'roomId' => $roomId,
            'drawId' => $drawId,
        ]);

        $this->roomId = $roomId;
        $this->drawId = $drawId;
        $this->winnerId = $winnerId;
        $this->winnerName = $winnerName;
        $this->cardId = $cardId;
        $this->pattern = $pattern;
        $this->prize = $prize;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'WinnerDeclared';
    }

    /**
     * This channel is public, so announce only that a winner was declared.
     * The winner identity, card, pattern, and prize are returned only by the
     * authorized room-scoped result endpoint.
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'draw_id' => $this->drawId,
            'timestamp' => now()->toISOString(),
        ];
    }
}
