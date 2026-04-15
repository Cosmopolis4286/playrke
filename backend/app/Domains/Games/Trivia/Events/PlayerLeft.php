<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Jugador Abandonó Sala.
 */
class PlayerLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $playerId,
        public readonly string $roomId,
        public readonly int $currentPlayerCount,
        public readonly ?string $reason = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trivia.room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trivia.player.left';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'player_id' => $this->playerId,
            'current_player_count' => $this->currentPlayerCount,
            'reason' => $this->reason,
        ];
    }
}
