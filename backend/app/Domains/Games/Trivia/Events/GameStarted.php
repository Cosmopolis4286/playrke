<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Events;

use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Juego Iniciado.
 *
 * Este evento se dispara cuando un juego comienza y:
 * 1. Se emite a todos los jugadores vía WebSocket (Reverb)
 * 2. Se puede escuchar para persistencia en DB
 * 3. Se puede usar para logging/auditoría
 *
 * Preparado para futura integración con microservicios mediante
 * un message broker (RabbitMQ, SQS, etc.)
 */
class GameStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly GameStateDTO $gameState,
    ) {
    }

    /**
     * Nombre del evento en el cliente.
     */
    public function broadcastAs() : string
    {
        return 'trivia.game.started';
    }

    /**
     * Canal de broadcast: sala específica.
     */
    public function broadcastOn() : array
    {
        return [
            new Channel('trivia.room.' . $this->gameState->roomId),
            new Channel('trivia.game.' . $this->gameState->gameId),
        ];
    }

    /**
     * Datos a enviar a los clientes.
     */
    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameState->gameId,
            'room_id' => $this->gameState->roomId,
            'total_questions' => $this->gameState->totalQuestions,
            'player_count' => count($this->gameState->playerIds),
            'start_time' => $this->gameState->startTime,
            'status' => $this->gameState->status,
        ];
    }
}
