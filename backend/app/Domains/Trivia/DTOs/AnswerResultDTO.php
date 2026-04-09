<?php

declare(strict_types=1);

namespace App\Domains\Trivia\DTOs;

/**
 * DTO para resultado de una respuesta.
 */
class AnswerResultDTO
{
    public function __construct(
        public readonly string $answerId,
        public readonly string $gameId,
        public readonly string $playerId,
        public readonly string $questionId,
        public readonly string $submittedAnswer,
        public readonly bool $isCorrect,
        public readonly int $pointsEarned,
        public readonly float $responseTimeMs,
        public readonly int $currentScore,
        public readonly int $currentPosition,
        public readonly ?string $correctAnswer = null,
        public readonly ?string $explanation = null
    ) {}

    /**
     * Crear resultado desde datos de validación.
     */
    public static function create(
        string $gameId,
        string $playerId,
        string $questionId,
        string $submittedAnswer,
        bool $isCorrect,
        int $basePoints,
        float $responseTimeMs,
        int $currentScore,
        int $currentPosition,
        ?string $correctAnswer = null
    ): self {
        // Calcular puntos basado en tiempo de respuesta
        $pointsEarned = $isCorrect ? self::calculatePoints($basePoints, $responseTimeMs) : 0;

        return new self(
            answerId: uniqid('ans_', true),
            gameId: $gameId,
            playerId: $playerId,
            questionId: $questionId,
            submittedAnswer: $submittedAnswer,
            isCorrect: $isCorrect,
            pointsEarned: $pointsEarned,
            responseTimeMs: $responseTimeMs,
            currentScore: $currentScore + $pointsEarned,
            currentPosition: $currentPosition,
            correctAnswer: $isCorrect ? null : $correctAnswer,
            explanation: null
        );
    }

    /**
     * Calcular puntos basado en tiempo de respuesta.
     * Más rápido = más puntos.
     */
    private static function calculatePoints(int $basePoints, float $responseTimeMs): int
    {
        $timeBonus = max(0, 1 - ($responseTimeMs / 10000)); // Bonus por rapidez (max 10s)
        return (int) ($basePoints * (0.5 + ($timeBonus * 0.5)));
    }

    /**
     * Convertir a array para broadcasting.
     */
    public function toArray(bool $includeCorrectAnswer = false): array
    {
        $data = [
            'answer_id' => $this->answerId,
            'game_id' => $this->gameId,
            'player_id' => $this->playerId,
            'question_id' => $this->questionId,
            'submitted_answer' => $this->submittedAnswer,
            'is_correct' => $this->isCorrect,
            'points_earned' => $this->pointsEarned,
            'response_time_ms' => $this->responseTimeMs,
            'current_score' => $this->currentScore,
            'current_position' => $this->currentPosition,
        ];

        if ($includeCorrectAnswer) {
            $data['correct_answer'] = $this->correctAnswer;
            $data['explanation'] = $this->explanation;
        }

        return $data;
    }

    /**
     * Respuesta para el jugador que respondió.
     */
    public function forPlayer(): array
    {
        return $this->toArray(true);
    }

    /**
     * Respuesta para otros jugadores (sin revelar respuesta correcta todavía).
     */
    public function forBroadcast(): array
    {
        return [
            'answer_id' => $this->answerId,
            'game_id' => $this->gameId,
            'player_id' => $this->playerId,
            'question_id' => $this->questionId,
            'is_correct' => $this->isCorrect,
            'points_earned' => $this->pointsEarned,
            'current_score' => $this->currentScore,
            'current_position' => $this->currentPosition,
        ];
    }
}
