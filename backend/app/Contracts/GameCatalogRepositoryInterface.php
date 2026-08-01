<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato base compartido para catálogos administrables específicos de
 * cada juego (GameMode, PrizePoolType, etc.), identificados por game_id.
 */
interface GameCatalogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Registros de un juego (activos e inactivos).
     */
    public function getByGame(string $gameId): Collection;

    /**
     * Registros activos de un juego, para selects/formularios.
     */
    public function getActiveByGame(string $gameId): Collection;

    /**
     * Verificar si existe un registro con ese código dentro del juego.
     */
    public function existsByGameAndCode(string $gameId, string $code, ?string $excludeId = null): bool;
}
