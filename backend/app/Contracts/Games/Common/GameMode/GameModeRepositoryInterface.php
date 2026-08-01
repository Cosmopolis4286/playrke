<?php

namespace App\Contracts\Games\Common\GameMode;

use App\Contracts\GameCatalogRepositoryInterface;

/**
 * Contrato para el catálogo de modos de juego (GameMode), administrable por
 * admins y específico de cada juego (Bingo, Trivia, etc.).
 *
 * Los métodos comunes (getByGame, getActiveByGame, existsByGameAndCode)
 * están definidos en GameCatalogRepositoryInterface.
 */
interface GameModeRepositoryInterface extends GameCatalogRepositoryInterface
{
}
