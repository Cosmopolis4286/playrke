<?php

namespace App\Contracts\Games\Common\GameMode;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\GameMode\GameMode;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el catálogo de modos de juego (GameMode), administrable por
 * admins y específico de cada juego (Bingo, Trivia, etc.).
 */
interface GameModeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Modos de un juego (activos e inactivos).
     */
    public function getByGame(string $gameId): Collection;

    /**
     * Modos activos de un juego, para selects/formularios.
     */
    public function getActiveByGame(string $gameId): Collection;

    /**
     * Verificar si existe un modo con ese código dentro del juego.
     */
    public function existsByGameAndCode(string $gameId, string $code, ?string $excludeId = null): bool;
}
