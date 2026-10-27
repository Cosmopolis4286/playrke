<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TriviaQuestionSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public readonly int $serverTimestamp;

    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly int $questionIndex,
        public readonly int $totalQuestions,
        public readonly array $question,
    ) {
        // Timestamp en milisegundos — el frontend lo usa para calcular el tiempo
        // transcurrido desde que el servidor envió la pregunta (lag de red).
        $this->serverTimestamp = (int) round(microtime(true) * 1000);
    }

    public function broadcastAs() : string
    {
        return 'trivia.question.sent';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameId,
            'question_index' => $this->questionIndex,
            'total_questions' => $this->totalQuestions,
            'question' => $this->question,
            'server_timestamp' => $this->serverTimestamp,
        ];
    }
}
