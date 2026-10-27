<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica que una partida pausada se reanudó. El cliente debe recalcular
 * su timer local — el servidor ya corrió server_timestamp_ms hacia
 * adelante por la duración de la pausa (ver TriviaPauseService::resume()),
 * así que el tiempo restante real de la pregunta activa es
 * `remaining_seconds`, no lo que el cliente tenía guardado antes de pausar.
 */
class TriviaGameResumed implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly int $remainingSeconds,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'trivia.game.resumed';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameId,
            'remaining_seconds' => $this->remainingSeconds,
        ];
    }
}
