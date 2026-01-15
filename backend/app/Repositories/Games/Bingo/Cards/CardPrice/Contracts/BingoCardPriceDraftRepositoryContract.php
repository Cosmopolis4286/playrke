<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards\CardPrice\Contracts;

use App\Models\Bingo\Cards\CardPrice\BingoCardPriceDraft;
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
interface BingoCardPriceDraftRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los borradores de una sala de bingo específica
     *
     * @param string $bingoRoomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceDraft> Borradores de la sala
     *
     * @example
     * $drafts = $repository->getDraftsByBingoRoom('123', ['status' => 'ACTIVE']);
     */
    public function getDraftsByBingoRoom(string $bingoRoomId, array $filters = []): Collection;

    /**
     * Obtiene todos los borradores de una sala automática específica
     *
     * @param string $automaticBingoRoomId ID de la sala automática
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceDraft> Borradores de la sala automática
     *
     * @example
     * $drafts = $repository->getDraftsByAutomaticBingoRoom('456', ['status' => 'ACTIVE']);
     */
    public function getDraftsByAutomaticBingoRoom(string $automaticBingoRoomId, array $filters = []): Collection;

    /**
     * Obtiene borradores por usuario creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE']
     * @return Collection<BingoCardPriceDraft> Borradores del usuario
     *
     * @example
     * $drafts = $repository->getDraftsByCreator('789', ['status' => 'ACTIVE']);
     */
    public function getDraftsByCreator(string $creatorUserId, array $filters = []): Collection;

    /**
     * Obtiene borradores por estado
     *
     * @param string $status Estado del borrador ('ACTIVE', 'EXECUTED')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceDraft> Borradores con el estado especificado
     *
     * @example
     * $activeDrafts = $repository->getDraftsByStatus('ACTIVE');
     */
    public function getDraftsByStatus(string $status, array $filters = []): Collection;

    /**
     * Busca un borrador específico por sala y usuario
     *
     * @param string|null $bingoRoomId ID de la sala de bingo (opcional)
     * @param string|null $automaticBingoRoomId ID de la sala automática (opcional)
     * @param string $creatorUserId ID del usuario creador
     * @return BingoCardPriceDraft|null Borrador encontrado o null
     *
     * @example
     * $draft = $repository->findDraftByRoomAndCreator('123', null, '789');
     */
    public function findDraftByRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): ?BingoCardPriceDraft;

    /**
     * Obtiene borradores que contienen una moneda específica
     *
     * @param string $currencyCode Código de la moneda (ej: 'USD', 'EUR')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceDraft> Borradores que contienen la moneda
     *
     * @example
     * $draftsWithUSD = $repository->getDraftsByCurrency('USD');
     */
    public function getDraftsByCurrency(string $currencyCode, array $filters = []): Collection;

    /**
     * Obtiene borradores con precios en un rango específico para una moneda
     *
     * @param string $currencyCode Código de la moneda
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceDraft> Borradores en el rango de precio
     *
     * @example
     * $drafts = $repository->getDraftsByPriceRange('USD', 10.00, 50.00);
     */
    public function getDraftsByPriceRange(string $currencyCode, float $minPrice, float $maxPrice, array $filters = []): Collection;

    /**
     * Actualiza el estado de un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $newStatus Nuevo estado ('ACTIVE', 'EXECUTED')
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateDraftStatus('123', 'EXECUTED');
     */
    public function updateDraftStatus(string $draftId, string $newStatus): bool;

    /**
     * Actualiza múltiples borradores a estado ejecutado
     *
     * @param array $draftIds IDs de los borradores
     * @return int Número de borradores actualizados
     *
     * @example
     * $executed = $repository->markDraftsAsExecuted(['1', '2', '3']);
     */
    public function markDraftsAsExecuted(array $draftIds): int;

    /**
     * Crea o actualiza un borrador (upsert)
     *
     * @param string|null $bingoRoomId ID de la sala de bingo
     * @param string|null $automaticBingoRoomId ID de la sala automática
     * @param string $creatorUserId ID del usuario creador
     * @param array $currencies Array de códigos de monedas
     * @param array $cardPrices Array asociativo de precios por moneda
     * @return BingoCardPriceDraft Borrador creado o actualizado
     *
     * @example
     * $draft = $repository->createOrUpdateDraft('123', null, '789', ['USD', 'EUR'], ['USD' => 25.00, 'EUR' => 22.00]);
     */
    public function createOrUpdateDraft(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId, array $currencies, array $cardPrices): BingoCardPriceDraft;

    /**
     * Elimina borradores ejecutados antiguos
     *
     * @param int $daysOld Días de antigüedad para considerar antiguos
     * @return int Número de borradores eliminados
     *
     * @example
     * $deleted = $repository->deleteOldExecutedDrafts(30);
     */
    public function deleteOldExecutedDrafts(int $daysOld = 30): int;

    /**
     * Obtiene estadísticas de borradores por usuario
     *
     * @param string $creatorUserId ID del usuario
     * @return array Estadísticas del usuario
     *
     * @example
     * $stats = $repository->getDraftStatsByCreator('789');
     * // ['total_drafts' => 15, 'active_drafts' => 5, 'executed_drafts' => 10]
     */
    public function getDraftStatsByCreator(string $creatorUserId): array;

    /**
     * Obtiene estadísticas generales de borradores
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralDraftStats();
     */
    public function getGeneralDraftStats(): array;

    /**
     * Obtiene borradores activos más recientes
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceDraft> Borradores más recientes
     *
     * @example
     * $recentDrafts = $repository->getRecentActiveDrafts(10);
     */
    public function getRecentActiveDrafts(int $limit = 10, array $filters = []): Collection;

    /**
     * Cuenta borradores por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countDraftsByStatus();
     * // ['ACTIVE' => 25, 'EXECUTED' => 75]
     */
    public function countDraftsByStatus(array $filters = []): array;

    /**
     * Obtiene borradores creados en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPriceDraft> Borradores creados en el rango
     *
     * @example
     * $drafts = $repository->getDraftsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getDraftsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene todas las monedas utilizadas en los borradores
     *
     * @param array $filters Filtros adicionales
     * @return array Array único de códigos de monedas
     *
     * @example
     * $currencies = $repository->getAllCurrenciesUsedInDrafts();
     * // ['USD', 'EUR', 'BRL', 'VES']
     */
    public function getAllCurrenciesUsedInDrafts(array $filters = []): array;

    /**
     * Verifica si existe un borrador activo para una sala y usuario
     *
     * @param string|null $bingoRoomId ID de la sala de bingo
     * @param string|null $automaticBingoRoomId ID de la sala automática
     * @param string $creatorUserId ID del usuario creador
     * @return bool True si existe un borrador activo
     *
     * @example
     * $hasActive = $repository->hasActiveDraftForRoomAndCreator('123', null, '789');
     */
    public function hasActiveDraftForRoomAndCreator(?string $bingoRoomId, ?string $automaticBingoRoomId, string $creatorUserId): bool;

    /**
     * Obtiene el precio de una moneda específica de un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $currencyCode Código de la moneda
     * @return float|null Precio encontrado o null
     *
     * @example
     * $usdPrice = $repository->getPriceFromDraft('123', 'USD');
     */
    public function getPriceFromDraft(string $draftId, string $currencyCode): ?float;

    /**
     * Actualiza el precio de una moneda específica en un borrador
     *
     * @param string $draftId ID del borrador
     * @param string $currencyCode Código de la moneda
     * @param float $newPrice Nuevo precio
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePriceInDraft('123', 'USD', 30.00);
     */
    public function updatePriceInDraft(string $draftId, string $currencyCode, float $newPrice): bool;
}