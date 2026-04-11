<?php

declare(strict_types=1);

namespace App\Domains\Trivia\DTOs;

/**
 * DTO para leaderboard/tabla de posiciones.
 */
class LeaderboardDTO
{
    /**
     * @param array<ScoreDTO> $scores
     */
    public function __construct(
        public readonly string $gameId,
        public readonly array $scores,
        public readonly int $totalPlayers,
        public readonly int $updatedAt,
        public readonly bool $isFinal = false
    ) {}

    public static function fromArray(array $data): self
    {
        $scores = array_map(
            fn($score) => ScoreDTO::fromArray($score),
            $data['scores'] ?? []
        );

        return new self(
            gameId: $data['game_id'],
            scores: $scores,
            totalPlayers: $data['total_players'] ?? count($scores),
            updatedAt: $data['updated_at'] ?? time(),
            isFinal: $data['is_final'] ?? false
        );
    }

    public function toArray(): array
    {
        return [
            'game_id' => $this->gameId,
            'scores' => array_map(fn($s) => $s->toArray(), $this->scores),
            'total_players' => $this->totalPlayers,
            'updated_at' => $this->updatedAt,
            'is_final' => $this->isFinal,
        ];
    }

    /**
     * Obtener top N jugadores.
     * @return array<ScoreDTO>
     */
    public function getTop(int $n): array
    {
        return array_slice($this->scores, 0, $n);
    }

    /**
     * Buscar posición de un jugador específico.
     */
    public function getPlayerPosition(string $playerId): ?int
    {
        foreach ($this->scores as $index => $score) {
            if ($score->playerId === $playerId) {
                return $index + 1;
            }
        }
        return null;
    }

    /**
     * Obtener score de un jugador específico.
     */
    public function getPlayerScore(string $playerId): ?ScoreDTO
    {
        foreach ($this->scores as $score) {
            if ($score->playerId === $playerId) {
                return $score;
            }
        }
        return null;
    }
}
