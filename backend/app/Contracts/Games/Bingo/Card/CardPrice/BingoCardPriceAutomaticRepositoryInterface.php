<?php

namespace App\Contracts\Games\Bingo\Card\CardPrice;

use App\Models\Games\Bingo\Card\CardPrice\BingoCardPriceAutomatic;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de borradores de precios de cartones de bingo
 *
 * Define los métodos disponibles para gestionar borradores de precios de cartones,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Cards\CardPrice
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
interface BingoCardPriceAutomaticRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los borradores de una sala de bingo específica
     *
     * @param string $bingoRoomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceAutomatic> Borradores de la sala
     *
     * @example
     * $automatic = $repository->getAutomaticsByBingoRoom('123', ['status' => 'ACTIVE']);
     */
    public function getAutomaticsByBingoRoom(string $bingoRoomId, array $filters = []): Collection;

    /**
     * Obtiene todos los borradores de una sala automática específica
     *
     * @param string $automaticBingoRoomId ID de la sala automática
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceAutomatic> Borradores de la sala automática
     *
     * @example
     * $automatic = $repository->getAutomaticsByAutomaticBingoRoom('456', ['status' => 'ACTIVE']);
     */
    public function getAutomaticsByAutomaticBingoRoom(string $automaticBingoRoomId, array $filters = []): Collection;

    /**
     * Obtiene borradores por usuario creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceAutomatic> Borradores del usuario
     *
     * @example
     * $automatic = $repository->getAutomaticsByCreator('789', ['status' => 'ACTIVE']);
     */
    public function getAutomaticsByCreator(string $creatorUserId, array $filters = []): Collection;

    /**
     * Obtiene borradores por estado
     *
     * @param string $status Estado del borrador ('ACTIVE', 'EXECUTED')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceAutomatic> Borradores con el estado especificado
     *
     * @example
     * $activeAutomatics = $repository->getAutomaticsByStatus('ACTIVE');
     */
    public function getAutomaticsByStatus(string $status, array $filters = []): Collection;

    /**
     * Busca un borrador específico por sala y usuario
     *
     * @param string|null $bingoRoomId ID de la sala de bingo (opcional)
     * @param string|null $automaticBingoRoomId ID de la sala automática (opcional)
     * @param string $creatorUserId ID del usuario creador
     * @return BingoCardPriceAutomatic|null Borrador encontrado o null
     *
     * @example
     * $draft = $repository->findAutomaticByRoomAndCreator('123', null, '789');
     */
    public function findAutomaticByRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): ?BingoCardPriceAutomatic;

    /**
     * Obtiene borradores que contienen una moneda específica
     *
     * @param string $currencyCode Código de la moneda (ej: 'USD', 'EUR')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceAutomatic> Borradores que contienen la moneda
     *
     * @example
     * $automaticWithUSD = $repository->getAutomaticsByCurrency('USD');
     */
    public function getAutomaticsByCurrency(string $currencyCode, array $filters = []): Collection;

    /**
     * Obtiene borradores con precios en un rango específico para una moneda
     *
     * @param string $currencyCode Código de la moneda
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceAutomatic> Borradores en el rango de precio
     *
     * @example
     * $automatic = $repository->getAutomaticsByPriceRange('USD', 10.00, 50.00);
     */
    public function getAutomaticsByPriceRange(string $currencyCode, float $minPrice, float $maxPrice, array $filters = []): Collection;

    /**
     * Actualiza el estado de un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $newStatus Nuevo estado ('ACTIVE', 'EXECUTED')
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateAutomaticStatus('123', 'EXECUTED');
     */
    public function updateAutomaticStatus(string $draftId, string $newStatus): bool;

    /**
     * Actualiza múltiples borradores a estado ejecutado
     *
     * @param array $draftIds IDs de los borradores
     * @return int Número de borradores actualizados
     *
     * @example
     * $executed = $repository->markAutomaticsAsExecuted(['1', '2', '3']);
     */
    public function markAutomaticsAsExecuted(array $draftIds): int;

    /**
     * Crea o actualiza un borrador (upsert)
     *
     * @param string|null $bingoRoomId ID de la sala de bingo
     * @param string|null $automaticBingoRoomId ID de la sala automática
     * @param string $creatorUserId ID del usuario creador
     * @param array $currencies Array de códigos de monedas
     * @param array $cardPrices Array asociativo de precios por moneda
     * @return BingoCardPriceAutomatic Borrador creado o actualizado
     *
     * @example
     * $draft = $repository->createOrUpdateAutomatic('123', null, '789', ['USD', 'EUR'], ['USD' => 25.00, 'EUR' => 22.00]);
     */
    public function createOrUpdateAutomatic(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId, array $currencies, array $cardPrices): BingoCardPriceAutomatic;

    /**
     * Elimina borradores ejecutados antiguos
     *
     * @param int $daysOld Días de antigüedad para considerar antiguos
     * @return int Número de borradores eliminados
     *
     * @example
     * $deleted = $repository->deleteOldExecutedAutomatics(30);
     */
    public function deleteOldExecutedAutomatics(int $daysOld = 30): int;

    /**
     * Obtiene estadísticas de borradores por usuario
     *
     * @param string $creatorUserId ID del usuario
     * @return array Estadísticas del usuario
     *
     * @example
     * $stats = $repository->getAutomaticStatsByCreator('789');
     * // ['total_automatic' => 15, 'active_automatic' => 5, 'executed_automatic' => 10]
     */
    public function getAutomaticStatsByCreator(string $creatorUserId): array;

    /**
     * Obtiene estadísticas generales de borradores
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralAutomaticStats();
     */
    public function getGeneralAutomaticStats(): array;

    /**
     * Obtiene borradores activos más recientes
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceAutomatic> Borradores más recientes
     *
     * @example
     * $recentAutomatics = $repository->getRecentActiveAutomatics(10);
     */
    public function getRecentActiveAutomatics(int $limit = 10, array $filters = []): Collection;

    /**
     * Cuenta borradores por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countAutomaticsByStatus();
     * // ['ACTIVE' => 25, 'EXECUTED' => 75]
     */
    public function countAutomaticsByStatus(array $filters = []): array;

    /**
     * Obtiene borradores creados en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceAutomatic> Borradores creados en el rango
     *
     * @example
     * $automatic = $repository->getAutomaticsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getAutomaticsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene todas las monedas utilizadas en los borradores
     *
     * @param array $filters Filtros adicionales
     * @return array Array único de códigos de monedas
     *
     * @example
     * $currencies = $repository->getAllCurrenciesUsedInAutomatics();
     * // ['USD', 'EUR', 'BRL', 'VES']
     */
    public function getAllCurrenciesUsedInAutomatics(array $filters = []): array;

    /**
     * Verifica si existe un borrador activo para una sala y usuario
     *
     * @param string|null $bingoRoomId ID de la sala de bingo
     * @param string|null $automaticBingoRoomId ID de la sala automática
     * @param string $creatorUserId ID del usuario creador
     * @return bool True si existe un borrador activo
     *
     * @example
     * $hasActive = $repository->hasActiveAutomaticForRoomAndCreator('123', null, '789');
     */
    public function hasActiveAutomaticForRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): bool;

    /**
     * Obtiene el precio de una moneda específica de un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $currencyCode Código de la moneda
     * @return float|null Precio encontrado o null
     *
     * @example
     * $usdPrice = $repository->getPriceFromAutomatic('123', 'USD');
     */
    public function getPriceFromAutomatic(string $draftId, string $currencyCode): ?float;

    /**
     * Actualiza el precio de una moneda específica en un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $currencyCode Código de la moneda
     * @param float $newPrice Nuevo precio
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePriceInAutomatic('123', 'USD', 30.00);
     */
    public function updatePriceInAutomatic(string $draftId, string $currencyCode, float $newPrice): bool;
}
