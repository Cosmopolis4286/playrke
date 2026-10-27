<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaGameFinished implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly array $leaderboard, // [{player_id, name, score, position}]
    ) {
    }

    public function broadcastAs() : string
    {
        return 'trivia.game.finished';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameId,
            'leaderboard' => $this->leaderboard,
        ];
    }
}
