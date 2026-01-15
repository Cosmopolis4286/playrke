<?php

namespace App\Repositories\Shared\Transactions\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de transacciones.
 *
 * Define las operaciones específicas para gestionar las transacciones
 * del sistema, incluyendo consultas por código, cliente, estado,
 * tipo de transacción y montos.
 */
interface TransactionRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene el valor de una transacción por código.
     *
     * @param string $code Código de la transacción
     * @param string $field Campo a obtener
     * @return mixed Valor del campo solicitado
     */
    public function getValueByCode(string $code, string $field): mixed;

    /**
     * Verifica si existe una transacción con el código y cliente dados.
     *
     * @param string $code Código de la transacción
     * @param string $customer ID del cliente
     * @return bool True si existe la transacción
     */
    public function existsCodeForCustomer(string $code, string $customer): bool;

    /**
     * Obtiene transacciones por cliente.
     *
     * @param string $customerId ID del cliente
     * @return Collection<Transaction> Transacciones del cliente
     */
    public function getByCustomer(string $customerId): Collection;

    /**
     * Obtiene transacciones por tipo.
     *
     * @param string $type Tipo de transacción
     * @return Collection<Transaction> Transacciones del tipo
     */
    public function getByType(string $type): Collection;

    /**
     * Obtiene transacciones por juego.
     *
     * @param string $gameId ID del juego
     * @return Collection<Transaction> Transacciones del juego
     */
    public function getByGame(string $gameId): Collection;

    /**
     * Obtiene transacciones por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @return Collection<Transaction> Transacciones de la moneda
     */
    public function getByCurrency(string $currencyId): Collection;

    /**
     * Obtiene transacciones en un rango de montos.
     *
     * @param float $minAmount Monto mínimo
     * @param float $maxAmount Monto máximo
     * @return Collection<Transaction> Transacciones en el rango
     */
    public function getByAmountRange(float $minAmount, float $maxAmount): Collection;

    /**
     * Obtiene transacciones en un rango de fechas.
     *
     * @param string $startDate Fecha inicio
     * @param string $endDate Fecha fin
     * @return Collection<Transaction> Transacciones en el rango
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Obtiene estadísticas de transacciones.
     *
     * @return array Estadísticas generales
     */
    public function getTransactionStats(): array;

    /**
     * Obtiene estadísticas por cliente.
     *
     * @param string $customerId ID del cliente
     * @return array Estadísticas del cliente
     */
    public function getCustomerStats(string $customerId): array;

    /**
     * Crea una nueva transacción con validación.
     *
     * @param array $data Datos de la transacción
     * @return mixed Transacción creada
     */
    public function createTransaction(array $data): mixed;

    /**
     * Actualiza el estado de una transacción.
     *
     * @param string $transactionId ID de la transacción
     * @param string $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $transactionId, string $status): bool;

    /**
     * Valida los datos de una transacción.
     *
     * @param array $data Datos a validar
     * @return array Array de errores de validación
     */
    public function validateTransactionData(array $data): array;
}
