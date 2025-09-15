<?php

namespace App\Repositories\Games\Bingo\Cards\CardPrize\Contracts;

use App\Repositories\BaseRepositoryContract;
use App\Models\Games\Bingo\Cards\CardPrize\BingoCardPrizePayment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de pagos de premios de cartones de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los pagos de premios de cartones de bingo de forma eficiente.
 *
 * @package App\Repositories\Games\Bingo\Cards\CardPrize\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoCardPrizePaymentRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener pagos completados
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getCompletedPayments(): Collection;

    /**
     * Obtener pagos pendientes
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPendingPayments(): Collection;

    /**
     * Obtener pagos fallidos
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getFailedPayments(): Collection;

    /**
     * Obtener pagos cancelados
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getCancelledPayments(): Collection;

    /**
     * Obtener pagos por método de pago
     *
     * @param string $paymentMethod
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByMethod(string $paymentMethod): Collection;

    /**
     * Obtener pagos por estado
     *
     * @param string $status
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByStatus(string $status): Collection;

    /**
     * Obtener pagos procesados
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getProcessedPayments(): Collection;

    /**
     * Obtener pagos sin procesar
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getUnprocessedPayments(): Collection;

    /**
     * Obtener pagos por vendedor
     *
     * @param string $sellerId
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsBySeller(string $sellerId): Collection;

    /**
     * Obtener pagos por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByRoom(string $roomId): Collection;

    /**
     * Obtener pagos por premio
     *
     * @param string $prizeId
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByPrize(string $prizeId): Collection;

    /**
     * Obtener pagos por usuario receptor
     *
     * @param string $userId
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByUser(string $userId): Collection;

    /**
     * Obtener pagos por jugador externo
     *
     * @param string $externalPlayerId
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getPaymentsByExternalPlayer(string $externalPlayerId): Collection;

    /**
     * Obtener pagos en efectivo del vendedor
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getSellerCashPayments(): Collection;

    /**
     * Obtener pagos con tarjeta de crédito
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getCreditCardPayments(): Collection;

    /**
     * Obtener pagos del sistema
     *
     * @return Collection<int, BingoCardPrizePayment>
     */
    public function getSystemPayments(): Collection;

    /**
     * Marcar pago como completado
     *
     * @param string $paymentId
     * @return bool
     */
    public function markAsCompleted(string $paymentId): bool;

    /**
     * Marcar pago como fallido
     *
     * @param string $paymentId
     * @return bool
     */
    public function markAsFailed(string $paymentId): bool;

    /**
     * Marcar pago como cancelado
     *
     * @param string $paymentId
     * @return bool
     */
    public function markAsCancelled(string $paymentId): bool;

    /**
     * Generar referencia de transacción para un pago
     *
     * @param string $paymentId
     * @return string|null
     */
    public function generateTransactionReference(string $paymentId): ?string;

    /**
     * Obtener estadísticas de pagos por sala
     *
     * @param string $roomId
     * @return array{
     *     total_payments: int,
     *     total_amount: float,
     *     completed_count: int,
     *     pending_count: int,
     *     failed_count: int,
     *     cancelled_count: int
     * }
     */
    public function getPaymentStatsByRoom(string $roomId): array;

    /**
     * Obtener estadísticas de pagos por vendedor
     *
     * @param string $sellerId
     * @return array{
     *     total_payments: int,
     *     total_amount: float,
     *     cash_payments: int,
     *     cash_amount: float
     * }
     */
    public function getPaymentStatsBySeller(string $sellerId): array;

    /**
     * Obtener estadísticas generales de pagos
     *
     * @return array{
     *     total_payments: int,
     *     total_amount: float,
     *     completed_payments: int,
     *     completed_amount: float,
     *     pending_payments: int,
     *     pending_amount: float
     * }
     */
    public function getGeneralPaymentStats(): array;

    /**
     * Buscar pagos por referencia de transacción
     *
     * @param string $reference
     * @return BingoCardPrizePayment|null
     */
    public function findByTransactionReference(string $reference): ?BingoCardPrizePayment;

    /**
     * Verificar si existe un pago para un premio específico
     *
     * @param string $prizeId
     * @return bool
     */
    public function hasPaymentForPrize(string $prizeId): bool;

    /**
     * Obtener el total de pagos completados por moneda
     *
     * @param string $currencyId
     * @return float
     */
    public function getCompletedPaymentsTotalByCurrency(string $currencyId): float;
}