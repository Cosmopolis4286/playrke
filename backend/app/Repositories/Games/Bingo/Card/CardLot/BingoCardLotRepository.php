<?php

namespace App\Repositories\Games\Bingo\Card\CardLot;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Contracts\Card\CardLot\BingoCardLotRepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de lotes de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los lotes de cartones de bingo, incluyendo consultas complejas y estadísticas.
 *
 * @package App\Repositories\Games\Bingo\Card\CardLot
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
class BingoCardLotRepository extends BaseRepository implements BingoCardLotRepositoryInterface
{
    public function __construct(BingoCardLot $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene todos los lotes de una sala específica
     */
    public function getLotsByRoom(string $roomId, array $filters = []): Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->whereHas('roomUser', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['roomUser.user', 'seller', 'currency', 'room'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene todos los lotes de un usuario en una sala
     */
    public function getLotsByRoomUser(string $roomUserId, ?bool $isActive = null): Collection
    {
        $query = $this->model->where('room_user_id', $roomUserId);

        if ($isActive !== null) {
            $query->where('status', $isActive);
        }

        return $query->with(['room', 'seller', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene lotes por vendedor
     */
    public function getLotsBySeller(string $sellerId, array $filters = []): Collection
    {
        $query = $this->model->where('seller_id', $sellerId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['room', 'roomUser.user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene lotes por moneda
     */
    public function getLotsByCurrency(string $currencyId, array $filters = []): Collection
    {
        $query = $this->model->where('currency_id', $currencyId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['room', 'roomUser.user', 'seller'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene lotes creados en un rango de fechas
     */
    public function getLotsByDateRange(string $dateFrom, string $dateTo, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$dateFrom, $dateTo]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['room', 'roomUser.user', 'seller', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene estadísticas de lotes por sala
     */
    public function getLotStatsByRoom(string $roomId): array
    {
        $stats = $this->model->where('room_id', $roomId)
            ->where('status', true)
            ->selectRaw('
                                COUNT(*) as total_lots,
                                SUM(card_count) as total_cards,
                                SUM(sale_amount) as total_amount,
                                AVG(sale_amount) as avg_amount,
                                SUM(commission_amount) as total_commission
                            ')
            ->first();

        return [
            'total_lots' => $stats->total_lots ?? 0,
            'total_cards' => $stats->total_cards ?? 0,
            'total_amount' => floatval($stats->total_amount ?? 0),
            'avg_amount' => floatval($stats->avg_amount ?? 0),
            'total_commission' => floatval($stats->total_commission ?? 0),
        ];
    }

    /**
     * Obtiene estadísticas de lotes por usuario
     */
    public function getLotStatsByUser(string $roomUserId): array
    {
        $stats = $this->model->where('room_user_id', $roomUserId)
            ->where('status', true)
            ->selectRaw('
                                COUNT(*) as total_lots,
                                SUM(card_count) as total_cards,
                                SUM(sale_amount) as total_spent,
                                AVG(sale_amount) as avg_spent
                            ')
            ->first();

        return [
            'total_lots' => $stats->total_lots ?? 0,
            'total_cards' => $stats->total_cards ?? 0,
            'total_spent' => floatval($stats->total_spent ?? 0),
            'avg_spent' => floatval($stats->avg_spent ?? 0),
        ];
    }

    /**
     * Cambia el estado de múltiples lotes
     */
    public function changeLotsStatus(array $lotIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $lotIds)
            ->update(['status' => $isActive]);
    }

    /**
     * Obtiene lotes activos de una sala
     */
    public function getActiveLotsInRoom(string $roomId, array $columns = ['*']): Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('status', true)
            ->select($columns)
            ->with(['roomUser.user', 'seller', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene lotes inactivos de una sala
     */
    public function getInactiveLotsInRoom(string $roomId, array $columns = ['*']): Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('status', false)
            ->select($columns)
            ->with(['roomUser.user', 'seller', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calcula el total de comisiones por vendedor en un período
     */
    public function calculateSellerCommissions(string $sellerId, string $dateFrom, string $dateTo): float
    {
        $result = $this->model->where('seller_id', $sellerId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', true)
            ->sum('commission_amount');

        return floatval($result ?? 0);
    }

    /**
     * Obtiene el lote más reciente de un usuario en una sala
     */
    public function getLatestUserLot(string $roomUserId, ?bool $isActive = null): ?BingoCardLot
    {
        $query = $this->model->where('room_user_id', $roomUserId);

        if ($isActive !== null) {
            $query->where('status', $isActive);
        }

        return $query->with(['room', 'seller', 'currency'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Busca lotes por monto de venta
     */
    public function getLotsBySaleAmount(float $minAmount, float $maxAmount, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('sale_amount', [$minAmount, $maxAmount]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['room', 'roomUser.user', 'seller', 'currency'])
            ->orderBy('sale_amount', 'desc')
            ->get();
    }

    /**
     * Cuenta los lotes por estado en una sala
     */
    public function countLotsByStatus(string $roomId): array
    {
        $counts = $this->model->where('room_id', $roomId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'active' => $counts[1] ?? 0,
            'inactive' => $counts[0] ?? 0,
        ];
    }
}
