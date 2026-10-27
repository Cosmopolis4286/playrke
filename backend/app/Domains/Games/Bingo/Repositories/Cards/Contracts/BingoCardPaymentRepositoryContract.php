<?php

namespace App\Domains\Games\Bingo\Repositories\Cards\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de pagos de cartones de bingo
 *
 * Define los métodos disponibles para gestionar pagos de cartones de bingo,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @author Sistema de Gestión de Bingo
 *
 * @version 1.0
 */
interface BingoCardPaymentRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Calcula el total de pagos completados por método
     *
     * @param string $paymentMethod Método de pago
     * @param array  $filters       Filtros adicionales
     *
     * @return float Total de pagos completados
     *
     * @example
     * $total = $repository->calculateTotalByMethod('payment_system', ['room_id' => '123']);
     */
    public function calculateTotalByMethod(string $paymentMethod, array $filters = []) : float;

    /**
     * Cambia el estado de múltiples pagos
     *
     * @param array  $paymentIds     IDs de los pagos
     * @param string $newStatus      Nuevo estado
     * @param array  $additionalData Datos adicionales para la actualización
     *
     * @return int Número de pagos actualizados
     *
     * @example
     * $updated = $repository->changeBulkStatus(['1', '2'], 'completed');
     */
    public function changeBulkStatus(array $paymentIds, string $newStatus, array $additionalData = []) : int;

    /**
     * Cuenta los pagos por estado en una sala
     *
     * @param string $roomId ID de la sala
     *
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countPaymentsByStatus('123');
     * // ['pending' => 5, 'completed' => 50, 'failed' => 2, 'cancelled' => 1]
     */
    public function countPaymentsByStatus(string $roomId) : array;

    /**
     * Busca pagos por comprobante de pago
     *
     * @param string $paymentProof Comprobante del pago
     * @param array  $columns      Columnas a seleccionar
     *
     * @return Collection<BingoCardPayment> Pagos con el comprobante
     *
     * @example
     * $payments = $repository->findByPaymentProof('RECEIPT-789');
     */
    public function findByPaymentProof(string $paymentProof, array $columns = ['*']) : Collection;

    /**
     * Busca pagos por referencia de transacción
     *
     * @param string $transactionReference Referencia de la transacción
     *
     * @return BingoCardPayment|null Pago encontrado o null
     *
     * @example
     * $payment = $repository->findByTransactionReference('TXN-123456');
     */
    public function findByTransactionReference(string $transactionReference) : ?BingoCardPayment;

    /**
     * Obtiene pagos pendientes que han expirado
     *
     * @param int   $hoursLimit Número de horas límite para considerar expirado
     * @param array $columns    Columnas a seleccionar
     *
     * @return Collection<BingoCardPayment> Pagos pendientes expirados
     *
     * @example
     * $expired = $repository->getExpiredPendingPayments(24);
     */
    public function getExpiredPendingPayments(int $hoursLimit = 24, array $columns = ['*']) : Collection;

    /**
     * Obtiene pagos por lote de cartones
     *
     * @param string      $cardLotId ID del lote de cartones
     * @param string|null $status    Estado específico del pago
     *
     * @return Collection<BingoCardPayment> Pagos del lote
     *
     * @example
     * $payments = $repository->getPaymentsByCardLot('789', 'completed');
     */
    public function getPaymentsByCardLot(string $cardLotId, ?string $status = null) : Collection;

    /**
     * Obtiene pagos por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array  $filters    Filtros adicionales
     *
     * @return Collection<BingoCardPayment> Pagos con la moneda especificada
     *
     * @example
     * $payments = $repository->getPaymentsByCurrency('USD');
     */
    public function getPaymentsByCurrency(string $currencyId, array $filters = []) : Collection;

    /**
     * Obtiene pagos en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate   Fecha de fin (Y-m-d H:i:s)
     * @param array  $filters   Filtros adicionales
     *
     * @return Collection<BingoCardPayment> Pagos en el rango
     *
     * @example
     * $payments = $repository->getPaymentsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getPaymentsByDateRange(string $startDate, string $endDate, array $filters = []) : Collection;

    /**
     * Obtiene pagos por jugador externo
     *
     * @param string $externalPlayerId ID del jugador externo
     * @param array  $filters          Filtros adicionales
     *
     * @return Collection<BingoCardPayment> Pagos del jugador externo
     *
     * @example
     * $payments = $repository->getPaymentsByExternalPlayer('654');
     */
    public function getPaymentsByExternalPlayer(string $externalPlayerId, array $filters = []) : Collection;

    /**
     * Obtiene pagos por método de pago
     *
     * @param string $paymentMethod Método de pago ('payment_system', 'seller_cash')
     * @param array  $filters       Filtros adicionales ['room_id' => '...', 'status' => '...']
     *
     * @return Collection<BingoCardPayment> Pagos del método especificado
     *
     * @example
     * $payments = $repository->getPaymentsByMethod('payment_system', ['status' => 'completed']);
     */
    public function getPaymentsByMethod(string $paymentMethod, array $filters = []) : Collection;

    /**
     * Obtiene todos los pagos de una sala específica
     *
     * @param string $roomId  ID de la sala de bingo
     * @param array  $filters Filtros adicionales ['status' => 'completed', 'method' => 'payment_system']
     *
     * @return Collection<BingoCardPayment> Pagos de la sala
     *
     * @example
     * $payments = $repository->getPaymentsByRoom('123', ['status' => 'completed']);
     */
    public function getPaymentsByRoom(string $roomId, array $filters = []) : Collection;

    /**
     * Obtiene pagos por vendedor
     *
     * @param string $sellerId ID del vendedor
     * @param array  $filters  Filtros adicionales ['room_id' => '...', 'status' => '...']
     *
     * @return Collection<BingoCardPayment> Pagos del vendedor
     *
     * @example
     * $payments = $repository->getPaymentsBySeller('321', ['status' => 'completed']);
     */
    public function getPaymentsBySeller(string $sellerId, array $filters = []) : Collection;

    /**
     * Obtiene pagos por estado
     *
     * @param string $status  Estado del pago ('pending', 'completed', 'failed', 'cancelled')
     * @param array  $filters Filtros adicionales ['room_id' => '...', 'method' => '...']
     *
     * @return Collection<BingoCardPayment> Pagos con el estado especificado
     *
     * @example
     * $payments = $repository->getPaymentsByStatus('pending');
     */
    public function getPaymentsByStatus(string $status, array $filters = []) : Collection;

    /**
     * Obtiene pagos por usuario
     *
     * @param string $userId  ID del usuario
     * @param array  $filters Filtros adicionales ['room_id' => '...', 'status' => '...']
     *
     * @return Collection<BingoCardPayment> Pagos del usuario
     *
     * @example
     * $payments = $repository->getPaymentsByUser('456', ['status' => 'completed']);
     */
    public function getPaymentsByUser(string $userId, array $filters = []) : Collection;

    /**
     * Obtiene estadísticas de pagos por sala
     *
     * @param string $roomId ID de la sala
     *
     * @return array Estadísticas agrupadas por estado y método
     *
     * @example
     * $stats = $repository->getPaymentStatsByRoom('123');
     */
    public function getPaymentStatsByRoom(string $roomId) : array;

    /**
     * Obtiene estadísticas de pagos por vendedor
     *
     * @param string $sellerId  ID del vendedor
     * @param string $startDate Fecha de inicio
     * @param string $endDate   Fecha de fin
     *
     * @return array Estadísticas del vendedor
     *
     * @example
     * $stats = $repository->getPaymentStatsBySeller('321', '2024-01-01', '2024-01-31');
     */
    public function getPaymentStatsBySeller(string $sellerId, string $startDate, string $endDate) : array;

    /**
     * Procesa un pago actualizando su estado y fecha de procesamiento
     *
     * @param string $paymentId   ID del pago
     * @param string $newStatus   Nuevo estado
     * @param array  $processData Datos del procesamiento
     *
     * @return bool True si se procesó correctamente
     *
     * @example
     * $processed = $repository->processPayment('123', 'completed', ['processed_by' => 'admin']);
     */
    public function processPayment(string $paymentId, string $newStatus, array $processData = []) : bool;
}
