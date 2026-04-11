<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Repositories;

use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use Illuminate\Support\Facades\Redis;

/**
 * Implementación Redis del repositorio de estado de juego.
 * 
 * Almacena estado temporal en Redis para alta velocidad
 * y baja latencia en operaciones de tiempo real.
 */
class RedisGameStateRepository implements GameStateRepositoryInterface
{
    private const KEY_PREFIX = 'trivia:game:';
    private const DEFAULT_TTL = 3600; // 1 hora

    /**
     * Guardar estado del juego en Redis.
     */
    public function saveState(string $gameId, GameStateDTO $state, ?int $ttl = null): void
    {
        $key = $this->key($gameId);
        
        Redis::hset($key, 'data', json_encode($state->toArray()));
        Redis::hset($key, 'updated_at', time());
        
        Redis::expire($key, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Obtener estado del juego desde Redis.
     */
    public function getState(string $gameId): ?GameStateDTO
    {
        $key = $this->key($gameId);
        
        $data = Redis::hget($key, 'data');
        
        if (!$data) {
            return null;
        }

        $decoded = json_decode($data, true);
        
        if (!$decoded) {
            return null;
        }

        return GameStateDTO::fromArray($decoded);
    }

    /**
     * Verificar si existe estado.
     */
    public function exists(string $gameId): bool
    {
        return (bool) Redis::exists($this->key($gameId));
    }

    /**
     * Eliminar estado del juego.
     */
    public function deleteState(string $gameId): void
    {
        Redis::del($this->key($gameId));
    }

    /**
     * Extender tiempo de vida.
     */
    public function extendTtl(string $gameId, int $ttl): void
    {
        Redis::expire($this->key($gameId), $ttl);
    }

    /**
     * Actualizar campo específico.
     */
    public function updateField(string $gameId, string $field, mixed $value): void
    {
        $state = $this->getState($gameId);
        
        if (!$state) {
            return;
        }

        $data = $state->toArray();
        $data[$field] = $value;
        
        $newState = GameStateDTO::fromArray($data);
        $this->saveState($gameId, $newState);
    }

    /**
     * Obtener campo específico.
     */
    public function getField(string $gameId, string $field): mixed
    {
        $state = $this->getState($gameId);
        
        if (!$state) {
            return null;
        }

        return $state->toArray()[$field] ?? null;
    }

    /**
     * Guardar puntuación de jugador en tiempo real.
     */
    public function savePlayerScore(string $gameId, string $playerId, array $scoreData): void
    {
        $key = self::KEY_PREFIX . "score:{$gameId}:{$playerId}";
        
        Redis::hset($key, 'data', json_encode($scoreData));
        Redis::expire($key, self::DEFAULT_TTL);
    }

    /**
     * Obtener puntuación de jugador.
     */
    public function getPlayerScore(string $gameId, string $playerId): ?array
    {
        $key = self::KEY_PREFIX . "score:{$gameId}:{$playerId}";
        $data = Redis::hget($key, 'data');
        
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Obtener todas las puntuaciones de un juego.
     */
    public function getAllScores(string $gameId): array
    {
        $pattern = self::KEY_PREFIX . "score:{$gameId}:*";
        $keys = Redis::keys($pattern);
        
        $scores = [];
        foreach ($keys as $key) {
            $data = Redis::hget($key, 'data');
            if ($data) {
                $scores[] = json_decode($data, true);
            }
        }
        
        return $scores;
    }

    /**
     * Guardar pregunta actual.
     */
    public function saveCurrentQuestion(string $gameId, array $questionData): void
    {
        $key = self::KEY_PREFIX . "question:{$gameId}";
        
        Redis::hset($key, 'data', json_encode($questionData));
        Redis::hset($key, 'sent_at', time());
        Redis::expire($key, self::DEFAULT_TTL);
    }

    /**
     * Obtener pregunta actual.
     */
    public function getCurrentQuestion(string $gameId): ?array
    {
        $key = self::KEY_PREFIX . "question:{$gameId}";
        $data = Redis::hget($key, 'data');
        
        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Generar key Redis.
     */
    private function key(string $gameId): string
    {
        return self::KEY_PREFIX . $gameId;
    }
}
