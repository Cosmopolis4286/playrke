<?php

namespace App\Contracts\Roles\Seller;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de relaciones vendedor-país-moneda.
 *
 * Define los métodos específicos para gestionar las asociaciones entre
 * vendedores, países y monedas en el sistema.
 */
interface SellerCurrencyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener relaciones por vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return Collection Colección de relaciones del vendedor
     */
    public function getBySeller(string $sellerId): Collection;

    /**
     * Obtener relaciones por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return Collection Colección de relaciones de la moneda
     */
    public function getByCurrency(string $currencyId): Collection;

    /**
     * Obtener todas las relaciones activas.
     *
     * @return Collection Colección de relaciones activas
     */
    public function getActiveRelations(): Collection;

    /**
     * Obtener todas las relaciones inactivas.
     *
     * @return Collection Colección de relaciones inactivas
     */
    public function getInactiveRelations(): Collection;

    /**
     * Buscar relación específica por vendedor y moneda.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @return SellerCurrency|null La relación encontrada o null
     */
    public function getBySellerAndCurrency(string $sellerId, string $currencyId): ?SellerCurrency;

    /**
     * Buscar relación específica por vendedor, país y moneda.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @return SellerCurrency|null La relación encontrada o null
     */
    public function getBySellerCountryAndCurrency(string $sellerId, string $currencyId): ?SellerCurrency;

    /**
     * Verificar si existe una relación específica.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @return bool True si existe, false en caso contrario
     */
    public function existsRelation(string $sellerId, string $currencyId): bool;

    /**
     * Crear una nueva relación vendedor-país-moneda.
     *
     * @param array $data Datos de la relación
     * @return SellerCurrency La relación creada
     */
    public function createRelation(array $data): SellerCurrency;

    /**
     * Actualizar una relación existente.
     *
     * @param string $relationId ID de la relación
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateRelation(string $relationId, array $data): bool;

    /**
     * Actualizar el estado de una relación.
     *
     * @param string $relationId ID de la relación
     * @param bool $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $relationId, bool $status): bool;

    /**
     * Activar una relación.
     *
     * @param string $relationId ID de la relación
     * @return bool True si se activó correctamente
     */
    public function activateRelation(string $relationId): bool;

    /**
     * Desactivar una relación.
     *
     * @param string $relationId ID de la relación
     * @return bool True si se desactivó correctamente
     */
    public function deactivateRelation(string $relationId): bool;

    /**
     * Eliminar una relación.
     *
     * @param string $relationId ID de la relación
     * @return bool True si se eliminó correctamente
     */
    public function deleteRelation(string $relationId): bool;

    /**
     * Obtener conteo de monedas por vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return int Número de monedas disponibles para el vendedor
     */
    public function getCountCurrency(string $sellerId): int;

    /**
     * Obtener países disponibles para un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param bool $activeOnly Solo relaciones activas (por defecto true)
     * @return array Array de IDs de países disponibles
     */
    public function getAvailableCountries(string $sellerId, bool $activeOnly = true): array;

    /**
     * Obtener monedas disponibles para un vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @param bool $activeOnly Solo relaciones activas (por defecto true)
     * @return array Array de IDs de monedas disponibles
     */
    public function getAvailableCurrencies(string $sellerId, bool $activeOnly = true): array;

    /**
     * Obtener valor específico de una relación vendedor-moneda.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param string $field Campo a obtener
     * @return mixed Valor del campo o null
     */
    public function getValue(string $sellerId, string $currencyId, string $field): mixed;

    /**
     * Obtener conteo total de relaciones por vendedor.
     *
     * @param string $sellerId ID del vendedor
     * @return int Número total de relaciones del vendedor
     */
    public function getCountCountryCurrency(string $sellerId): int;

    /**
     * Obtener estadísticas de relaciones.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getRelationStatistics(): array;

    /**
     * Obtener relaciones con sus datos relacionados.
     *
     * @param bool $activeOnly Solo relaciones activas (por defecto true)
     * @return Collection Relaciones con datos de vendedor, país y moneda
     */
    public function getRelationsWithData(bool $activeOnly = true): Collection;

    /**
     * Crear relaciones por lotes.
     *
     * @param array $relationsData Array de datos de relaciones
     * @return Collection Relaciones creadas
     */
    public function createBulkRelations(array $relationsData): Collection;

    /**
     * Obtener relaciones para uso en selects/formularios.
     *
     * @param string|null $sellerId Filtrar por vendedor (opcional)
     * @param bool $activeOnly Solo relaciones activas (por defecto true)
     * @return array<string, string> Array asociativo [id => descripción]
     */
    public function getForSelect(?string $sellerId = null, bool $activeOnly = true): array;

    /**
     * Validar datos de relación antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir en validaciones únicas
     * @return array<string, string> Array de errores de validación
     */
    public function validateRelationData(array $data, ?string $excludeId = null): array;

    /**
     * Actualización masiva de estado para múltiples relaciones.
     *
     * @param array $relationIds IDs de las relaciones
     * @param bool $status Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $relationIds, bool $status): int;

    /**
     * Obtener vendedores que operan con una moneda específica.
     *
     * @param string $currencyId ID de la moneda
     * @param bool $activeOnly Solo relaciones activas (por defecto true)
     * @return Collection Vendedores que operan con la moneda
     */
    public function getSellersByCurrency(string $currencyId, bool $activeOnly = true): Collection;

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
     * Obtener nivel de operación de un vendedor para moneda específico.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @return int|null Nivel de operación o null si no existe
     */
    public function getOperationLevel(string $sellerId, string $currencyId): ?int;

    /**
     * Actualizar el nivel de operación de una relación.
     *
     * @param string $sellerId ID del vendedor
     * @param string $currencyId ID de la moneda
     * @param int $level Nuevo nivel
     * @return bool True si se actualizó correctamente
     */
    public function updateOperationLevel(string $sellerId, string $currencyId, int $level): bool;
}
