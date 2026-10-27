<?php

namespace App\Domains\Games\Bingo\Repositories\Card\CardPrice;

use App\Domains\Games\Bingo\Contracts\Card\CardPrice\BingoCardPriceRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de precios de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los precios de cartones de bingo, incluyendo consultas complejas y estadísticas.
 *
 * @author Sistema de Gestión de Bingo
 *
 * @version 1.0
 */
class BingoCardPriceRepository extends BaseRepository implements BingoCardPriceRepositoryInterface
{
    public function __construct(BingoCardPrice $model)
    {
        $this->model = $model;
    }

    /**
     * Actualiza múltiples precios de forma masiva
     */
    public function bulkUpdatePrices(array $priceData, string $creatorUserId) : int
    {
        $updatedCount = 0;

        DB::transaction(function() use ($priceData, $creatorUserId, &$updatedCount) {
            foreach ($priceData as $data) {
                if (isset($data['room_id'], $data['currency_id'], $data['price'])) {
                    $this->createOrUpdatePrice(
                        $data['room_id'],
                        $data['currency_id'],
                        $data['price'],
                        $creatorUserId,
                    );
                    $updatedCount++;
                }
            }
        });

        return $updatedCount;
    }

    /**
     * Cuenta precios por moneda
     */
    public function countPricesByCurrency(array $filters = []) : array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->join('currencies', 'bingo_card_prices.currency_id', '=', 'currencies.id')
            ->select('currencies.code as currency_code', DB::raw('count(*) as total'))
            ->groupBy('currencies.code')
            ->pluck('total', 'currency_code')
            ->toArray();
    }

    /**
     * Crea o actualiza un precio (upsert)
     */
    public function createOrUpdatePrice(string $roomId, string $currencyId, float $price, string $creatorUserId) : BingoCardPrice
    {
        return $this->model->updateOrCreate(
            [
                'room_id' => $roomId,
                'currency_id' => $currencyId,
            ],
            [
                'card_price' => $price,
                'creator_user_id' => $creatorUserId,
            ],
        );
    }

    /**
     * Elimina precios de una sala específica
     */
    public function deletePricesByRoom(string $roomId, array $filters = []) : int
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros adicionales
        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->delete();
    }

    /**
     * Busca un precio específico por sala y moneda
     */
    public function findPriceByRoomAndCurrency(string $roomId, string $currencyId) : ?BingoCardPrice
    {
        return $this->model->where('room_id', $roomId)
            ->where('currency_id', $currencyId)
            ->with(['room', 'creatorUser', 'currency'])
            ->first();
    }

    /**
     * Obtiene el precio promedio por sala
     */
    public function getAveragePriceByRoom(string $roomId) : float
    {
        $average = $this->model->where('room_id', $roomId)
            ->avg('card_price');

        return floatval($average ?? 0);
    }

    /**
     * Obtiene todas las monedas que tienen precios configurados para una sala
     */
    public function getCurrenciesWithPricesForRoom(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->with('currency')
            ->get()
            ->pluck('currency')
            ->unique('id')
            ->values();
    }

    /**
     * Obtiene estadísticas generales de precios
     */
    public function getGeneralPriceStats() : array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_prices,
                                COUNT(DISTINCT room_id) as total_rooms,
                                COUNT(DISTINCT currency_id) as total_currencies,
                                AVG(card_price) as avg_price,
                                MIN(card_price) as min_price,
                                MAX(card_price) as max_price
                            ')
            ->first();

        return [
            'total_prices' => $stats->total_prices ?? 0,
            'total_rooms' => $stats->total_rooms ?? 0,
            'total_currencies' => $stats->total_currencies ?? 0,
            'average_price' => floatval($stats->avg_price ?? 0),
            'minimum_price' => floatval($stats->min_price ?? 0),
            'maximum_price' => floatval($stats->max_price ?? 0),
        ];
    }

    /**
     * Obtiene precios más altos
     */
    public function getHighestPrices(int $limit = 10, array $filters = []) : Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['room', 'currency'])
            ->orderBy('card_price', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene precios más bajos
     */
    public function getLowestPrices(int $limit = 10, array $filters = []) : Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['room', 'currency'])
            ->orderBy('card_price', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene precios por usuario creador
     */
    public function getPricesByCreator(string $creatorUserId, array $filters = []) : Collection
    {
        $query = $this->model->where('creator_user_id', $creatorUserId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['room', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene precios por moneda
     */
    public function getPricesByCurrency(string $currencyId, array $filters = []) : Collection
    {
        $query = $this->model->where('currency_id', $currencyId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('card_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('card_price', '<=', $filters['max_price']);
        }

        return $query->with(['room', 'creatorUser', 'currency'])
            ->orderBy('card_price', 'desc')
            ->get();
    }

    /**
     * Obtiene precios creados en un rango de fechas
     */
    public function getPricesByDateRange(string $startDate, string $endDate, array $filters = []) : Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['room', 'creatorUser', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene precios en un rango específico
     */
    public function getPricesByRange(float $minPrice, float $maxPrice, array $filters = []) : Collection
    {
        $query = $this->model->whereBetween('card_price', [$minPrice, $maxPrice]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['room', 'creatorUser', 'currency'])
            ->orderBy('card_price', 'asc')
            ->get();
    }

    /**
     * Obtiene todos los precios de una sala específica
     */
    public function getPricesByRoom(string $roomId, array $filters = []) : Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('card_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('card_price', '<=', $filters['max_price']);
        }

        return $query->with(['room', 'creatorUser', 'currency'])
            ->orderBy('card_price', 'asc')
            ->get();
    }

    /**
     * Obtiene estadísticas de precios por sala
     */
    public function getPriceStatsByRoom(string $roomId) : array
    {
        $stats = $this->model->where('room_id', $roomId)
            ->selectRaw('
                                COUNT(*) as total_currencies,
                                AVG(card_price) as avg_price,
                                MIN(card_price) as min_price,
                                MAX(card_price) as max_price,
                                SUM(card_price) as total_price
                            ')
            ->first();

        return [
            'total_currencies' => $stats->total_currencies ?? 0,
            'average_price' => floatval($stats->avg_price ?? 0),
            'minimum_price' => floatval($stats->min_price ?? 0),
            'maximum_price' => floatval($stats->max_price ?? 0),
            'total_price' => floatval($stats->total_price ?? 0),
        ];
    }

    /**
     * Verifica si existe un precio para una sala y moneda específica
     */
    public function priceExistsForRoomAndCurrency(string $roomId, string $currencyId) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where('currency_id', $currencyId)
            ->exists();
    }

    /**
     * Actualiza el precio de un cartón para una sala y moneda específica
     */
    public function updatePriceByRoomAndCurrency(string $roomId, string $currencyId, float $newPrice, string $creatorUserId) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where('currency_id', $currencyId)
            ->update([
                'card_price' => $newPrice,
                'creator_user_id' => $creatorUserId,
                'updated_at' => now(),
            ]) > 0;
    }
}
