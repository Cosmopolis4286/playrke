<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Contracts;

use App\Domains\Games\Trivia\DTOs\GameStateDTO;

/**
 * Repositorio para estado temporal de juegos (Redis).
 *
 * Esta interfaz permite cambiar fácilmente la implementación
 * de almacenamiento sin afectar la lógica de negocio.
 */
interface GameStateRepositoryInterface
{
    /**
     * Eliminar estado del juego.
     */
    public function deleteState(string $gameId) : void;

    /**
     * Verificar si existe estado.
     */
    public function exists(string $gameId) : bool;

    /**
     * Extender tiempo de vida del estado.
     */
    public function extendTtl(string $gameId, int $ttl) : void;

    /**
     * Obtener la pregunta activa del juego.
     */
    public function getCurrentQuestion(string $gameId) : ?array;

    /**
     * Obtener campo específico del estado.
     */
    public function getField(string $gameId, string $field) : mixed;

    /**
     * Obtener estado del juego.
     */
    public function getState(string $gameId) : ?GameStateDTO;

    /**
     * Guardar la pregunta activa del juego.
     */
    public function saveCurrentQuestion(string $gameId, array $questionData) : void;

    /**
     * Guardar estado del juego.
     */
    public function saveState(string $gameId, GameStateDTO $state, ?int $ttl = null) : void;

    /**
     * Actualizar campo específico del estado.
     */
    public function updateField(string $gameId, string $field, mixed $value) : void;
}
