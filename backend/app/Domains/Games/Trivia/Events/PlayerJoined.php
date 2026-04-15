<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Events;

use App\Domains\Games\Trivia\DTOs\PlayerDTO;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de dominio: Jugador Unido a Sala.
 * 
 * Notifica a todos en la sala cuando un nuevo jugador se une.
 */
class PlayerJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PlayerDTO $player,
        public readonly string $roomId,
        public readonly int $currentPlayerCount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trivia.room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'trivia.player.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'player' => $this->player->toArray(),
            'current_player_count' => $this->currentPlayerCount,
        ];
    }
}
