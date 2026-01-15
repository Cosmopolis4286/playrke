<?php

namespace App\Repositories\Shared\GameRoom\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de monedas en salas de juego
 */
interface CurrencyGameRoomRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Crea nueva moneda en sala
     *
     * @param array $game Datos del juego
     * @param mixed $roomId ID de la sala
     * @param mixed $currencyId ID de la moneda
     * @return bool
     */
    public function createNewCurrencyInRoom(array $game, $roomId, $currencyId): bool;

    /**
     * Verifica si existe combinación de moneda, sala, juego y modo
     *
     * @param mixed $currencyId ID de la moneda
     * @param mixed $roomId ID de la sala
     * @param mixed $gameId ID del juego
     * @param mixed|null $gameModeId ID del modo de juego
     * @return bool
     */
    public function existsCurrencyGameRoom($currencyId, $roomId, $gameId, $gameModeId = null): bool;

    /**
     * Obtiene monedas disponibles por sala
     *
     * @param mixed $gameId ID del juego
     * @param mixed $roomId ID de la sala
     * @param mixed|null $gameModeId ID del modo de juego
     * @return Collection
     */
    public function getCurrenciesByRoom($gameId, $roomId, $gameModeId = null): Collection;

    /**
     * Obtiene configuraciones por juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getByGame($gameId): Collection;

    /**
     * Obtiene configuraciones por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getByRoom($roomId): Collection;

    /**
     * Obtiene configuraciones por moneda
     *
     * @param mixed $currencyId ID de la moneda
     * @return Collection
     */
    public function getByCurrency($currencyId): Collection;

    /**
     * Obtiene configuraciones por modo de juego
     *
     * @param mixed $gameModeId ID del modo de juego
     * @return Collection
     */
    public function getByGameMode($gameModeId): Collection;

    /**
     * Elimina configuración específica
     *
     * @param mixed $gameId ID del juego
     * @param mixed $roomId ID de la sala
     * @param mixed $currencyId ID de la moneda
     * @param mixed|null $gameModeId ID del modo de juego
     * @return bool
     */
    public function removeConfiguration($gameId, $roomId, $currencyId, $gameModeId = null): bool;

    /**
     * Obtiene salas que soportan una moneda específica
     *
     * @param mixed $currencyId ID de la moneda
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getRoomsByCurrency($currencyId, $gameId): Collection;
}