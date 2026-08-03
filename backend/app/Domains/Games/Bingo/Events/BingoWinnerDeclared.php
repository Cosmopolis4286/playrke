<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
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
        Log::info('Creando evento BingoWinnerDeclared', [
            'roomId' => $roomId,
            'winnerId' => $winnerId,
            'winnerName' => $winnerName,
            'prize' => $prize
        ]);

        $this->roomId = $roomId;
        $this->drawId = $drawId;
        $this->winnerId = $winnerId;
        $this->winnerName = $winnerName;
        $this->cardId = $cardId;
        $this->pattern = $pattern;
        $this->prize = $prize;
    }

    /**
     * Además del canal por sala (consumido por los jugadores en juego), se
     * publica en `admins.activity` para alimentar el feed de actividad
     * reciente del dashboard de Admin/SuperAdmin.
     */
    public function broadcastOn()
    {
        return [
            new Channel("bingo.room.{$this->roomId}"),
            new PrivateChannel('admins.activity'),
        ];
    }

    public function broadcastAs()
    {
        return 'WinnerDeclared';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'draw_id' => $this->drawId,
            'winner' => [
                'id'      => $this->winnerId,
                'name'    => $this->winnerName,
                'card_id' => (string) $this->cardId,
                'pattern' => $this->pattern,
                'prize'   => $this->prize,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
