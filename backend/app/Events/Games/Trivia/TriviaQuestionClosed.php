<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * La pregunta dejó de aceptar respuestas (todos respondieron o venció el
 * tiempo). Revela a toda la sala la respuesta correcta y la distribución de
 * respuestas; la siguiente pregunta llega en next_question_at_ms.
 */
class TriviaQuestionClosed implements ShouldBroadcastNow
{
    use SerializesModels;

    public readonly int $serverTimestamp;

    /**
     * @param  array<int, int>  $distribution
     */
    public function __construct(
        public readonly string $roomId,
        public readonly string $gameId,
        public readonly string $questionId,
        public readonly int $questionIndex,
        public readonly int $totalQuestions,
        public readonly int $correctOptionIndex,
        public readonly array $distribution,
        public readonly int $answeredCount,
        public readonly int $playerCount,
        public readonly int $revealSeconds,
        public readonly string $reason,
    ) {
        $this->serverTimestamp = (int) round(microtime(true) * 1000);
    }

    public function broadcastAs() : string
    {
        return 'trivia.question.closed';
    }

    public function broadcastOn() : Channel
    {
        return new Channel("trivia.room.{$this->roomId}");
    }

    public function broadcastWith() : array
    {
        return [
            'game_id' => $this->gameId,
            'question_id' => $this->questionId,
            'question_index' => $this->questionIndex,
            'total_questions' => $this->totalQuestions,
            'is_last_question' => $this->questionIndex >= $this->totalQuestions,
            'correct_option_index' => $this->correctOptionIndex,
            'distribution' => $this->distribution,
            'answered_count' => $this->answeredCount,
            'player_count' => $this->playerCount,
            'reveal_seconds' => $this->revealSeconds,
            'next_question_at_ms' => $this->serverTimestamp + $this->revealSeconds * 1000,
            'reason' => $this->reason,
            'server_timestamp' => $this->serverTimestamp,
        ];
    }
}
