<?php

namespace App\Repositories\Users\Roles\Seller\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Users\Roles\Seller\Seller;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de vendedores.
 *
 * Define los métodos específicos para gestionar los vendedores del sistema,
 * incluyendo operaciones de consulta, creación, comisiones y estadísticas.
 */
interface SellerRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener vendedores por estado.
     *
     * @param bool $status Estado del vendedor (activo/inactivo)
     * @return Collection Colección de vendedores con el estado especificado
     */
    public function getByStatus(bool $status): Collection;

    /**
     * Obtener todos los vendedores activos.
     *
     * @return Collection Colección de vendedores activos
     */
    public function getActiveSellers(): Collection;

    /**
     * Obtener todos los vendedores inactivos.
     *
     * @return Collection Colección de vendedores inactivos
     */
    public function getInactiveSellers(): Collection;

    /**
     * Buscar vendedor por usuario.
     *
     * @param string $userId ID del usuario
     * @return Seller|null El vendedor encontrado o null
     */
    public function getByUserId(string $userId): ?Seller;

    /**
     * Buscar vendedores por país.
     *
     * @param string $countryId ID del país
     * @return Collection Vendedores del país especificado
     */
    public function getByCountry(string $countryId): Collection;

    /**
     * Buscar vendedores por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return Collection Vendedores que usan la moneda especificada
     */
    public function getByCurrency(string $currencyId): Collection;

    /**
     * Buscar vendedores por comercio.
     *
     * @param string $commerceId ID del comercio
     * @return Collection Vendedores del comercio especificado
     */
    public function getByCommerce(string $commerceId): Collection;

    /**
     * Buscar vendedores por nombre.
     *
     * @param string $name Nombre a buscar
     * @return Collection Vendedores que coinciden con el nombre
     */
    public function getByName(string $name): Collection;

    /**
     * Buscar vendedores por teléfono.
     *
     * @param string $phone Teléfono a buscar
     * @return Seller|null Vendedor encontrado o null
     */
    public function getByPhone(string $phone): ?Seller;

    /**
     * Verificar si existe un vendedor con el teléfono dado.
     *
     * @param string $phone Teléfono a verificar
     * @param string|null $excludeId ID a excluir de la verificación
     * @return bool True si existe, false en caso contrario
     */
    public function existsByPhone(string $phone, ?string $excludeId = null): bool;

    /**
     * Crear un nuevo vendedor.
     *
     * @param array $data Datos del vendedor
     * @return Seller El vendedor creado
     */
    public function createSeller(array $data): Seller;

    /**
     * Actualizar información de un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateSeller(string $sellerId, array $data): bool;

    /**
     * Actualizar el estado de un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param bool $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $sellerId, bool $status): bool;

    /**
     * Activar un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return bool True si se activó correctamente
     */
    public function activateSeller(string $sellerId): bool;

    /**
     * Desactivar un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return bool True si se desactivó correctamente
     */
    public function deactivateSeller(string $sellerId): bool;

    /**
     * Eliminar un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return bool True si se eliminó correctamente
     */
    public function deleteSeller(string $sellerId): bool;

    /**
     * Actualizar la comisión de un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param float $commission Nueva comisión
     * @return bool True si se actualizó correctamente
     */
    public function updateCommission(string $sellerId, float $commission): bool;

    /**
     * Obtener vendedores por rango de comisión.
     *
     * @param float $minCommission Comisión mínima
     * @param float $maxCommission Comisión máxima
     * @return Collection Vendedores en el rango especificado
     */
    public function getByCommissionRange(float $minCommission, float $maxCommission): Collection;

    /**
     * Obtener estadísticas de vendedores.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getSellerStatistics(): array;

    /**
     * Obtener vendedores ordenados por comisión.
     *
     * @param string $direction Dirección del ordenamiento (asc|desc)
     * @param bool $activeOnly Solo vendedores activos (por defecto false)
     * @return Collection Vendedores ordenados por comisión
     */
    public function getOrderedByCommission(string $direction = 'desc', bool $activeOnly = false): Collection;

    /**
     * Obtener vendedores con más ventas.
     *
     * @param int $limit Límite de resultados (por defecto 10)
     * @return Collection Vendedores más activos
     */
    public function getTopSellers(int $limit = 10): Collection;

    /**
     * Verificar si un vendedor está siendo utilizado en transacciones.
     *
     * @param string $sellerId ID del vendedor
     * @return bool True si está en uso, false en caso contrario
     */
    public function isSellerInUse(string $sellerId): bool;

    /**
     * Obtener conteo de ventas por vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return int Número de ventas del vendedor
     */
    public function getSalesCountBySeller(string $sellerId): int;

    /**
     * Obtener vendedores con sus estadísticas de ventas.
     *
     * @param bool $activeOnly Solo vendedores activos (por defecto true)
     * @return Collection Vendedores con estadísticas de ventas
     */
    public function getSellersWithSalesStats(bool $activeOnly = true): Collection;

    /**
     * Crear vendedores por lotes.
     *
     * @param array $sellersData Array de datos de vendedores
     * @return Collection Vendedores creados
     */
    public function createBulkSellers(array $sellersData): Collection;

    /**
     * Obtener vendedores para uso en selects/formularios.
     *
     * @param bool $activeOnly Solo vendedores activos (por defecto true)
     * @param string|null $countryFilter Filtrar por país (opcional)
     * @return array<string, string> Array asociativo [id => name]
     */
    public function getForSelect(bool $activeOnly = true, ?string $countryFilter = null): array;

    /**
     * Validar datos de vendedor antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir en validaciones únicas
     * @return array<string, string> Array de errores de validación
     */
    public function validateSellerData(array $data, ?string $excludeId = null): array;

    /**
     * Obtener moneda por ID de vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return string|null ID de la moneda o null
     */
    public function getCurrencyByIdId(string $sellerId): ?string;

    /**
     * Obtener valor específico por ID de vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param string $field Campo a obtener
     * @return mixed Valor del campo o null
     */
    public function getValueById(string $sellerId, string $field): mixed;

    /**
     * Obtener valor específico por ID de usuario.
     *
     * @param string $userId ID del usuario
     * @param string $field Campo a obtener (por defecto 'id')
     * @return mixed Valor del campo o null
     */
    public function getValueByUserId(string $userId, string $field = 'id'): mixed;

    /**
     * Actualización masiva de estado para múltiples vendedores.
     *
     * @param array $sellerIds IDs de los vendedores
     * @param bool $status Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $sellerIds, bool $status): int;

    /**
     * Obtener vendedores sin países/monedas asignadas.
     *
     * @return Collection Vendedores incompletos
     */
    public function getIncompleteData(): Collection;

    /**
     * Obtener distribución de vendedores por país.
     *
     * @return array<string, int> Array con distribución por país
     */
    public function getCountryDistribution(): array;

    /**
     * Obtener distribución de vendedores por moneda.
     *
     * @return array<string, int> Array con distribución por moneda
     */
    public function getCurrencyDistribution(): array;

    /**
     * Obtener promedio de comisión de vendedores activos.
     *
     * @return float Promedio de comisión
     */
    public function getAverageCommission(): float;
}