<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\DTOs;

/**
 * Data Transfer Object para estado del juego.
 * 
 * Este DTO es serializable y puede usarse en:
 * - Comunicación interna entre servicios
 * - Broadcasting via WebSockets
 * - Futuros microservicios (JSON)
 */
class GameStateDTO
{
    /**
     * @param array<string> $playerIds IDs de jugadores participantes
     * @param array<PlayerScoreDTO> $scores Puntuaciones actuales
     * @param array<string, mixed> $metadata Datos adicionales del juego
     */
    public function __construct(
        public readonly string $gameId,
        public readonly string $roomId,
        public readonly string $status,
        public readonly int $currentQuestionIndex,
        public readonly int $totalQuestions,
        public readonly ?int $startTime,
        public readonly ?int $endTime,
        public readonly array $playerIds = [],
        public readonly array $scores = [],
        public readonly array $metadata = []
    ) {}

    /**
     * Crear DTO desde array (útil para Redis/cache).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            gameId: $data['game_id'],
            roomId: $data['room_id'],
            status: $data['status'],
            currentQuestionIndex: $data['current_question_index'] ?? 0,
            totalQuestions: $data['total_questions'] ?? 0,
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            playerIds: $data['player_ids'] ?? [],
            scores: $data['scores'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convertir a array para almacenamiento.
     */
    public function toArray(): array
    {
        return [
            'game_id' => $this->gameId,
            'room_id' => $this->roomId,
            'status' => $this->status,
            'current_question_index' => $this->currentQuestionIndex,
            'total_questions' => $this->totalQuestions,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'player_ids' => $this->playerIds,
            'scores' => $this->scores,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Verificar si el juego está activo.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verificar si el juego ha terminado.
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['finished', 'cancelled'], true);
    }

    /**
     * Avanzar a la siguiente pregunta.
     */
    public function advanceQuestion(): self
    {
        return new self(
            gameId: $this->gameId,
            roomId: $this->roomId,
            status: $this->status,
            currentQuestionIndex: $this->currentQuestionIndex + 1,
            totalQuestions: $this->totalQuestions,
            startTime: $this->startTime,
            endTime: $this->endTime,
            playerIds: $this->playerIds,
            scores: $this->scores,
            metadata: $this->metadata
        );
    }

    /**
     * Cambiar estado del juego.
     */
    public function withStatus(string $status): self
    {
        return new self(
            gameId: $this->gameId,
            roomId: $this->roomId,
            status: $status,
            currentQuestionIndex: $this->currentQuestionIndex,
            totalQuestions: $this->totalQuestions,
            startTime: $this->startTime,
            endTime: $status === 'finished' ? time() : $this->endTime,
            playerIds: $this->playerIds,
            scores: $this->scores,
            metadata: $this->metadata
        );
    }
}
