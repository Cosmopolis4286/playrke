<?php

namespace App\Contracts\Games\Common\PrizePool;

use App\Contracts\GameCatalogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el catálogo de tipos de pool de premios (PrizePoolType),
 * administrable por admins y específico de cada juego (Bingo, Trivia, etc.).
 *
 * Los métodos comunes (getByGame, getActiveByGame, existsByGameAndCode)
 * están definidos en GameCatalogRepositoryInterface. Aquí solo queda el
 * método propio de PrizePoolType.
 */
interface PrizePoolTypeRepositoryInterface extends GameCatalogRepositoryInterface
{
    /**
     * Obtener todos los tipos activos (usado por BingoRoomContextService
     * para alimentar el formulario de creación de salas).
     */
    public function getActiveTypes() : Collection;
}
