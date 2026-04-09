<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Events;

use App\Domains\Trivia\DTOs\QuestionDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Pregunta Enviada.
 * 
 * Se emite cuando una nueva pregunta está disponible para los jugadores.
 * Incluye temporizador para sincronización de tiempo de respuesta.
 */
class QuestionSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly QuestionDTO $question,
        public readonly string $gameId,
        public readonly int $serverTimestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trivia.game.' . $this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trivia.question.sent';
    }

    /**
     * Datos enviados a clientes (sin respuesta correcta).
     */
    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'question' => $this->question->forClient(),
            'server_timestamp' => $this->serverTimestamp,
            'time_limit_seconds' => $this->question->timeLimitSeconds,
        ];
    }
}
