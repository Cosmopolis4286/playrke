<?php

declare(strict_types=1);

namespace App\Repositories\Games\Bingo\Cards;

use App\Models\Games\Bingo\Cards\BingoCardPayment;
use App\Repositories\Contracts\Games\Bingo\Cards\BingoCardPaymentRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de pagos de cartones de bingo
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los pagos de cartones de bingo, incluyendo consultas complejas y estadísticas.
 *
 * @package App\Repositories\Games\Bingo\Cards
 * @author Sistema de Gestión de Bingo
 * @version 1.0
 */
class BingoCardPaymentRepository extends BaseRepository implements BingoCardPaymentRepositoryContract
{
    /**
     * Crear instancia del modelo BingoCardPayment
     *
     * @return Model
     */
    protected function makeModel(): Model
    {
        return new BingoCardPayment();
    }

    /**
     * Obtiene todos los pagos de una sala específica
     */
    public function getPaymentsByRoom(string $roomId, array $filters = []): Collection
    {
        $query = $this->model->where('room_id', $roomId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with(['room', 'seller', 'user', 'externalPlayer', 'currency', 'cardLot'])
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
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['room', 'seller', 'user', 'currency'])
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

        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->with(['room', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por lote de cartones
     */
    public function getPaymentsByCardLot(string $cardLotId, ?string $status = null): Collection
    {
        $query = $this->model->where('card_lot_id', $cardLotId);

        if ($status) {
            $query->where('payment_status', $status);
        }

        return $query->with(['room', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por vendedor
     */
    public function getPaymentsBySeller(string $sellerId, array $filters = []): Collection
    {
        $query = $this->model->where('seller_id', $sellerId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['room', 'user', 'currency', 'cardLot'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por usuario
     */
    public function getPaymentsByUser(string $userId, array $filters = []): Collection
    {
        $query = $this->model->where('user_id', $userId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        return $query->with(['room', 'seller', 'currency', 'cardLot'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos por jugador externo
     */
    public function getPaymentsByExternalPlayer(string $externalPlayerId, array $filters = []): Collection
    {
        $query = $this->model->where('external_player_id', $externalPlayerId);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        return $query->with(['room', 'seller', 'externalPlayer', 'currency'])
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
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        return $query->with(['room', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene pagos en un rango de fechas
     */
    public function getPaymentsByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }

        if (isset($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        return $query->with(['room', 'seller', 'user', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Busca pagos por referencia de transacción
     */
    public function findByTransactionReference(string $transactionReference): ?BingoCardPayment
    {
        return $this->model->where('transaction_reference', $transactionReference)
                          ->with(['room', 'seller', 'user', 'currency'])
                          ->first();
    }

    /**
     * Busca pagos por comprobante de pago
     */
    public function findByPaymentProof(string $paymentProof, array $columns = ['*']): Collection
    {
        return $this->model->where('payment_proof', $paymentProof)
                          ->select($columns)
                          ->with(['room', 'seller', 'user'])
                          ->get();
    }

    /**
     * Cambia el estado de múltiples pagos
     */
    public function changeBulkStatus(array $paymentIds, string $newStatus, array $additionalData = []): int
    {
        $updateData = array_merge(['payment_status' => $newStatus], $additionalData);

        // Si el estado es completed, marcar fecha de procesamiento
        if ($newStatus === 'completed' && !isset($updateData['processed_at'])) {
            $updateData['processed_at'] = now();
        }

        return $this->model->whereIn('id', $paymentIds)
                          ->update($updateData);
    }

    /**
     * Procesa un pago actualizando su estado y fecha de procesamiento
     */
    public function processPayment(string $paymentId, string $newStatus, array $processData = []): bool
    {
        $updateData = [
            'payment_status' => $newStatus,
            'processed_at' => now()
        ];

        // Agregar datos adicionales de procesamiento
        $updateData = array_merge($updateData, $processData);

        return $this->model->where('id', $paymentId)
                          ->update($updateData) > 0;
    }

    /**
     * Obtiene estadísticas de pagos por sala
     */
    public function getPaymentStatsByRoom(string $roomId): array
    {
        $stats = $this->model->where('room_id', $roomId)
                            ->selectRaw('
                                payment_status,
                                payment_method,
                                COUNT(*) as total_count,
                                SUM(payment_amount) as total_amount,
                                AVG(payment_amount) as avg_amount
                            ')
                            ->groupBy('payment_status', 'payment_method')
                            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->payment_status][$stat->payment_method] = [
                'count' => $stat->total_count,
                'total' => floatval($stat->total_amount),
                'average' => floatval($stat->avg_amount),
            ];
        }

        return $result;
    }

    /**
     * Obtiene estadísticas de pagos por vendedor
     */
    public function getPaymentStatsBySeller(string $sellerId, string $startDate, string $endDate): array
    {
        $stats = $this->model->where('seller_id', $sellerId)
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->selectRaw('
                                payment_status,
                                COUNT(*) as total_count,
                                SUM(payment_amount) as total_amount,
                                AVG(payment_amount) as avg_amount
                            ')
                            ->groupBy('payment_status')
                            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->payment_status] = [
                'count' => $stat->total_count,
                'total' => floatval($stat->total_amount),
                'average' => floatval($stat->avg_amount),
            ];
        }

        return $result;
    }

    /**
     * Calcula el total de pagos completados por método
     */
    public function calculateTotalByMethod(string $paymentMethod, array $filters = []): float
    {
        $query = $this->model->where('payment_method', $paymentMethod)
                            ->where('payment_status', 'completed');

        // Aplicar filtros adicionales
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return floatval($query->sum('payment_amount'));
    }

    /**
     * Obtiene pagos pendientes que han expirado
     */
    public function getExpiredPendingPayments(int $hoursLimit = 24, array $columns = ['*']): Collection
    {
        $expiredDate = now()->subHours($hoursLimit);

        return $this->model->where('payment_status', 'pending')
                          ->where('created_at', '<', $expiredDate)
                          ->select($columns)
                          ->with(['room', 'seller', 'user'])
                          ->orderBy('created_at', 'asc')
                          ->get();
    }

    /**
     * Cuenta los pagos por estado en una sala
     */
    public function countPaymentsByStatus(string $roomId): array
    {
        $counts = $this->model->where('room_id', $roomId)
                             ->select('payment_status', DB::raw('count(*) as total'))
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
}