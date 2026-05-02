<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaGameStarted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roomCode,
        public readonly string $gameId,
        public readonly int    $totalQuestions,
        public readonly int    $timePerQuestion,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'trivia.game.started';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id'           => $this->gameId,
            'room_code'         => $this->roomCode,
            'total_questions'   => $this->totalQuestions,
            'time_per_question' => $this->timePerQuestion,
        ];
    }
}
