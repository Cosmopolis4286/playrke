<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaPlayerLeft implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $playerId,
        public readonly int $currentPlayerCount,
        public readonly string $reason = 'left',
    ) {
    }

    public function broadcastAs() : string
    {
        return 'trivia.player.left';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'player_id' => $this->playerId,
            'current_player_count' => $this->currentPlayerCount,
            'reason' => $this->reason,
        ];
    }
}
