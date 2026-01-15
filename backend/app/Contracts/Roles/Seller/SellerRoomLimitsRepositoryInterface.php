<?php

namespace App\Contracts\Roles\Seller;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de límites de salas de vendedores.
 *
 * Define las operaciones específicas para gestionar los límites y comisiones
 * de vendedores en diferentes salas de juego, incluyendo consultas por juego,
 * sala, vendedor y gestión de comisiones.
 */
interface SellerRoomLimitsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los límites de un vendedor específico.
     *
     * @param string $sellerId ID del vendedor
     * @return Collection<SellerRoomLimits> Colección de límites del vendedor
     */
    public function getBySeller(string $sellerId): Collection;

    /**
     * Obtiene todos los límites de una sala específica.
     *
     * @param string $roomId ID de la sala
     * @return Collection<SellerRoomLimits> Colección de límites de la sala
     */
    public function getByRoom(string $roomId): Collection;

    /**
     * Obtiene todos los límites de un juego específico.
     *
     * @param string $gameId ID del juego
     * @return Collection<SellerRoomLimits> Colección de límites del juego
     */
    public function getByGame(string $gameId): Collection;

    /**
     * Obtiene el límite específico de un vendedor en una sala y juego.
     *
     * @param string $gameId ID del juego
     * @param string $roomId ID de la sala
     * @param string $sellerId ID del vendedor
     * @return mixed Límite encontrado o null
     */
    public function getByGameRoomSeller(string $gameId, string $roomId, string $sellerId): mixed;

    /**
     * Obtiene un valor específico para una combinación de juego, sala y vendedor.
     *
     * @param string $gameId ID del juego
     * @param string $roomId ID de la sala
     * @param string $sellerId ID del vendedor
     * @param string $field Campo a obtener
     * @return mixed Valor del campo solicitado
     */
    public function getValueByGameRoomSeller(string $gameId, string $roomId, string $sellerId, string $field): mixed;

    /**
     * Crea o actualiza un límite de vendedor.
     *
     * @param array $data Datos del límite
     * @return mixed Límite creado o actualizado
     */
    public function createOrUpdateLimit(array $data): mixed;

    /**
     * Actualiza la comisión de un límite específico.
     *
     * @param string $limitId ID del límite
     * @param float $commission Nueva comisión
     * @return bool True si se actualizó correctamente
     */
    public function updateCommission(string $limitId, float $commission): bool;

    /**
     * Actualiza la comisión para una combinación específica.
     *
     * @param string $gameId ID del juego
     * @param string $roomId ID de la sala
     * @param string $sellerId ID del vendedor
     * @param float $commission Nueva comisión
     * @return bool True si se actualizó correctamente
     */
    public function updateCommissionByGameRoomSeller(string $gameId, string $roomId, string $sellerId, float $commission): bool;

    /**
     * Elimina un límite específico.
     *
     * @param string $limitId ID del límite
     * @return bool True si se eliminó correctamente
     */
    public function deleteLimit(string $limitId): bool;

    /**
     * Elimina todos los límites de un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return int Número de límites eliminados
     */
    public function deleteBySeller(string $sellerId): int;

    /**
     * Elimina todos los límites de una sala.
     *
     * @param string $roomId ID de la sala
     * @return int Número de límites eliminados
     */
    public function deleteByRoom(string $roomId): int;

    /**
     * Obtiene estadísticas de comisiones por vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return array Estadísticas de comisiones
     */
    public function getCommissionStatsBySeller(string $sellerId): array;

    /**
     * Obtiene estadísticas de comisiones por sala.
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas de comisiones
     */
    public function getCommissionStatsByRoom(string $roomId): array;

    /**
     * Obtiene vendedores con acceso a una sala específica.
     *
     * @param string $roomId ID de la sala
     * @return Collection<SellerRoomLimits> Vendedores con acceso
     */
    public function getSellersWithRoomAccess(string $roomId): Collection;

    /**
     * Obtiene salas disponibles para un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return Collection<SellerRoomLimits> Salas disponibles
     */
    public function getRoomsForSeller(string $sellerId): Collection;

    /**
     * Verifica si un vendedor tiene acceso a una sala específica.
     *
     * @param string $sellerId ID del vendedor
     * @param string $roomId ID de la sala
     * @return bool True si tiene acceso
     */
    public function hasSellerRoomAccess(string $sellerId, string $roomId): bool;

    /**
     * Obtiene límites con comisiones en un rango específico.
     *
     * @param float $minCommission Comisión mínima
     * @param float $maxCommission Comisión máxima
     * @return Collection<SellerRoomLimits> Límites en el rango
     */
    public function getByCommissionRange(float $minCommission, float $maxCommission): Collection;

    /**
     * Crea límites en lote para múltiples vendedores.
     *
     * @param array $limitsData Array de datos de límites
     * @return Collection<SellerRoomLimits> Límites creados
     */
    public function createBulkLimits(array $limitsData): Collection;

    /**
     * Valida los datos de un límite antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir de validaciones de unicidad
     * @return array Array de errores de validación
     */
    public function validateLimitData(array $data, ?string $excludeId = null): array;

    /**
     * Obtiene el promedio de comisiones por juego.
     *
     * @param string $gameId ID del juego
     * @return float Promedio de comisiones
     */
    public function getAverageCommissionByGame(string $gameId): float;

    /**
     * Obtiene la distribución de vendedores por sala.
     *
     * @return array Distribución de vendedores
     */
    public function getSellerDistributionByRoom(): array;
}
