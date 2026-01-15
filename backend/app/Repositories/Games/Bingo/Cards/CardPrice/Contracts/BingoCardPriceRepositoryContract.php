<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards\CardPrice\Contracts;

use App\Models\Bingo\Cards\CardPrice\BingoCardPrice;
use App\Repositories\Base\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de precios de cartones de bingo
 *
 * Define los métodos disponibles para gestionar precios de cartones de bingo,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Cards\CardPrice
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
interface BingoCardPriceRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene todos los precios de una sala específica
     *
     * @param string $roomId ID de la sala de bingo
     * @param array $filters Filtros adicionales ['currency_id' => '...']
     * @return Collection<BingoCardPrice> Precios de la sala
     *
     * @example
     * $prices = $repository->getPricesByRoom('123', ['currency_id' => 'USD']);
     */
    public function getPricesByRoom(string $roomId, array $filters = []): Collection;

    /**
     * Obtiene precios por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array $filters Filtros adicionales ['room_id' => '...']
     * @return Collection<BingoCardPrice> Precios con la moneda especificada
     *
     * @example
     * $prices = $repository->getPricesByCurrency('USD');
     */
    public function getPricesByCurrency(string $currencyId, array $filters = []): Collection;

    /**
     * Busca un precio específico por sala y moneda
     *
     * @param string $roomId ID de la sala
     * @param string $currencyId ID de la moneda
     * @return BingoCardPrice|null Precio encontrado o null
     *
     * @example
     * $price = $repository->findPriceByRoomAndCurrency('123', 'USD');
     */
    public function findPriceByRoomAndCurrency(string $roomId, string $currencyId): ?BingoCardPrice;

    /**
     * Obtiene precios por usuario creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $filters Filtros adicionales ['room_id' => '...']
     * @return Collection<BingoCardPrice> Precios creados por el usuario
     *
     * @example
     * $prices = $repository->getPricesByCreator('456');
     */
    public function getPricesByCreator(string $creatorUserId, array $filters = []): Collection;

    /**
     * Obtiene precios en un rango específico
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrice> Precios en el rango
     *
     * @example
     * $prices = $repository->getPricesByRange(10.00, 50.00);
     */
    public function getPricesByRange(float $minPrice, float $maxPrice, array $filters = []): Collection;

    /**
     * Actualiza el precio de un cartón para una sala y moneda específica
     *
     * @param string $roomId ID de la sala
     * @param string $currencyId ID de la moneda
     * @param float $newPrice Nuevo precio
     * @param string $creatorUserId ID del usuario que actualiza
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePriceByRoomAndCurrency('123', 'USD', 25.00, '456');
     */
    public function updatePriceByRoomAndCurrency(string $roomId, string $currencyId, float $newPrice, string $creatorUserId): bool;

    /**
     * Crea o actualiza un precio (upsert)
     *
     * @param string $roomId ID de la sala
     * @param string $currencyId ID de la moneda
     * @param float $price Precio del cartón
     * @param string $creatorUserId ID del usuario creador
     * @return BingoCardPrice Precio creado o actualizado
     *
     * @example
     * $price = $repository->createOrUpdatePrice('123', 'USD', 30.00, '456');
     */
    public function createOrUpdatePrice(string $roomId, string $currencyId, float $price, string $creatorUserId): BingoCardPrice;

    /**
     * Elimina precios de una sala específica
     *
     * @param string $roomId ID de la sala
     * @param array $filters Filtros adicionales ['currency_id' => '...']
     * @return int Número de precios eliminados
     *
     * @example
     * $deleted = $repository->deletePricesByRoom('123', ['currency_id' => 'USD']);
     */
    public function deletePricesByRoom(string $roomId, array $filters = []): int;

    /**
     * Obtiene estadísticas de precios por sala
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas de precios
     *
     * @example
     * $stats = $repository->getPriceStatsByRoom('123');
     * // ['total_currencies' => 3, 'avg_price' => 25.00, 'min_price' => 10.00, 'max_price' => 50.00]
     */
    public function getPriceStatsByRoom(string $roomId): array;

    /**
     * Obtiene estadísticas generales de precios
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralPriceStats();
     */
    public function getGeneralPriceStats(): array;

    /**
     * Obtiene precios más altos
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrice> Precios más altos
     *
     * @example
     * $highestPrices = $repository->getHighestPrices(10);
     */
    public function getHighestPrices(int $limit = 10, array $filters = []): Collection;

    /**
     * Obtiene precios más bajos
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrice> Precios más bajos
     *
     * @example
     * $lowestPrices = $repository->getLowestPrices(10);
     */
    public function getLowestPrices(int $limit = 10, array $filters = []): Collection;

    /**
     * Cuenta precios por moneda
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por moneda
     *
     * @example
     * $counts = $repository->countPricesByCurrency();
     * // ['USD' => 25, 'EUR' => 18, 'VES' => 30]
     */
    public function countPricesByCurrency(array $filters = []): array;

    /**
     * Obtiene precios creados en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrice> Precios creados en el rango
     *
     * @example
     * $prices = $repository->getPricesByDateRange('2024-01-01', '2024-01-31');
     */
    public function getPricesByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene el precio promedio por sala
     *
     * @param string $roomId ID de la sala
     * @return float Precio promedio
     *
     * @example
     * $avgPrice = $repository->getAveragePriceByRoom('123');
     */
    public function getAveragePriceByRoom(string $roomId): float;

    /**
     * Verifica si existe un precio para una sala y moneda específica
     *
     * @param string $roomId ID de la sala
     * @param string $currencyId ID de la moneda
     * @return bool True si existe el precio
     *
     * @example
     * $exists = $repository->priceExistsForRoomAndCurrency('123', 'USD');
     */
    public function priceExistsForRoomAndCurrency(string $roomId, string $currencyId): bool;

    /**
     * Obtiene todas las monedas que tienen precios configurados para una sala
     *
     * @param string $roomId ID de la sala
     * @return Collection Monedas con precios configurados
     *
     * @example
     * $currencies = $repository->getCurrenciesWithPricesForRoom('123');
     */
    public function getCurrenciesWithPricesForRoom(string $roomId): Collection;

    /**
     * Actualiza múltiples precios de forma masiva
     *
     * @param array $priceData Array con datos de precios [['room_id' => '...', 'currency_id' => '...', 'price' => 25.00]]
     * @param string $creatorUserId ID del usuario que actualiza
     * @return int Número de precios actualizados
     *
     * @example
     * $updated = $repository->bulkUpdatePrices($pricesData, '456');
     */
    public function bulkUpdatePrices(array $priceData, string $creatorUserId): int;
}