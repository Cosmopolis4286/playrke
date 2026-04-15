<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\DTOs;

/**
 * DTO para puntuaciones individuales.
 */
class ScoreDTO
{
    public function __construct(
        public readonly string $playerId,
        public readonly string $gameId,
        public readonly int $totalScore,
        public readonly int $correctAnswers,
        public readonly int $wrongAnswers,
        public readonly float $averageResponseTimeMs,
        public readonly int $position,
        public readonly int $streak, // respuestas correctas consecutivas
        public readonly int $maxStreak,
        public readonly array $answerHistory = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            playerId: $data['player_id'],
            gameId: $data['game_id'],
            totalScore: $data['total_score'] ?? 0,
            correctAnswers: $data['correct_answers'] ?? 0,
            wrongAnswers: $data['wrong_answers'] ?? 0,
            averageResponseTimeMs: $data['average_response_time_ms'] ?? 0,
            position: $data['position'] ?? 0,
            streak: $data['streak'] ?? 0,
            maxStreak: $data['max_streak'] ?? 0,
            answerHistory: $data['answer_history'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'player_id' => $this->playerId,
            'game_id' => $this->gameId,
            'total_score' => $this->totalScore,
            'correct_answers' => $this->correctAnswers,
            'wrong_answers' => $this->wrongAnswers,
            'average_response_time_ms' => $this->averageResponseTimeMs,
            'position' => $this->position,
            'streak' => $this->streak,
            'max_streak' => $this->maxStreak,
            'answer_history' => $this->answerHistory,
        ];
    }

    public function addPoints(int $points, bool $isCorrect, float $responseTimeMs): self
    {
        $newCorrect = $isCorrect ? $this->correctAnswers + 1 : $this->correctAnswers;
        $newWrong = !$isCorrect ? $this->wrongAnswers + 1 : $this->wrongAnswers;
        $newStreak = $isCorrect ? $this->streak + 1 : 0;
        $newMaxStreak = max($this->maxStreak, $newStreak);

        $totalAnswers = $newCorrect + $newWrong;
        $newAvgTime = $totalAnswers > 0
            ? (($this->averageResponseTimeMs * ($totalAnswers - 1)) + $responseTimeMs) / $totalAnswers
            : $responseTimeMs;

        return new self(
            playerId: $this->playerId,
            gameId: $this->gameId,
            totalScore: $this->totalScore + $points,
            correctAnswers: $newCorrect,
            wrongAnswers: $newWrong,
            averageResponseTimeMs: $newAvgTime,
            position: $this->position,
            streak: $newStreak,
            maxStreak: $newMaxStreak,
            answerHistory: [...$this->answerHistory, [
                'is_correct' => $isCorrect,
                'points' => $points,
                'response_time_ms' => $responseTimeMs,
            ]]
        );
    }

    public function withPosition(int $position): self
    {
        return new self(
            playerId: $this->playerId,
            gameId: $this->gameId,
            totalScore: $this->totalScore,
            correctAnswers: $this->correctAnswers,
            wrongAnswers: $this->wrongAnswers,
            averageResponseTimeMs: $this->averageResponseTimeMs,
            position: $position,
            streak: $this->streak,
            maxStreak: $this->maxStreak,
            answerHistory: $this->answerHistory
        );
    }
}
