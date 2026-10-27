<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaPlayerJoined implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $playerId,
        public readonly string $playerName,
        public readonly int $currentPlayerCount,
        public readonly ?string $teamId = null,
    ) {
    }

    public function broadcastAs() : string
    {
        return 'trivia.player.joined';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'player' => [
                'id' => $this->playerId,
                'name' => $this->playerName,
                'teamId' => $this->teamId,
            ],
            'current_player_count' => $this->currentPlayerCount,
        ];
    }
}
