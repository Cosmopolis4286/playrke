<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaQuestionSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly int    $questionIndex,
        public readonly int    $totalQuestions,
        public readonly array  $question,  // {id, text, options[], timeLimit, category?}
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'trivia.question.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id'          => $this->gameId,
            'question_index'   => $this->questionIndex,
            'total_questions'  => $this->totalQuestions,
            'question'         => $this->question,
        ];
    }
}
