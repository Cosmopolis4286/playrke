<?php

namespace App\Contracts\Roles\Commerce;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Pivots\Roles\Commerce\CommerceGame;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de permisos de juegos de comercios
 */
interface CommerceGameRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene permisos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getByCommerce($commerceId): Collection;

    /**
     * Obtiene permisos por juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getByGame($gameId): Collection;

    /**
     * Obtiene juegos disponibles/permitidos para un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed|null $exceptGameId ID de juego a excluir
     * @return array
     */
    public function getAllowedGames($commerceId, $exceptGameId = null): array;

    /**
     * Obtiene permisos activos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getActiveByCommerce($commerceId): Collection;

    /**
     * Obtiene permisos inactivos por comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return Collection
     */
    public function getInactiveByCommerce($commerceId): Collection;

    /**
     * Obtiene comercios con permiso para un juego
     *
     * @param mixed $gameId ID del juego
     * @return Collection
     */
    public function getCommercesWithGamePermission($gameId): Collection;

    /**
     * Verifica si un comercio tiene permiso para un juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function hasPermission($commerceId, $gameId): bool;

    /**
     * Obtiene permiso específico por comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return CommerceGame|null
     */
    public function getPermission($commerceId, $gameId): ?CommerceGame;

    /**
     * Activa permiso para un comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function activatePermission($commerceId, $gameId): bool;

    /**
     * Desactiva permiso para un comercio y juego
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @return bool
     */
    public function deactivatePermission($commerceId, $gameId): bool;

    /**
     * Activa todos los permisos de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de permisos activados
     */
    public function activateAllByCommerce($commerceId): int;

    /**
     * Desactiva todos los permisos de un comercio
     *
     * @param mixed $commerceId ID del comercio
     * @return int Número de permisos desactivados
     */
    public function deactivateAllByCommerce($commerceId): int;

    /**
     * Crea o actualiza permiso
     *
     * @param mixed $commerceId ID del comercio
     * @param mixed $gameId ID del juego
     * @param bool $isActive Estado del permiso
     * @return CommerceGame
     */
    public function createOrUpdatePermission($commerceId, $gameId, bool $isActive = true): CommerceGame;

    /**
     * Obtiene todos los permisos activos
     *
     * @return Collection
     */
    public function getAllActivePermissions(): Collection;

    /**
     * Obtiene todos los permisos inactivos
     *
     * @return Collection
     */
    public function getAllInactivePermissions(): Collection;
}
