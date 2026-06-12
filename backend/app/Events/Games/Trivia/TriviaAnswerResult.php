<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Enviado al canal privado de cada jugador con el resultado de su respuesta.
 */
class TriviaAnswerResult implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $playerId,
        public readonly string $gameId,
        public readonly string $questionId,
        public readonly bool   $isCorrect,
        public readonly int    $pointsEarned,
        public readonly int    $correctOptionIndex,
        public readonly int    $currentScore,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("trivia.player.{$this->playerId}");
    }

    public function broadcastAs(): string
    {
        return 'trivia.answer.result';
    }

    public function broadcastWith(): array
    {
        return [
            'game_id'             => $this->gameId,
            'question_id'         => $this->questionId,
            'is_correct'          => $this->isCorrect,
            'points_earned'       => $this->pointsEarned,
            'correct_option_index'=> $this->correctOptionIndex,
            'current_score'       => $this->currentScore,
        ];
    }
}
