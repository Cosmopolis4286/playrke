<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards\CardPrize;

use App\Models\Games\Bingo\Cards\CardPrize\BingoCardPrizePayment;
use App\Repositories\Games\Bingo\Cards\CardPrize\Contracts\BingoCardPrizePaymentRepositoryContract;
use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Repositorio para la gestión de pagos de premios de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los pagos de premios de cartones de bingo.
 *
 * @package App\Repositories\Games\Bingo\Cards\CardPrize
 * @author Sistema de Gestión de Pagos de Premios de Bingo
 * @version 1.0
 */
class BingoCardPrizePaymentRepository extends BaseRepository implements BingoCardPrizePaymentRepositoryContract
{
    public function __construct(BingoCardPrizePayment $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene todos los pagos de un premio específico
     */
    public function getPaymentsByPrize(string $prizeId, array $filters = []): Collection
    {
        $query = $this->model->where('card_prize_id', $prizeId);

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por sala
     */
    public function getPaymentsByRoom(string $roomId, array $filters = []): Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por creador
     */
    public function getPaymentsByCreator(string $creatorUserId, array $filters = []): Collection
    {
        $query = $this->model->where('creator_user_id', $creatorUserId);

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por estado
     */
    public function getPaymentsByStatus(string $status, array $filters = []): Collection
    {
        $query = $this->model->where('payment_status', $status);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por método de pago
     */
    public function getPaymentsByMethod(string $paymentMethod, array $filters = []): Collection
    {
        $query = $this->model->where('payment_method', $paymentMethod);

        // Aplicar filtros adicionales
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por vendedor
     */
    public function getPaymentsBySeller(string $sellerId, array $filters = []): Collection
    {
        $query = $this->model->where('seller_id', $sellerId);

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->with(['cardPrize', 'room', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por usuario receptor
     */
    public function getPaymentsByUser(string $userId, array $filters = []): Collection
    {
        $query = $this->model->where('user_id', $userId);

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Busca un pago por referencia de transacción
     */
    public function findByTransactionReference(string $transactionReference): ?BingoCardPrizePayment
    {
        return $this->model->where('transaction_reference', $transactionReference)
                          ->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                          ->first();
    }

    /**
     * Obtiene pagos completados
     */
    public function getCompletedPayments(array $filters = []): Collection
    {
        $query = $this->model->where('payment_status', 'completed');

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('processed_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos pendientes
     */
    public function getPendingPayments(array $filters = []): Collection
    {
        $query = $this->model->where('payment_status', 'pending');

        // Aplicar filtros adicionales
        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    /**
     * Actualiza el estado de un pago
     */
    public function updatePaymentStatus(string $paymentId, string $newStatus): bool
    {
        $updateData = ['payment_status' => $newStatus];

        // Si el estado es un estado final, actualizar fecha de procesamiento
        if (in_array($newStatus, ['completed', 'failed', 'cancelled'])) {
            $updateData['processed_at'] = Carbon::now();
        }

        return $this->model->where('id', $paymentId)
                          ->update($updateData) > 0;
    }

    /**
     * Marca un pago como completado
     */
    public function markPaymentAsCompleted(string $paymentId): bool
    {
        return $this->model->where('id', $paymentId)
                          ->update([
                              'payment_status' => 'completed',
                              'processed_at' => Carbon::now(),
                          ]) > 0;
    }

    /**
     * Marca un pago como fallido
     */
    public function markPaymentAsFailed(string $paymentId): bool
    {
        return $this->model->where('id', $paymentId)
                          ->update([
                              'payment_status' => 'failed',
                              'processed_at' => Carbon::now(),
                          ]) > 0;
    }

    /**
     * Obtiene el total de pagos completados para un premio
     */
    public function getTotalCompletedPaymentsForPrize(string $prizeId): float
    {
        return floatval(
            $this->model->where('card_prize_id', $prizeId)
                        ->where('payment_status', 'completed')
                        ->sum('payment_amount')
        );
    }

    /**
     * Obtiene estadísticas de pagos por sala
     */
    public function getPaymentStatsByRoom(string $roomId): array
    {
        $stats = $this->model->where('room_id', $roomId)
                            ->selectRaw('
                                COUNT(*) as total_payments,
                                SUM(CASE WHEN payment_status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                                SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                                SUM(CASE WHEN payment_status = "failed" THEN 1 ELSE 0 END) as failed_payments,
                                SUM(CASE WHEN payment_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_payments,
                                SUM(CASE WHEN payment_status = "completed" THEN payment_amount ELSE 0 END) as total_completed_amount,
                                AVG(CASE WHEN payment_status = "completed" THEN payment_amount ELSE NULL END) as average_payment_amount
                            ')
                            ->first();

        return [
            'total_payments' => $stats->total_payments ?? 0,
            'completed_payments' => $stats->completed_payments ?? 0,
            'pending_payments' => $stats->pending_payments ?? 0,
            'failed_payments' => $stats->failed_payments ?? 0,
            'cancelled_payments' => $stats->cancelled_payments ?? 0,
            'total_completed_amount' => floatval($stats->total_completed_amount ?? 0),
            'average_payment_amount' => floatval($stats->average_payment_amount ?? 0),
        ];
    }

    /**
     * Obtiene estadísticas de pagos por vendedor
     */
    public function getPaymentStatsBySeller(string $sellerId): array
    {
        $stats = $this->model->where('seller_id', $sellerId)
                            ->selectRaw('
                                COUNT(*) as total_payments,
                                SUM(CASE WHEN payment_status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                                SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                                SUM(CASE WHEN payment_status = "completed" THEN payment_amount ELSE 0 END) as total_amount
                            ')
                            ->first();

        return [
            'total_payments' => $stats->total_payments ?? 0,
            'completed_payments' => $stats->completed_payments ?? 0,
            'pending_payments' => $stats->pending_payments ?? 0,
            'total_amount' => floatval($stats->total_amount ?? 0),
        ];
    }

    /**
     * Cuenta pagos por estado
     */
    public function countPaymentsByStatus(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        $counts = $query->select('payment_status', DB::raw('count(*) as total'))
                       ->groupBy('payment_status')
                       ->pluck('total', 'payment_status')
                       ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['pending', 'completed', 'failed', 'cancelled'];
        foreach ($validStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Cuenta pagos por método
     */
    public function countPaymentsByMethod(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $counts = $query->select('payment_method', DB::raw('count(*) as total'))
                       ->groupBy('payment_method')
                       ->pluck('total', 'payment_method')
                       ->toArray();

        // Asegurar que todos los métodos estén presentes
        $validMethods = ['seller_cash', 'credit_card', 'system_payment'];
        foreach ($validMethods as $method) {
            if (!isset($counts[$method])) {
                $counts[$method] = 0;
            }
        }

        return $counts;
    }

    /**
     * Obtiene pagos recientes
     */
    public function getRecentPayments(int $limit = 20, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtiene pagos por rango de fechas
     */
    public function getPaymentsByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por moneda
     */
    public function getPaymentsByCurrency(string $currencyId, array $filters = []): Collection
    {
        $query = $this->model->where('currency_id', $currencyId);

        // Aplicar filtros adicionales
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene el valor total de pagos
     */
    public function getTotalPaymentsValue(array $filters = []): float
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return floatval($query->sum('payment_amount'));
    }

    /**
     * Obtiene pagos procesados
     */
    public function getProcessedPayments(array $filters = []): Collection
    {
        $query = $this->model->whereNotNull('processed_at');

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('processed_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos sin procesar
     */
    public function getUnprocessedPayments(array $filters = []): Collection
    {
        $query = $this->model->whereNull('processed_at');

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    /**
     * Genera referencia de transacción única para un pago
     */
    public function generateTransactionReference(string $paymentId): ?string
    {
        $payment = $this->model->find($paymentId);

        if (!$payment) {
            return null;
        }

        // Generar referencia única
        $transactionReference = 'PRP-' . strtoupper(uniqid());

        // Verificar que sea única
        while ($this->model->where('transaction_reference', $transactionReference)->exists()) {
            $transactionReference = 'PRP-' . strtoupper(uniqid());
        }

        $payment->update(['transaction_reference' => $transactionReference]);

        return $transactionReference;
    }

    /**
     * Obtiene pagos por jugador externo
     */
    public function getPaymentsByExternalPlayer(string $externalPlayerId, array $filters = []): Collection
    {
        $query = $this->model->where('external_player_id', $externalPlayerId);

        // Aplicar filtros
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->with(['cardPrize', 'room', 'creatorUser', 'externalPlayer', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Actualiza múltiples pagos en lote
     */
    public function bulkUpdatePayments(array $paymentIds, array $updateData): int
    {
        return $this->model->whereIn('id', $paymentIds)
                          ->update($updateData);
    }

    /**
     * Elimina pagos cancelados antiguos
     */
    public function deleteOldCancelledPayments(int $daysOld = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('payment_status', 'cancelled')
                          ->where('updated_at', '<', $cutoffDate)
                          ->delete();
    }

    /**
     * Obtiene pagos fallidos para reintento
     */
    public function getFailedPaymentsForRetry(int $limit = 50): Collection
    {
        return $this->model->where('payment_status', 'failed')
                          ->whereDate('created_at', '>=', Carbon::now()->subDays(7)) // Solo últimos 7 días
                          ->with(['cardPrize', 'room', 'creatorUser', 'seller', 'user', 'currency'])
                          ->orderBy('created_at', 'asc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Obtiene estadísticas generales de pagos
     */
    public function getGeneralPaymentStats(): array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_payments,
                                SUM(CASE WHEN payment_status = "completed" THEN 1 ELSE 0 END) as completed_payments,
                                SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                                SUM(CASE WHEN payment_status = "failed" THEN 1 ELSE 0 END) as failed_payments,
                                SUM(CASE WHEN payment_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_payments,
                                SUM(payment_amount) as total_amount,
                                AVG(payment_amount) as average_amount,
                                COUNT(DISTINCT room_id) as total_rooms,
                                COUNT(DISTINCT seller_id) as total_sellers
                            ')
                            ->first();

        return [
            'total_payments' => $stats->total_payments ?? 0,
            'completed_payments' => $stats->completed_payments ?? 0,
            'pending_payments' => $stats->pending_payments ?? 0,
            'failed_payments' => $stats->failed_payments ?? 0,
            'cancelled_payments' => $stats->cancelled_payments ?? 0,
            'total_amount' => floatval($stats->total_amount ?? 0),
            'average_amount' => floatval($stats->average_amount ?? 0),
            'total_rooms' => $stats->total_rooms ?? 0,
            'total_sellers' => $stats->total_sellers ?? 0,
        ];
    }
}