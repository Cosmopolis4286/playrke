<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Events;

use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\DTOs\LeaderboardDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Juego Finalizado.
 *
 * Marca el fin de la partida con resultados finales.
 * Este evento puede disparar persistencia en base de datos
 * y notificaciones a jugadores.
 */
class GameFinished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly GameStateDTO $gameState,
        public readonly LeaderboardDTO $finalLeaderboard,
        public readonly array $winners = [], // IDs de ganadores
        public readonly array $statistics = [],
    ) {
    }

    public function broadcastAs() : string
    {
        return 'trivia.game.finished';
    }

    public function broadcastOn() : array
    {
        return [
            new Channel('trivia.room.' . $this->gameState->roomId),
            new Channel('trivia.game.' . $this->gameState->gameId),
        ];
    }

    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameState->gameId,
            'room_id' => $this->gameState->roomId,
            'final_leaderboard' => $this->finalLeaderboard->toArray(),
            'winners' => $this->winners,
            'statistics' => $this->statistics,
            'ended_at' => $this->gameState->endTime,
        ];
    }
}
