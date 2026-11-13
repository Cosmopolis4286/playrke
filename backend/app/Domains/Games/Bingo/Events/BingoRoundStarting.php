<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Terminó una ronda y la siguiente empieza en `startsAt` (pausa entre rondas,
 * de la sala, o config('bingo.round_pause_seconds') si no tiene): el cliente muestra la cuenta regresiva.
 */
class BingoRoundStarting implements ShouldBroadcastNow
{
    public function __construct(
        public readonly int $roomId,
        public readonly int $round,
        public readonly string $startsAt,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'RoundStarting';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return ['round' => $this->round, 'starts_at' => $this->startsAt];
    }
}
