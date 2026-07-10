<?php

namespace App\Domains\Games\Bingo\Repositories\Cards\CardPrize;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Repositories\Cards\CardPrize\Contracts\BingoCardPrizeRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Repositorio para la gestión de premios de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los premios de cartones de bingo.
 *
 * @package App\Repositories\Bingo\Card\CardPrize
 * @author Sistema de Gestión de Premios de Bingo
 * @version 1.0
 */
class BingoCardPrizeRepository extends BaseRepository implements BingoCardPrizeRepositoryContract
{
    public function __construct(BingoCardPrize $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene todos los premios de una sala específica
     */
    public function getPrizesByRoom(string $roomId, array $filters = []): Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        if (isset($filters['prize_type'])) {
            $query->where('prize_type', $filters['prize_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['game_round'])) {
            $query->where('game_round', $filters['game_round']);
        }

        return $query->with(['room', 'creatorUser', 'seller', 'roomUser', 'cardLot', 'winningCard', 'currency', 'payments'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por creador
     */
    public function getPrizesByCreator(string $creatorUserId, array $filters = []): Collection
    {
        $query = $this->model->where('creator_user_id', $creatorUserId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por estado
     */
    public function getPrizesByStatus(string $status, array $filters = []): Collection
    {
        $query = $this->model->where('prize_status', $status);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por tipo
     */
    public function getPrizesByType(string $prizeType, array $filters = []): Collection
    {
        $query = $this->model->where('prize_type', $prizeType);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Busca un premio por cartón ganador
     */
    public function findByWinningCard(string $cardId, array $filters = []): ?BingoCardPrize
    {
        $query = $this->model->where('winning_card_id', $cardId);

        // Aplicar filtros adicionales
        if (isset($filters['game_round'])) {
            $query->where('game_round', $filters['game_round']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency', 'payments'])
                    ->first();
    }

    /**
     * Busca un premio por código de verificación
     */
    public function findByVerificationCode(string $verificationCode): ?BingoCardPrize
    {
        return $this->model->where('verification_code', $verificationCode)
                          ->with(['room', 'creatorUser', 'winningCard', 'currency', 'payments'])
                          ->first();
    }

    /**
     * Obtiene premios activos
     */
    public function getActivePrizes(array $filters = []): Collection
    {
        $query = $this->model->where('status', true);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por ronda de juego
     */
    public function getPrizesByGameRound(string $gameRound, array $filters = []): Collection
    {
        $query = $this->model->where('game_round', $gameRound);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por lote de cartones
     */
    public function getPrizesByCardLot(string $cardLotId, array $filters = []): Collection
    {
        $query = $this->model->where('card_lot_id', $cardLotId);

        // Aplicar filtros adicionales
        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['room', 'creatorUser', 'cardLot', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Actualiza el estado de un premio
     */
    public function updatePrizeStatus(string $prizeId, string $newStatus): bool
    {
        $updateData = ['prize_status' => $newStatus];

        // Si el estado es "claimed", también actualizar la fecha de reclamo
        if ($newStatus === 'claimed') {
            $updateData['claimed_at'] = Carbon::now();
        }

        return $this->model->where('id', $prizeId)
                          ->update($updateData) > 0;
    }

    /**
     * Marca un premio como reclamado
     */
    public function markPrizeAsClaimed(string $prizeId): bool
    {
        return $this->model->where('id', $prizeId)
                          ->update([
                              'prize_status' => 'claimed',
                              'claimed_at' => Carbon::now(),
                          ]) > 0;
    }

    /**
     * Activa o desactiva un premio
     */
    public function togglePrizeActivation(string $prizeId, bool $isActive): bool
    {
        return $this->model->where('id', $prizeId)
                          ->update(['status' => $isActive]) > 0;
    }

    /**
     * Verifica si existe un premio para un cartón en una ronda específica
     */
    public function existsPrizeForCardInRound(string $cardId, string $gameRound): bool
    {
        return $this->model->where('winning_card_id', $cardId)
                          ->where('game_round', $gameRound)
                          ->exists();
    }

    /**
     * Obtiene estadísticas de premios por sala
     */
    public function getPrizeStatsByRoom(string $roomId): array
    {
        $stats = $this->model->where('room_id', $roomId)
                            ->selectRaw('
                                COUNT(*) as total_prizes,
                                SUM(CASE WHEN prize_status = "claimed" THEN 1 ELSE 0 END) as claimed_prizes,
                                SUM(CASE WHEN prize_status = "waiting" THEN 1 ELSE 0 END) as waiting_prizes,
                                SUM(CASE WHEN prize_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_prizes,
                                SUM(CASE WHEN prize_status = "suspended" THEN 1 ELSE 0 END) as suspended_prizes,
                                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_prizes,
                                SUM(usd_amount) as total_usd_value,
                                AVG(usd_amount) as average_usd_value
                            ')
                            ->first();

        return [
            'total_prizes' => $stats->total_prizes ?? 0,
            'claimed_prizes' => $stats->claimed_prizes ?? 0,
            'waiting_prizes' => $stats->waiting_prizes ?? 0,
            'cancelled_prizes' => $stats->cancelled_prizes ?? 0,
            'suspended_prizes' => $stats->suspended_prizes ?? 0,
            'active_prizes' => $stats->active_prizes ?? 0,
            'total_usd_value' => floatval($stats->total_usd_value ?? 0),
            'average_usd_value' => floatval($stats->average_usd_value ?? 0),
        ];
    }

    /**
     * Obtiene estadísticas de premios por creador
     */
    public function getPrizeStatsByCreator(string $creatorUserId): array
    {
        $stats = $this->model->where('creator_user_id', $creatorUserId)
                            ->selectRaw('
                                COUNT(*) as total_prizes,
                                SUM(CASE WHEN prize_status = "claimed" THEN 1 ELSE 0 END) as claimed_prizes,
                                SUM(CASE WHEN prize_status = "waiting" THEN 1 ELSE 0 END) as waiting_prizes,
                                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_prizes,
                                SUM(usd_amount) as total_usd_value
                            ')
                            ->first();

        return [
            'total_prizes' => $stats->total_prizes ?? 0,
            'claimed_prizes' => $stats->claimed_prizes ?? 0,
            'waiting_prizes' => $stats->waiting_prizes ?? 0,
            'active_prizes' => $stats->active_prizes ?? 0,
            'total_usd_value' => floatval($stats->total_usd_value ?? 0),
        ];
    }

    /**
     * Cuenta premios por tipo
     */
    public function countPrizesByType(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        $counts = $query->select('prize_type', DB::raw('count(*) as total'))
                       ->groupBy('prize_type')
                       ->pluck('total', 'prize_type')
                       ->toArray();

        // Asegurar que todos los tipos estén presentes
        $validTypes = ['full_house', 'single_line', 'serial'];
        foreach ($validTypes as $type) {
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
        }

        return $counts;
    }

    /**
     * Cuenta premios por estado
     */
    public function countPrizesByStatus(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        $counts = $query->select('prize_status', DB::raw('count(*) as total'))
                       ->groupBy('prize_status')
                       ->pluck('total', 'prize_status')
                       ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['waiting', 'claimed', 'cancelled', 'suspended'];
        foreach ($validStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Obtiene premios con mayor valor
     */
    public function getHighestValuePrizes(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('usd_amount', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtiene premios recientes
     */
    public function getRecentPrizes(int $limit = 20, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtiene premios por rango de fechas
     */
    public function getPrizesByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por moneda
     */
    public function getPrizesByCurrency(string $currencyId, array $filters = []): Collection
    {
        $query = $this->model->where('currency_id', $currencyId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene el valor total de premios
     */
    public function getTotalPrizesValue(array $filters = [], string $currency = 'usd'): float
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        if (isset($filters['creator_user_id'])) {
            $query->where('creator_user_id', $filters['creator_user_id']);
        }

        $column = $currency === 'usd' ? 'usd_amount' : 'local_amount';

        return floatval($query->sum($column));
    }

    /**
     * Obtiene ganadores únicos
     */
    public function getUniqueWinners(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        return $query->distinct()
                    ->pluck('room_user_id');
    }

    /**
     * Obtiene premios pendientes de pago por creador
     */
    public function getPendingPaymentsByCreator(string $creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)
                          ->where('status', true)
                          ->where('prize_status', 'waiting')
                          ->with(['room', 'creatorUser', 'winningCard', 'currency'])
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Genera código de verificación único para un premio
     */
    public function generateVerificationCode(string $prizeId): ?string
    {
        $prize = $this->model->find($prizeId);

        if (!$prize) {
            return null;
        }

        $verificationCode = strtoupper(bin2hex(random_bytes(4)));

        while ($this->model->where('verification_code', $verificationCode)->exists()) {
            $verificationCode = strtoupper(bin2hex(random_bytes(4)));
        }

        $prize->update(['verification_code' => $verificationCode]);

        return $verificationCode;
    }

    /**
     * Actualiza múltiples premios en lote
     */
    public function bulkUpdatePrizes(array $prizeIds, array $updateData): int
    {
        return $this->model->whereIn('id', $prizeIds)
                          ->update($updateData);
    }

    /**
     * Elimina premios cancelados antiguos
     */
    public function deleteOldCancelledPrizes(int $daysOld = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('prize_status', 'cancelled')
                          ->where('updated_at', '<', $cutoffDate)
                          ->delete();
    }

    /**
     * Obtiene premios por vendedor
     */
    public function getPrizesBySeller(string $sellerId, array $filters = []): Collection
    {
        $query = $this->model->where('seller_id', $sellerId);

        // Aplicar filtros
        if (isset($filters['prize_status'])) {
            $query->where('prize_status', $filters['prize_status']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['room', 'seller', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene premios por patrón ganador
     */
    public function getPrizesByWinningPattern(string $pattern, array $filters = []): Collection
    {
        $query = $this->model->where('winning_pattern', 'LIKE', "%{$pattern}%");

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['prize_type'])) {
            $query->where('prize_type', $filters['prize_type']);
        }

        return $query->with(['room', 'creatorUser', 'winningCard', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }
}