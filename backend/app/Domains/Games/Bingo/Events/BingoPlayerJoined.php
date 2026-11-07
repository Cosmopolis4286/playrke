<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BingoPlayerJoined implements ShouldBroadcastNow
{
    use SerializesModels;

    public $currentPlayers;
    public $playerId;
    public $playerName;
    public $roomId;

    public function __construct(int $roomId, int $playerId, string $playerName, int $currentPlayers)
    {
        // El canal es público: no registrar ni transmitir datos identificables.
        $this->roomId = $roomId;
        $this->playerId = $playerId;
        $this->playerName = $playerName;
        $this->currentPlayers = $currentPlayers;
    }

    public function broadcastAs()
    {
        return 'PlayerJoined';
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'current_players' => $this->currentPlayers,
            'timestamp' => now()->toISOString(),
        ];
    }
}
