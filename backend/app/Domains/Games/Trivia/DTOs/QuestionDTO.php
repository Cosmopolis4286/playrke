<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\DTOs;

/**
 * DTO para preguntas de trivia.
 * 
 * Nota: No incluye la respuesta correcta cuando se envía a clientes.
 */
class QuestionDTO
{
    /**
     * @param array<string> $options Opciones de respuesta
     */
    public function __construct(
        public readonly string $questionId,
        public readonly string $gameId,
        public readonly int $questionNumber,
        public readonly string $questionText,
        public readonly ?string $imageUrl,
        public readonly array $options,
        public readonly int $timeLimitSeconds,
        public readonly ?string $correctAnswer = null,
        public readonly ?int $points = 100,
        public readonly ?float $serverTimestampMs = null
    ) {}

    /**
     * Reconstruir desde array serializado (ej: Redis).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            questionId:       (string) ($data['question_id'] ?? $data['id'] ?? ''),
            gameId:           (string) ($data['game_id'] ?? ''),
            questionNumber:   (int)    ($data['question_number'] ?? 1),
            questionText:     (string) ($data['question_text'] ?? $data['question'] ?? ''),
            imageUrl:         $data['image_url'] ?? $data['imagen'] ?? null,
            options:          (array)  ($data['options'] ?? []),
            timeLimitSeconds: (int)    ($data['time_limit_seconds'] ?? $data['time_limit'] ?? 15),
            correctAnswer:    $data['correct_answer'] ?? $data['answer'] ?? null,
            points:           (int)    ($data['points'] ?? 100),
            serverTimestampMs: isset($data['server_timestamp_ms']) ? (float) $data['server_timestamp_ms'] : null,
        );
    }

    /**
     * Crear desde array de base de datos.
     */
    public static function fromModel(array $data, ?string $gameId = null): self
    {
        return new self(
            questionId: (string) $data['id'],
            gameId: $gameId ?? $data['game_id'] ?? '',
            questionNumber: $data['question_number'] ?? $data['num_quest'] ?? 1,
            questionText: $data['question'] ?? $data['question_text'] ?? '',
            imageUrl: $data['imagen'] ?? $data['image_url'] ?? null,
            options: self::extractOptions($data),
            timeLimitSeconds: $data['time_limit'] ?? 15,
            correctAnswer: $data['answer'] ?? $data['correct_answer'] ?? null,
            points: $data['points'] ?? 100
        );
    }

    /**
     * Extraer opciones de diferentes formatos.
     */
    private static function extractOptions(array $data): array
    {
        // Formato con campos separados (option_1, option_2, etc.)
        if (isset($data['option_1'])) {
            $options = [];
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($data["option_{$i}"])) {
                    $options[] = $data["option_{$i}"];
                }
            }
            return $options;
        }

        // Formato JSON/array
        if (isset($data['options']) && is_array($data['options'])) {
            return $data['options'];
        }

        return [];
    }

    /**
     * Versión segura para enviar a clientes (sin respuesta correcta).
     */
    public function forClient(): array
    {
        return [
            'question_id' => $this->questionId,
            'game_id' => $this->gameId,
            'question_number' => $this->questionNumber,
            'question_text' => $this->questionText,
            'image_url' => $this->imageUrl,
            'options' => $this->options,
            'time_limit_seconds' => $this->timeLimitSeconds,
            'points' => $this->points,
        ];
    }

    /**
     * Versión completa para uso interno.
     */
    public function toArray(): array
    {
        return [
            'question_id' => $this->questionId,
            'game_id' => $this->gameId,
            'question_number' => $this->questionNumber,
            'question_text' => $this->questionText,
            'image_url' => $this->imageUrl,
            'options' => $this->options,
            'time_limit_seconds' => $this->timeLimitSeconds,
            'correct_answer' => $this->correctAnswer,
            'points' => $this->points,
            'server_timestamp_ms' => $this->serverTimestampMs,
        ];
    }

    /**
     * Verificar si una respuesta es correcta.
     */
    public function isCorrect(string $answer): bool
    {
        return strtoupper(trim($answer)) === strtoupper(trim($this->correctAnswer ?? ''));
    }
}
