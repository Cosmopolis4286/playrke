<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BingoPlayerJoined implements ShouldBroadcastNow
{
    use SerializesModels;

    public $roomId;
    public $playerId;
    public $playerName;
    public $currentPlayers;

    public function __construct(int $roomId, int $playerId, string $playerName, int $currentPlayers)
    {
        Log::info('Creando evento BingoPlayerJoined', [
            'roomId' => $roomId,
            'playerId' => $playerId,
            'playerName' => $playerName
        ]);

        $this->roomId = $roomId;
        $this->playerId = $playerId;
        $this->playerName = $playerName;
        $this->currentPlayers = $currentPlayers;
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'PlayerJoined';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'player' => [
                'id' => $this->playerId,
                'name' => $this->playerName,
            ],
            'current_players' => $this->currentPlayers,
            'timestamp' => now()->toISOString(),
        ];
    }
}
