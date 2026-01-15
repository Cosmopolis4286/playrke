<?php

namespace App\Repositories\Users\Roles\Treasurer\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Users\Treasurer\OtherTreasurers;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de otros tesoreros.
 *
 * Define los métodos específicos para gestionar otros tesoreros,
 * incluyendo permisos de juegos, monedas y estados de los tesoreros.
 */
interface OtherTreasurersRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener un tesorero por usuario creador.
     *
     * @param string $creatorUserId ID del usuario creador
     * @return OtherTreasurers|null El tesorero encontrado o null si no existe
     */
    public function getByCreatorUser(string $creatorUserId): ?OtherTreasurers;

    /**
     * Verificar si existe un tesorero para el usuario creador.
     *
     * @param string $creatorUserId ID del usuario creador
     * @return bool True si existe, false en caso contrario
     */
    public function existsByCreatorUser(string $creatorUserId): bool;

    /**
     * Obtener tesoreros activos.
     *
     * @return Collection Colección de tesoreros activos
     */
    public function getActiveTesoreros(): Collection;

    /**
     * Obtener tesoreros inactivos.
     *
     * @return Collection Colección de tesoreros inactivos
     */
    public function getInactiveTesoreros(): Collection;

    /**
     * Obtener tesoreros bloqueados.
     *
     * @return Collection Colección de tesoreros bloqueados
     */
    public function getBlockedTesoreros(): Collection;

    /**
     * Obtener tesoreros que tienen acceso a un juego específico.
     *
     * @param string $gameId ID del juego
     * @return Collection Tesoreros con acceso al juego
     */
    public function getByAllowedGame(string $gameId): Collection;

    /**
     * Obtener tesoreros que tienen acceso a una moneda específica.
     *
     * @param string $currencyId ID de la moneda
     * @return Collection Tesoreros con acceso a la moneda
     */
    public function getByAllowedCurrency(string $currencyId): Collection;

    /**
     * Crear un nuevo tesorero.
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $allowedGames IDs de juegos permitidos
     * @param array $allowedCurrencies IDs de monedas permitidas
     * @param string $status Estado inicial (por defecto ACTIVE)
     * @return OtherTreasurers El tesorero creado
     */
    public function createTesorero(
        string $creatorUserId,
        array $allowedGames,
        array $allowedCurrencies,
        string $status = 'ACTIVE'
    ): OtherTreasurers;

    /**
     * Actualizar el estado de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $treasurerId, string $status): bool;

    /**
     * Activar un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @return bool True si se activó correctamente
     */
    public function activateTesorero(string $treasurerId): bool;

    /**
     * Desactivar un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @return bool True si se desactivó correctamente
     */
    public function deactivateTesorero(string $treasurerId): bool;

    /**
     * Bloquear un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @return bool True si se bloqueó correctamente
     */
    public function blockTesorero(string $treasurerId): bool;

    /**
     * Actualizar los juegos permitidos para un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param array $allowedGames Nuevos IDs de juegos permitidos
     * @return bool True si se actualizó correctamente
     */
    public function updateAllowedGames(string $treasurerId, array $allowedGames): bool;

    /**
     * Actualizar las monedas permitidas para un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param array $allowedCurrencies Nuevos IDs de monedas permitidas
     * @return bool True si se actualizó correctamente
     */
    public function updateAllowedCurrencies(string $treasurerId, array $allowedCurrencies): bool;

    /**
     * Agregar un juego a los permisos de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $gameId ID del juego a agregar
     * @return bool True si se agregó correctamente
     */
    public function addAllowedGame(string $treasurerId, string $gameId): bool;

    /**
     * Remover un juego de los permisos de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $gameId ID del juego a remover
     * @return bool True si se removió correctamente
     */
    public function removeAllowedGame(string $treasurerId, string $gameId): bool;

    /**
     * Agregar una moneda a los permisos de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $currencyId ID de la moneda a agregar
     * @return bool True si se agregó correctamente
     */
    public function addAllowedCurrency(string $treasurerId, string $currencyId): bool;

    /**
     * Remover una moneda de los permisos de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $currencyId ID de la moneda a remover
     * @return bool True si se removió correctamente
     */
    public function removeAllowedCurrency(string $treasurerId, string $currencyId): bool;

    /**
     * Eliminar un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @return bool True si se eliminó correctamente
     */
    public function deleteTesorero(string $treasurerId): bool;

    /**
     * Actualización masiva de estado para múltiples tesoreros.
     *
     * @param array $treasurerIds IDs de los tesoreros
     * @param string $status Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $treasurerIds, string $status): int;

    /**
     * Obtener estadísticas de tesoreros.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getTesorerosStatistics(): array;

    /**
     * Obtener estadísticas de permisos de un tesorero.
     *
     * @param string $treasurerId ID del tesorero
     * @return array<string, mixed> Array con estadísticas de permisos
     */
    public function getPermissionsStatistics(string $treasurerId): array;

    /**
     * Obtener todos los usuarios creadores únicos.
     *
     * @return Collection IDs únicos de usuarios creadores
     */
    public function getAllCreatorUsers(): Collection;

    /**
     * Obtener tesoreros creados recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Tesoreros creados en el período especificado
     */
    public function getRecentTesoreros(int $days = 30): Collection;

    /**
     * Verificar si un tesorero tiene acceso a un juego específico.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $gameId ID del juego
     * @return bool True si tiene acceso, false en caso contrario
     */
    public function hasGameAccess(string $treasurerId, string $gameId): bool;

    /**
     * Verificar si un tesorero tiene acceso a una moneda específica.
     *
     * @param string $treasurerId ID del tesorero
     * @param string $currencyId ID de la moneda
     * @return bool True si tiene acceso, false en caso contrario
     */
    public function hasCurrencyAccess(string $treasurerId, string $currencyId): bool;
}
