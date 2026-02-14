<?php

namespace App\Contracts;

use App\Models\Prize;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de recompensas
 *
 * Define los métodos disponibles para gestionar recompensas del sistema,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Contracts\Prizes
 * @author Sistema de Gestión de Recompensas
 * @version 1.0
 */
interface PrizeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene todas las recompensas de un usuario creador
     *
     * @param string $userId ID del usuario creador
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<Prize> Recompensas del usuario
     *
     * @example
     * $prizes = $repository->getPrizesByCreator('123', ['status' => 'ACTIVE']);
     */
    public function getPrizesByCreator(string $userId, array $filters = []): Collection;

    /**
     * Obtiene recompensas por tipo
     *
     * @param string $rewardType Tipo de recompensa ('PRODUCT', 'SERVICE')
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas del tipo especificado
     *
     * @example
     * $products = $repository->getPrizesByType('PRODUCT');
     */
    public function getPrizesByType(string $rewardType, array $filters = []): Collection;

    /**
     * Obtiene recompensas por estado
     *
     * @param string $status Estado de la recompensa ('ACTIVE', 'INACTIVE')
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas con el estado especificado
     *
     * @example
     * $activePrizes = $repository->getPrizesByStatus('ACTIVE');
     */
    public function getPrizesByStatus(string $status, array $filters = []): Collection;

    /**
     * Busca una recompensa por código único
     *
     * @param string $code Código de la recompensa
     * @param string|null $userId ID del usuario (opcional para búsqueda específica)
     * @return Prize|null Recompensa encontrada o null
     *
     * @example
     * $prize = $repository->findByCode('REW001', '123');
     */
    public function findByCode(string $code, ?string $userId = null): ?Prize;

    /**
     * Obtiene recompensas que son kits
     *
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas que son kits
     *
     * @example
     * $kits = $repository->getPrizesKits(['status' => 'ACTIVE']);
     */
    public function getPrizesKits(array $filters = []): Collection;

    /**
     * Obtiene recompensas que no son kits
     *
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas individuales
     *
     * @example
     * $individualPrizes = $repository->getIndividualPrizes(['status' => 'ACTIVE']);
     */
    public function getIndividualPrizes(array $filters = []): Collection;

    /**
     * Busca recompensas por nombre
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas que coinciden
     *
     * @example
     * $prizes = $repository->searchPrizesByName('premium', ['status' => 'ACTIVE']);
     */
    public function searchPrizesByName(string $searchTerm, array $filters = []): Collection;

    /**
     * Obtiene recompensas en un rango de precios
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas en el rango de precio
     *
     * @example
     * $prizes = $repository->getPrizesByPriceRange(10.00, 100.00, ['status' => 'ACTIVE']);
     */
    public function getPrizesByPriceRange(float $minPrice, float $maxPrice, array $filters = []): Collection;

    /**
     * Actualiza el estado de una recompensa
     *
     * @param string $rewardId ID de la recompensa
     * @param string $newStatus Nuevo estado
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePrizeStatus('123', 'INACTIVE');
     */
    public function updatePrizeStatus(string $rewardId, string $newStatus): bool;

    /**
     * Verifica si un código existe para un usuario
     *
     * @param string $code Código de la recompensa
     * @param string $userId ID del usuario
     * @param string|null $excludeId ID de recompensa a excluir (para updates)
     * @return bool True si el código existe
     *
     * @example
     * $exists = $repository->codeExistsForUser('REW001', '123');
     */
    public function codeExistsForUser(string $code, string $userId, ?string $excludeId = null): bool;

    /**
     * Obtiene estadísticas de recompensas por usuario
     *
     * @param string $userId ID del usuario
     * @return array Estadísticas del usuario
     *
     * @example
     * $stats = $repository->getPrizeStatsByUser('123');
     * // ['total_prizes' => 15, 'active_prizes' => 12, 'inactive_prizes' => 3]
     */
    public function getPrizeStatsByUser(string $userId): array;

    /**
     * Obtiene estadísticas generales de recompensas
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralPrizeStats();
     */
    public function getGeneralPrizeStats(): array;

    /**
     * Cuenta recompensas por tipo
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por tipo
     *
     * @example
     * $counts = $repository->countPrizesByType();
     * // ['PRODUCT' => 25, 'SERVICE' => 15]
     */
    public function countPrizesByType(array $filters = []): array;

    /**
     * Cuenta recompensas por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countPrizesByStatus();
     * // ['ACTIVE' => 35, 'INACTIVE' => 5]
     */
    public function countPrizesByStatus(array $filters = []): array;

    /**
     * Obtiene recompensas más caras
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas más caras
     *
     * @example
     * $expensivePrizes = $repository->getMostExpensivePrizes(10);
     */
    public function getMostExpensivePrizes(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene recompensas más baratas
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas más baratas
     *
     * @example
     * $cheapPrizes = $repository->getCheapestPrizes(10);
     */
    public function getCheapestPrizes(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene recompensas creadas en un rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $filters Filtros adicionales
     * @return Collection<Prize> Recompensas en el rango
     *
     * @example
     * $prizes = $repository->getPrizesByDateRange('2024-01-01', '2024-01-31');
     */
    public function getPrizesByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene todas las recompensas con paginación
     *
     * @param int $perPage Elementos por página
     * @param array $filters Filtros adicionales
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Recompensas paginadas
     *
     * @example
     * $paginatedPrizes = $repository->getPaginatedPrizes(20, ['status' => 'ACTIVE']);
     */
    public function getPaginatedPrizes(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Actualiza múltiples recompensas en lote
     *
     * @param array $rewardIds IDs de las recompensas
     * @param array $updateData Datos a actualizar
     * @return int Número de recompensas actualizadas
     *
     * @example
     * $updated = $repository->bulkUpdatePrizes(['1', '2'], ['status' => 'INACTIVE']);
     */
    public function bulkUpdatePrizes(array $rewardIds, array $updateData): int;

    /**
     * Elimina recompensas antiguas inactivas
     *
     * @param int $daysOld Días de antigüedad
     * @return int Número de recompensas eliminadas
     *
     * @example
     * $deleted = $repository->deleteOldInactivePrizes(90);
     */
    public function deleteOldInactivePrizes(int $daysOld = 90): int;

    /**
     * Obtiene el valor total de recompensas de un usuario
     *
     * @param string $userId ID del usuario
     * @param array $filters Filtros adicionales
     * @return float Valor total
     *
     * @example
     * $totalValue = $repository->getTotalValueByUser('123', ['status' => 'ACTIVE']);
     */
    public function getTotalValueByUser(string $userId, array $filters = []): float;
}
