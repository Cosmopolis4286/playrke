<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\ScoreServiceInterface;
use App\Domains\Games\Trivia\DTOs\LeaderboardDTO;
use App\Domains\Games\Trivia\DTOs\ScoreDTO;
use Illuminate\Support\Facades\Redis;

/**
 * Servicio: Gestión de Puntuaciones.
 * 
 * Maneja:
 * - Puntuaciones en tiempo real (Redis)
 * - Leaderboard ordenado
 * - Cálculo de posiciones
 * - Estadísticas de jugadores
 */
class ScoreService implements ScoreServiceInterface
{
    private const KEY_PREFIX = 'trivia:score:';
    private const LEADERBOARD_KEY = 'trivia:leaderboard:';
    private const TTL = 3600; // 1 hora

    /**
     * Actualizar puntuación de jugador.
     */
    public function updateScore(
        string $gameId,
        string $playerId,
        int $points,
        array $metadata = []
    ): ScoreDTO {
        $key = self::KEY_PREFIX . "{$gameId}:{$playerId}";
        
        // Obtener score actual o crear nuevo
        $existingData = Redis::hget($key, 'data');
        $existing = $existingData ? json_decode($existingData, true) : null;

        $score = new ScoreDTO(
            playerId: $playerId,
            gameId: $gameId,
            totalScore: $points,
            correctAnswers: $metadata['correct_answers'] ?? ($existing['correct_answers'] ?? 0),
            wrongAnswers: $metadata['wrong_answers'] ?? ($existing['wrong_answers'] ?? 0),
            averageResponseTimeMs: $metadata['average_response_time_ms'] ?? ($existing['average_response_time_ms'] ?? 0),
            position: $existing['position'] ?? 0,
            streak: $metadata['streak'] ?? ($existing['streak'] ?? 0),
            maxStreak: $metadata['max_streak'] ?? ($existing['max_streak'] ?? 0),
            answerHistory: $existing['answer_history'] ?? []
        );

        // Guardar en Redis
        Redis::hset($key, 'data', json_encode($score->toArray()));
        Redis::expire($key, self::TTL);

        // Actualizar leaderboard ordenado
        $this->updateLeaderboardSortedSet($gameId, $playerId, $points);

        // Recalcular posiciones
        $this->recalculatePositions($gameId);

        return $score;
    }

    /**
     * Registrar respuesta correcta con tiempo.
     */
    public function recordCorrectAnswer(
        string $gameId,
        string $playerId,
        float $responseTimeMs,
        int $basePoints = 100
    ): int {
        // Fórmula: puntos base + bonus por tiempo
        // Menos tiempo = más puntos (hasta 50% extra)
        $timeBonus = max(0, 1 - ($responseTimeMs / 10000)); // Max 10 segundos
        $totalPoints = (int) ($basePoints * (1 + ($timeBonus * 0.5)));

        return min($totalPoints, $basePoints * 2); // Cap en 2x
    }

    /**
     * Obtener puntuación de jugador.
     */
    public function getPlayerScore(string $gameId, string $playerId): ScoreDTO
    {
        $key = self::KEY_PREFIX . "{$gameId}:{$playerId}";
        $data = Redis::hget($key, 'data');

        if ($data) {
            return ScoreDTO::fromArray(json_decode($data, true));
        }

        // Default si no existe
        return new ScoreDTO(
            playerId: $playerId,
            gameId: $gameId,
            totalScore: 0,
            correctAnswers: 0,
            wrongAnswers: 0,
            averageResponseTimeMs: 0,
            position: 0,
            streak: 0,
            maxStreak: 0,
            answerHistory: []
        );
    }

    /**
     * Obtener leaderboard completo.
     */
    public function getLeaderboard(string $gameId): LeaderboardDTO
    {
        // Obtener jugadores ordenados por puntuación
        $playerIds = Redis::zrevrange(
            self::LEADERBOARD_KEY . $gameId,
            0,
            -1,
            'WITHSCORES'
        );

        $scores = [];
        $position = 1;

        // playerIds viene como [id1, score1, id2, score2, ...]
        for ($i = 0; $i < count($playerIds); $i += 2) {
            $playerId = $playerIds[$i];
            $redisScore = (int) $playerIds[$i + 1];

            // Obtener datos completos del jugador
            $scoreDto = $this->getPlayerScore($gameId, $playerId);
            
            // Actualizar con posición correcta
            $scores[] = $scoreDto->withPosition($position++);
        }

        return new LeaderboardDTO(
            gameId: $gameId,
            scores: $scores,
            totalPlayers: count($scores),
            updatedAt: time(),
            isFinal: false
        );
    }

    /**
     * Obtener posición de jugador.
     */
    public function getPlayerPosition(string $gameId, string $playerId): int
    {
        $rank = Redis::zrevrank(
            self::LEADERBOARD_KEY . $gameId,
            $playerId
        );

        return $rank !== false ? $rank + 1 : 0;
    }

    /**
     * Obtener todas las puntuaciones.
     */
    public function getAllScores(string $gameId): array
    {
        $pattern = self::KEY_PREFIX . "{$gameId}:*";
        $keys = Redis::keys($pattern);

        $scores = [];
        foreach ($keys as $key) {
            $data = Redis::hget($key, 'data');
            if ($data) {
                $scores[] = ScoreDTO::fromArray(json_decode($data, true));
            }
        }

        return $scores;
    }

    /**
     * Resetear puntuaciones.
     */
    public function resetScores(string $gameId): void
    {
        // Eliminar todos los scores del juego
        $pattern = self::KEY_PREFIX . "{$gameId}:*";
        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del(...$keys);
        }

        // Eliminar leaderboard
        Redis::del(self::LEADERBOARD_KEY . $gameId);
    }

    /**
     * Actualizar sorted set del leaderboard.
     */
    private function updateLeaderboardSortedSet(string $gameId, string $playerId, int $score): void
    {
        Redis::zadd(
            self::LEADERBOARD_KEY . $gameId,
            $score,
            $playerId
        );
        Redis::expire(self::LEADERBOARD_KEY . $gameId, self::TTL);
    }

    /**
     * Recalcular posiciones de todos los jugadores.
     */
    private function recalculatePositions(string $gameId): void
    {
        $playerIds = Redis::zrevrange(
            self::LEADERBOARD_KEY . $gameId,
            0,
            -1
        );

        foreach ($playerIds as $index => $playerId) {
            $position = $index + 1;
            $key = self::KEY_PREFIX . "{$gameId}:{$playerId}";
            
            $data = Redis::hget($key, 'data');
            if ($data) {
                $scoreData = json_decode($data, true);
                $scoreData['position'] = $position;
                Redis::hset($key, 'data', json_encode($scoreData));
            }
        }
    }
}
