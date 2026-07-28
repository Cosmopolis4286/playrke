<?php

namespace App\Contracts\Games\Common\PrizePool;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el catálogo de tipos de pool de premios (PrizePoolType),
 * administrable por admins y específico de cada juego (Bingo, Trivia, etc.).
 */
interface PrizePoolTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener todos los tipos activos (usado por BingoRoomContextService
     * para alimentar el formulario de creación de salas).
     */
    public function getActiveTypes(): Collection;

    /**
     * Tipos de un juego (activos e inactivos).
     */
    public function getByGame(string $gameId): Collection;

    /**
     * Tipos activos de un juego, para selects/formularios.
     */
    public function getActiveByGame(string $gameId): Collection;

    /**
     * Verificar si existe un tipo con ese código dentro del juego.
     */
    public function existsByGameAndCode(string $gameId, string $code, ?string $excludeId = null): bool;
}
