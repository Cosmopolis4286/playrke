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
 *
 * Diseño de claves:
 *   trivia:score:{gameId}:{playerId}  → hash con campo 'data' (JSON ScoreDTO sin position)
 *   trivia:leaderboard:{gameId}       → sorted set score → playerId
 *
 * Position se deriva siempre de ZREVRANK en tiempo real — no se almacena en el hash
 * para evitar el bucle N+1 de recalculatePositions (2N round-trips por respuesta).
 */
class ScoreService implements ScoreServiceInterface
{
    private const KEY_PREFIX     = 'trivia:score:';
    private const LEADERBOARD_KEY = 'trivia:leaderboard:';
    private const TTL = 3600; // 1 hora

    /**
     * Actualizar puntuación de jugador.
     *
     * Antes: updateScore llamaba recalculatePositions() que hacía 2N round-trips
     * a Redis (hget + hset por cada jugador en el sorted set) en cada respuesta.
     * Ahora: solo 3 round-trips totales independientemente del número de jugadores
     * (hget, hset+expire, zadd+expire).
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
        $existing     = $existingData ? json_decode($existingData, true) : null;

        $score = new ScoreDTO(
            playerId: $playerId,
            gameId: $gameId,
            totalScore: $points,
            correctAnswers: $metadata['correct_answers'] ?? ($existing['correct_answers'] ?? 0),
            wrongAnswers: $metadata['wrong_answers'] ?? ($existing['wrong_answers'] ?? 0),
            averageResponseTimeMs: $metadata['average_response_time_ms'] ?? ($existing['average_response_time_ms'] ?? 0),
            // position se deriva de ZREVRANK en el sorted set — no se persiste en el hash
            // para evitar el bucle N+1 de recalculatePositions que antes se ejecutaba aquí.
            position: 0,
            streak: $metadata['streak'] ?? ($existing['streak'] ?? 0),
            maxStreak: $metadata['max_streak'] ?? ($existing['max_streak'] ?? 0),
            answerHistory: $existing['answer_history'] ?? []
        );

        // Guardar score (sin position — se calcula en lectura)
        Redis::pipeline(function ($pipe) use ($key, $score, $gameId, $playerId, $points) {
            $pipe->hset($key, 'data', json_encode($score->toArray()));
            $pipe->expire($key, self::TTL);
            $pipe->zadd(self::LEADERBOARD_KEY . $gameId, $points, $playerId);
            $pipe->expire(self::LEADERBOARD_KEY . $gameId, self::TTL);
        });

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
        $timeBonus   = max(0, 1 - ($responseTimeMs / 10000));
        $totalPoints = (int) ($basePoints * (1 + ($timeBonus * 0.5)));

        return min($totalPoints, $basePoints * 2);
    }

    /**
     * Obtener puntuación de jugador.
     * Position se asigna desde ZREVRANK — O(log N), un solo round-trip.
     */
    public function getPlayerScore(string $gameId, string $playerId): ScoreDTO
    {
        $key  = self::KEY_PREFIX . "{$gameId}:{$playerId}";
        $data = Redis::hget($key, 'data');

        if ($data) {
            $dto = ScoreDTO::fromArray(json_decode($data, true));
            return $dto->withPosition($this->getPlayerPosition($gameId, $playerId));
        }

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
     *
     * Antes: 1 zrevrange + N hget secuenciales = N+1 round-trips.
     * Ahora: 1 zrevrange + 1 pipeline de N hget = 2 round-trips totales.
     */
    public function getLeaderboard(string $gameId): LeaderboardDTO
    {
        $playerIds = Redis::zrevrange(self::LEADERBOARD_KEY . $gameId, 0, -1);

        if (empty($playerIds)) {
            return new LeaderboardDTO(
                gameId: $gameId,
                scores: [],
                totalPlayers: 0,
                updatedAt: time(),
                isFinal: false
            );
        }

        // Pipeline: un solo round-trip para todos los hget
        $rawScores = Redis::pipeline(function ($pipe) use ($gameId, $playerIds) {
            foreach ($playerIds as $playerId) {
                $pipe->hget(self::KEY_PREFIX . "{$gameId}:{$playerId}", 'data');
            }
        });

        $scores = [];
        foreach ($playerIds as $i => $playerId) {
            $raw = $rawScores[$i] ?? null;
            $dto = $raw
                ? ScoreDTO::fromArray(json_decode($raw, true))
                : new ScoreDTO(
                    playerId: (string) $playerId,
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

            $scores[] = $dto->withPosition($i + 1);
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
     * Obtener posición de jugador — O(log N), un solo round-trip.
     */
    public function getPlayerPosition(string $gameId, string $playerId): int
    {
        $rank = Redis::zrevrank(self::LEADERBOARD_KEY . $gameId, $playerId);

        return $rank !== false ? $rank + 1 : 0;
    }

    /**
     * Obtener todas las puntuaciones.
     *
     * Antes: Redis::keys() — O(N) bloqueante sobre todo el keyspace.
     * Ahora: Redis::scan() con cursor — no bloqueante, O(1) por iteración.
     */
    public function getAllScores(string $gameId): array
    {
        $pattern = self::KEY_PREFIX . "{$gameId}:*";
        $keys    = $this->scanKeys($pattern);

        if (empty($keys)) {
            return [];
        }

        $rawScores = Redis::pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $key) {
                $pipe->hget($key, 'data');
            }
        });

        $scores = [];
        foreach ($rawScores as $raw) {
            if ($raw) {
                $scores[] = ScoreDTO::fromArray(json_decode($raw, true));
            }
        }

        return $scores;
    }

    /**
     * Resetear puntuaciones.
     *
     * Antes: Redis::keys() bloqueante + del individual.
     * Ahora: scan no bloqueante + del en pipeline.
     */
    public function resetScores(string $gameId): void
    {
        $keys = $this->scanKeys(self::KEY_PREFIX . "{$gameId}:*");

        Redis::pipeline(function ($pipe) use ($keys, $gameId) {
            foreach ($keys as $key) {
                $pipe->del($key);
            }
            $pipe->del(self::LEADERBOARD_KEY . $gameId);
        });
    }

    /**
     * Actualizar sorted set del leaderboard.
     * Llamado internamente por updateScore (ya dentro de pipeline).
     */
    private function updateLeaderboardSortedSet(string $gameId, string $playerId, int $score): void
    {
        Redis::zadd(self::LEADERBOARD_KEY . $gameId, $score, $playerId);
        Redis::expire(self::LEADERBOARD_KEY . $gameId, self::TTL);
    }

    /**
     * Escanear claves por patrón sin bloquear Redis.
     * Reemplaza Redis::keys() (O(N) bloqueante) con SCAN iterativo (O(1) por tick).
     *
     * @return string[]
     */
    private function scanKeys(string $pattern): array
    {
        $keys   = [];
        $cursor = '0';

        do {
            [$cursor, $found] = Redis::scan($cursor, 'match', $pattern, 'count', 100);
            $keys = array_merge($keys, $found);
        } while ($cursor !== '0');

        return $keys;
    }
}
