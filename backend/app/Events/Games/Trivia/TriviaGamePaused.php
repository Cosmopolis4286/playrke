<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaGamePaused implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly string $reason,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'trivia.game.paused';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id' => $this->gameId,
            'reason'  => $this->reason,
        ];
    }
}
