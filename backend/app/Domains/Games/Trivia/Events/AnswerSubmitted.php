<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Events;

use App\Domains\Trivia\DTOs\AnswerResultDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Respuesta Enviada.
 * 
 * Se dispara cuando un jugador envía una respuesta.
 * Emite a todos para actualizar leaderboard en tiempo real.
 */
class AnswerSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AnswerResultDTO $result
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trivia.game.' . $this->result->gameId),
            // Canal privado para el jugador específico (con respuesta correcta)
            new Channel('trivia.player.' . $this->result->playerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trivia.answer.submitted';
    }

    /**
     * Datos para broadcast público (sin revelar respuesta correcta).
     */
    public function broadcastWith(): array
    {
        return $this->result->forBroadcast();
    }

    /**
     * Datos para el jugador que respondió.
     */
    public function forPlayer(): array
    {
        return $this->result->forPlayer();
    }
}
