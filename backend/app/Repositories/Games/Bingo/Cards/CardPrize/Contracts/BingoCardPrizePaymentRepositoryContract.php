<?php

namespace App\Repositories\Bingo\Card\CardPrize\Contracts;

use App\Models\Bingo\Cards\CardPrize\BingoCardPrizePayment;
use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de pagos de premios de cartones de bingo
 *
 * Define los métodos disponibles para gestionar pagos de premios de cartones,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Games\Bingo\Cards\CardPrize
 * @author Sistema de Gestión de Pagos de Premios de Bingo
 * @version 1.0
 */
interface BingoCardPrizePaymentRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene todos los pagos de un premio específico
     *
     * @param string $prizeId ID del premio
     * @param array $filters Filtros adicionales ['payment_status' => 'completed']
     * @return Collection<BingoCardPrizePayment> Pagos del premio
     *
     * @example
     * $payments = $repository->getPaymentsByPrize('123', ['payment_status' => 'completed']);
     */
    public function getPaymentsByPrize(string $prizeId, array $filters = []): Collection;

    /**
     * Obtiene pagos por sala
     *
     * @param string $roomId ID de la sala
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos de la sala
     *
     * @example
     * $payments = $repository->getPaymentsByRoom('456', ['payment_method' => 'seller_cash']);
     */
    public function getPaymentsByRoom(string $roomId, array $filters = []): Collection;

    /**
     * Obtiene pagos por creador
     *
     * @param string $creatorUserId ID del usuario creador
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos del creador
     *
     * @example
     * $payments = $repository->getPaymentsByCreator('789', ['payment_status' => 'pending']);
     */
    public function getPaymentsByCreator(string $creatorUserId, array $filters = []): Collection;

    /**
     * Obtiene pagos por estado
     *
     * @param string $status Estado del pago ('pending', 'completed', 'failed', 'cancelled')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos con el estado especificado
     *
     * @example
     * $pendingPayments = $repository->getPaymentsByStatus('pending');
     */
    public function getPaymentsByStatus(string $status, array $filters = []): Collection;

    /**
     * Obtiene pagos por método de pago
     *
     * @param string $paymentMethod Método de pago ('seller_cash', 'credit_card', 'system_payment')
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos con el método especificado
     *
     * @example
     * $cashPayments = $repository->getPaymentsByMethod('seller_cash');
     */
    public function getPaymentsByMethod(string $paymentMethod, array $filters = []): Collection;

    /**
     * Obtiene pagos por vendedor
     *
     * @param string $sellerId ID del vendedor
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos del vendedor
     *
     * @example
     * $sellerPayments = $repository->getPaymentsBySeller('seller123');
     */
    public function getPaymentsBySeller(string $sellerId, array $filters = []): Collection;

    /**
     * Obtiene pagos por usuario receptor
     *
     * @param string $userId ID del usuario que recibe el pago
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos del usuario
     *
     * @example
     * $userPayments = $repository->getPaymentsByUser('user456');
     */
    public function getPaymentsByUser(string $userId, array $filters = []): Collection;

    /**
     * Busca un pago por referencia de transacción
     *
     * @param string $transactionReference Referencia de transacción
     * @return BingoCardPrizePayment|null Pago encontrado o null
     *
     * @example
     * $payment = $repository->findByTransactionReference('PRP-ABC123');
     */
    public function findByTransactionReference(string $transactionReference): ?BingoCardPrizePayment;

    /**
     * Obtiene pagos completados
     *
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos completados
     *
     * @example
     * $completedPayments = $repository->getCompletedPayments(['room_id' => '123']);
     */
    public function getCompletedPayments(array $filters = []): Collection;

    /**
     * Obtiene pagos pendientes
     *
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos pendientes
     *
     * @example
     * $pendingPayments = $repository->getPendingPayments(['seller_id' => '456']);
     */
    public function getPendingPayments(array $filters = []): Collection;

    /**
     * Actualiza el estado de un pago
     *
     * @param string $paymentId ID del pago
     * @param string $newStatus Nuevo estado
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updatePaymentStatus('123', 'completed');
     */
    public function updatePaymentStatus(string $paymentId, string $newStatus): bool;

    /**
     * Marca un pago como completado
     *
     * @param string $paymentId ID del pago
     * @return bool True si se marcó correctamente
     *
     * @example
     * $completed = $repository->markPaymentAsCompleted('123');
     */
    public function markPaymentAsCompleted(string $paymentId): bool;

    /**
     * Marca un pago como fallido
     *
     * @param string $paymentId ID del pago
     * @return bool True si se marcó correctamente
     *
     * @example
     * $failed = $repository->markPaymentAsFailed('123');
     */
    public function markPaymentAsFailed(string $paymentId): bool;

    /**
     * Obtiene el total de pagos completados para un premio
     *
     * @param string $prizeId ID del premio
     * @return float Total de pagos completados
     *
     * @example
     * $total = $repository->getTotalCompletedPaymentsForPrize('prize123');
     */
    public function getTotalCompletedPaymentsForPrize(string $prizeId): float;

    /**
     * Obtiene estadísticas de pagos por sala
     *
     * @param string $roomId ID de la sala
     * @return array Estadísticas de pagos de la sala
     *
     * @example
     * $stats = $repository->getPaymentStatsByRoom('123');
     * // ['total_payments' => 50, 'completed_payments' => 40, 'total_amount' => 5000.00]
     */
    public function getPaymentStatsByRoom(string $roomId): array;

    /**
     * Obtiene estadísticas de pagos por vendedor
     *
     * @param string $sellerId ID del vendedor
     * @return array Estadísticas de pagos del vendedor
     *
     * @example
     * $stats = $repository->getPaymentStatsBySeller('seller456');
     */
    public function getPaymentStatsBySeller(string $sellerId): array;

    /**
     * Cuenta pagos por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countPaymentsByStatus();
     * // ['pending' => 20, 'completed' => 150, 'failed' => 5, 'cancelled' => 3]
     */
    public function countPaymentsByStatus(array $filters = []): array;

    /**
     * Cuenta pagos por método
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por método
     *
     * @example
     * $counts = $repository->countPaymentsByMethod();
     * // ['seller_cash' => 80, 'credit_card' => 60, 'system_payment' => 40]
     */
    public function countPaymentsByMethod(array $filters = []): array;

    /**
     * Obtiene pagos recientes
     *
     * @param int $limit Límite de resultados
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos más recientes
     *
     * @example
     * $recentPayments = $repository->getRecentPayments(20);
     */
    public function getRecentPayments(int $limit = 20, array $filters = []): Collection;

    /**
     * Obtiene pagos por rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos en el rango
     *
     * @example
     * $payments = $repository->getPaymentsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getPaymentsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Obtiene pagos por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos en la moneda especificada
     *
     * @example
     * $usdPayments = $repository->getPaymentsByCurrency('usd_id');
     */
    public function getPaymentsByCurrency(string $currencyId, array $filters = []): Collection;

    /**
     * Obtiene el valor total de pagos
     *
     * @param array $filters Filtros adicionales
     * @return float Valor total de pagos
     *
     * @example
     * $totalValue = $repository->getTotalPaymentsValue(['payment_status' => 'completed']);
     */
    public function getTotalPaymentsValue(array $filters = []): float;

    /**
     * Obtiene pagos procesados
     *
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos procesados
     *
     * @example
     * $processedPayments = $repository->getProcessedPayments(['room_id' => '123']);
     */
    public function getProcessedPayments(array $filters = []): Collection;

    /**
     * Obtiene pagos sin procesar
     *
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos sin procesar
     *
     * @example
     * $unprocessedPayments = $repository->getUnprocessedPayments();
     */
    public function getUnprocessedPayments(array $filters = []): Collection;

    /**
     * Genera referencia de transacción única para un pago
     *
     * @param string $paymentId ID del pago
     * @return string|null Referencia generada o null si falló
     *
     * @example
     * $reference = $repository->generateTransactionReference('123');
     */
    public function generateTransactionReference(string $paymentId): ?string;

    /**
     * Obtiene pagos por jugador externo
     *
     * @param string $externalPlayerId ID del jugador externo
     * @param array $filters Filtros adicionales
     * @return Collection<BingoCardPrizePayment> Pagos del jugador externo
     *
     * @example
     * $playerPayments = $repository->getPaymentsByExternalPlayer('player789');
     */
    public function getPaymentsByExternalPlayer(string $externalPlayerId, array $filters = []): Collection;

    /**
     * Actualiza múltiples pagos en lote
     *
     * @param array $paymentIds IDs de los pagos
     * @param array $updateData Datos a actualizar
     * @return int Número de pagos actualizados
     *
     * @example
     * $updated = $repository->bulkUpdatePayments(['1', '2'], ['payment_status' => 'cancelled']);
     */
    public function bulkUpdatePayments(array $paymentIds, array $updateData): int;

    /**
     * Elimina pagos cancelados antiguos
     *
     * @param int $daysOld Días de antigüedad
     * @return int Número de pagos eliminados
     *
     * @example
     * $deleted = $repository->deleteOldCancelledPayments(90);
     */
    public function deleteOldCancelledPayments(int $daysOld = 90): int;

    /**
     * Obtiene pagos fallidos para reintento
     *
     * @param int $limit Límite de resultados
     * @return Collection<BingoCardPrizePayment> Pagos fallidos
     *
     * @example
     * $failedPayments = $repository->getFailedPaymentsForRetry(50);
     */
    public function getFailedPaymentsForRetry(int $limit = 50): Collection;

    /**
     * Obtiene estadísticas generales de pagos
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralPaymentStats();
     */
    public function getGeneralPaymentStats(): array;
}