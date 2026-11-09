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
        /**
         * Posición de la ficha en la ronda (1 = primera). Con ella el cliente
         * detecta si se perdió una ficha sin haberse desconectado y se
         * resincroniza por HTTP (draw-state).
         */
        public readonly ?int $index = null,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'BingoNumberCalled';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return $this->index === null
            ? ['number' => $this->number]
            : ['number' => $this->number, 'index' => $this->index];
    }
}
