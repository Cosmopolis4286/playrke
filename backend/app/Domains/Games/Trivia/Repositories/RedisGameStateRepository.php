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
    private const DEFAULT_TTL = 3600; // 1 hora
    private const KEY_PREFIX = 'trivia:game:';

    /**
     * Eliminar estado del juego.
     */
    public function deleteState(string $gameId) : void
    {
        Redis::del($this->key($gameId));
    }

    /**
     * Verificar si existe estado.
     */
    public function exists(string $gameId) : bool
    {
        return (bool) Redis::exists($this->key($gameId));
    }

    /**
     * Extender tiempo de vida.
     */
    public function extendTtl(string $gameId, int $ttl) : void
    {
        Redis::expire($this->key($gameId), $ttl);
    }

    /**
     * Obtener pregunta actual.
     */
    public function getCurrentQuestion(string $gameId) : ?array
    {
        $key = self::KEY_PREFIX . "question:{$gameId}";
        $data = Redis::hget($key, 'data');

        if (! $data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Obtener campo específico.
     */
    public function getField(string $gameId, string $field) : mixed
    {
        $state = $this->getState($gameId);

        if (! $state) {
            return null;
        }

        return $state->toArray()[$field] ?? null;
    }

    /**
     * Obtener estado del juego desde Redis.
     */
    public function getState(string $gameId) : ?GameStateDTO
    {
        $key = $this->key($gameId);

        $data = Redis::hget($key, 'data');

        if (! $data) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (! $decoded) {
            return null;
        }

        return GameStateDTO::fromArray($decoded);
    }

    /**
     * Guardar pregunta actual.
     */
    public function saveCurrentQuestion(string $gameId, array $questionData) : void
    {
        $key = self::KEY_PREFIX . "question:{$gameId}";

        Redis::pipeline(function($pipe) use ($key, $questionData) {
            $pipe->hset($key, 'data', json_encode($questionData));
            $pipe->hset($key, 'sent_at', time());
            $pipe->expire($key, self::DEFAULT_TTL);
        });
    }

    /**
     * Guardar estado del juego en Redis.
     */
    public function saveState(string $gameId, GameStateDTO $state, ?int $ttl = null) : void
    {
        $key = $this->key($gameId);
        $effectiveTtl = $ttl ?? self::DEFAULT_TTL;

        Redis::pipeline(function($pipe) use ($key, $state, $effectiveTtl) {
            $pipe->hset($key, 'data', json_encode($state->toArray()));
            $pipe->hset($key, 'updated_at', time());
            $pipe->expire($key, $effectiveTtl);
        });
    }

    /**
     * Actualizar campo específico.
     */
    public function updateField(string $gameId, string $field, mixed $value) : void
    {
        $state = $this->getState($gameId);

        if (! $state) {
            return;
        }

        $data = $state->toArray();
        $data[$field] = $value;

        $newState = GameStateDTO::fromArray($data);
        $this->saveState($gameId, $newState);
    }

    /**
     * Generar key Redis.
     */
    private function key(string $gameId) : string
    {
        return self::KEY_PREFIX . $gameId;
    }
}
