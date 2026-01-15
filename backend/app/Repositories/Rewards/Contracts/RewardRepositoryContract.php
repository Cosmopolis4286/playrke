<?php

declare(strict_types=1);

namespace App\Repositories\Rewards\Contracts;

use App\Models\Reward;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de recompensas
 *
 * Define los métodos disponibles para gestionar recompensas del sistema,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Rewards
 * @author Sistema de Gestión de Recompensas
 * @version 1.0
 */
interface RewardRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene todas las recompensas de un usuario creador
     *
     * @param string $userId ID del usuario creador
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<Reward> Recompensas del usuario
     *
     * @example
     * $rewards = $repository->getRewardsByCreator('123', ['status' => 'ACTIVE']);
     */
    public function getRewardsByCreator(string $userId, array $filters = []): Collection;

    /**
     * Obtiene recompensas por tipo
     *
     * @param string $rewardType Tipo de recompensa ('PRODUCT', 'SERVICE')
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas del tipo especificado
     *
     * @example
     * $products = $repository->getRewardsByType('PRODUCT');
     */
    public function getRewardsByType(string $rewardType, array $filters = []): Collection;

    /**
     * Obtiene recompensas por estado
     *
     * @param string $status Estado de la recompensa ('ACTIVE', 'INACTIVE')
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas con el estado especificado
     *
     * @example
     * $activeRewards = $repository->getRewardsByStatus('ACTIVE');
     */
    public function getRewardsByStatus(string $status, array $filters = []): Collection;

    /**
     * Busca una recompensa por código único
     *
     * @param string $code Código de la recompensa
     * @param string|null $userId ID del usuario (opcional para búsqueda específica)
     * @return Reward|null Recompensa encontrada o null
     *
     * @example
     * $reward = $repository->findByCode('REW001', '123');
     */
    public function findByCode(string $code, ?string $userId = null): ?Reward;

    /**
     * Obtiene recompensas que son kits
     *
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas que son kits
     *
     * @example
     * $kits = $repository->getRewardsKits(['status' => 'ACTIVE']);
     */
    public function getRewardsKits(array $filters = []): Collection;

    /**
     * Obtiene recompensas que no son kits
     *
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas individuales
     *
     * @example
     * $individualRewards = $repository->getIndividualRewards(['status' => 'ACTIVE']);
     */
    public function getIndividualRewards(array $filters = []): Collection;

    /**
     * Busca recompensas por nombre
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas que coinciden
     *
     * @example
     * $rewards = $repository->searchRewardsByName('premium', ['status' => 'ACTIVE']);
     */
    public function searchRewardsByName(string $searchTerm, array $filters = []): Collection;

    /**
     * Obtiene recompensas en un rango de precios
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas en el rango de precio
     *
     * @example
     * $rewards = $repository->getRewardsByPriceRange(10.00, 100.00, ['status' => 'ACTIVE']);
     */
    public function getRewardsByPriceRange(float $minPrice, float $maxPrice, array $filters = []): Collection;

    /**
     * Actualiza el estado de una recompensa
     *
     * @param string $rewardId ID de la recompensa
     * @param string $newStatus Nuevo estado
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateRewardStatus('123', 'INACTIVE');
     */
    public function updateRewardStatus(string $rewardId, string $newStatus): bool;

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
     * $stats = $repository->getRewardStatsByUser('123');
     * // ['total_rewards' => 15, 'active_rewards' => 12, 'inactive_rewards' => 3]
     */
    public function getRewardStatsByUser(string $userId): array;

    /**
     * Obtiene estadísticas generales de recompensas
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralRewardStats();
     */
    public function getGeneralRewardStats(): array;

    /**
     * Cuenta recompensas por tipo
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por tipo
     *
     * @example
     * $counts = $repository->countRewardsByType();
     * // ['PRODUCT' => 25, 'SERVICE' => 15]
     */
    public function countRewardsByType(array $filters = []): array;

    /**
     * Cuenta recompensas por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countRewardsByStatus();
     * // ['ACTIVE' => 35, 'INACTIVE' => 5]
     */
    public function countRewardsByStatus(array $filters = []): array;

    /**
     * Obtiene recompensas más caras
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas más caras
     *
     * @example
     * $expensiveRewards = $repository->getMostExpensiveRewards(10);
     */
    public function getMostExpensiveRewards(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene recompensas más baratas
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas más baratas
     *
     * @example
     * $cheapRewards = $repository->getCheapestRewards(10);
     */
    public function getCheapestRewards(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene recompensas creadas en un rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $filters Filtros adicionales
     * @return Collection<Reward> Recompensas en el rango
     *
     * @example
     * $rewards = $repository->getRewardsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getRewardsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene todas las recompensas con paginación
     *
     * @param int $perPage Elementos por página
     * @param array $filters Filtros adicionales
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Recompensas paginadas
     *
     * @example
     * $paginatedRewards = $repository->getPaginatedRewards(20, ['status' => 'ACTIVE']);
     */
    public function getPaginatedRewards(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Actualiza múltiples recompensas en lote
     *
     * @param array $rewardIds IDs de las recompensas
     * @param array $updateData Datos a actualizar
     * @return int Número de recompensas actualizadas
     *
     * @example
     * $updated = $repository->bulkUpdateRewards(['1', '2'], ['status' => 'INACTIVE']);
     */
    public function bulkUpdateRewards(array $rewardIds, array $updateData): int;

    /**
     * Elimina recompensas antiguas inactivas
     *
     * @param int $daysOld Días de antigüedad
     * @return int Número de recompensas eliminadas
     *
     * @example
     * $deleted = $repository->deleteOldInactiveRewards(90);
     */
    public function deleteOldInactiveRewards(int $daysOld = 90): int;

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