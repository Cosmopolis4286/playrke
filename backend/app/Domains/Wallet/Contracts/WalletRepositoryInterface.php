<?php

namespace App\Domains\Wallet\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de wallets
 *
 * Define los métodos disponibles para gestionar wallets de usuarios,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Contracts
 * @author Sistema de Gestión de Wallets
 * @version 1.0
 */
interface WalletRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtiene todas las wallets de un usuario específico
     *
     * @param string $userId ID del usuario
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE', 'currency_id' => '...']
     * @return Collection<Wallet> Wallets del usuario
     *
     * @example
     * $wallets = $repository->getWalletsByUser('123', ['status' => 'ACTIVE']);
     */
    public function getWalletsByUser(string $userId, array $filters = []): Collection;

    /**
     * Obtiene wallets por moneda
     *
     * @param string $currencyId ID de la moneda
     * @param array $filters Filtros adicionales ['status' => 'ACTIVE', 'user_id' => '...']
     * @return Collection<Wallet> Wallets con la moneda especificada
     *
     * @example
     * $wallets = $repository->getWalletsByCurrency('USD', ['status' => 'ACTIVE']);
     */
    public function getWalletsByCurrency(string $currencyId, array $filters = []): Collection;

    /**
     * Obtiene wallets por estado
     *
     * @param string $status Estado de la wallet ('ACTIVE', 'INACTIVE', 'BLOCKED')
     * @param array $filters Filtros adicionales ['user_id' => '...', 'currency_id' => '...']
     * @return Collection<Wallet> Wallets con el estado especificado
     *
     * @example
     * $wallets = $repository->getWalletsByStatus('ACTIVE');
     */
    public function getWalletsByStatus(string $status, array $filters = []): Collection;

    /**
     * Busca una wallet específica por usuario y moneda
     *
     * @param string $userId ID del usuario
     * @param string $currencyId ID de la moneda
     * @return Wallet|null Wallet encontrada o null
     *
     * @example
     * $wallet = $repository->findWalletByUserAndCurrency('123', 'USD');
     */
    public function findWalletByUserAndCurrency(string $userId, string $currencyId): ?Wallet;

    /**
     * Obtiene wallets activas de un usuario
     *
     * @param string $userId ID del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<Wallet> Wallets activas del usuario
     *
     * @example
     * $activeWallets = $repository->getActiveWalletsByUser('123');
     */
    public function getActiveWalletsByUser(string $userId, array $columns = ['*']): Collection;

    /**
     * Obtiene wallets bloqueadas
     *
     * @param array $filters Filtros adicionales ['user_id' => '...']
     * @param array $columns Columnas a seleccionar
     * @return Collection<Wallet> Wallets bloqueadas
     *
     * @example
     * $blockedWallets = $repository->getBlockedWallets(['user_id' => '123']);
     */
    public function getBlockedWallets(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Elimina todas las wallets de un usuario
     * @param string $userId ID del usuario
     * @return int Número de wallets eliminadas
     */
    public function deleteByUserId(string $userId): int;

    /**
     * Actualiza el balance de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param float $newBalance Nuevo balance
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateBalance('456', 150.75);
     */
    public function updateBalance(string $walletId, float $newBalance): bool;

    /**
     * Incrementa el balance de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param float $amount Cantidad a incrementar
     * @return bool True si se incrementó correctamente
     *
     * @example
     * $incremented = $repository->incrementBalance('456', 50.00);
     */
    public function incrementBalance(string $walletId, float $amount): bool;

    /**
     * Decrementa el balance de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param float $amount Cantidad a decrementar
     * @return bool True si se decrementó correctamente
     *
     * @example
     * $decremented = $repository->decrementBalance('456', 25.00);
     */
    public function decrementBalance(string $walletId, float $amount): bool;

    /**
     * Incrementa el saldo bono (no retirable) de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param float $amount Cantidad a incrementar
     * @return bool True si se incrementó correctamente
     */
    public function incrementBonusBalance(string $walletId, float $amount): bool;

    /**
     * Decrementa el saldo bono (no retirable) de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param float $amount Cantidad a decrementar
     * @return bool True si se decrementó correctamente
     */
    public function decrementBonusBalance(string $walletId, float $amount): bool;

    /**
     * Consume hasta $amount de la wallet priorizando bonus_balance sobre
     * balance. Debe llamarse siempre dentro de una transacción DB — toma su
     * propio lockForUpdate() sobre la fila para leer el split de forma segura.
     *
     * @param string $walletId ID de la wallet
     * @param float $amount Monto total a consumir (bono + real)
     * @return array{bonus_spent: float, balance_spent: float}
     * @throws \DomainException si bonus_balance + balance < $amount
     */
    public function consumeBonusFirst(string $walletId, float $amount): array;

    /**
     * Cambia el estado de una wallet
     *
     * @param string $walletId ID de la wallet
     * @param string $newStatus Nuevo estado ('ACTIVE', 'INACTIVE', 'BLOCKED')
     * @return bool True si se cambió correctamente
     *
     * @example
     * $changed = $repository->changeWalletStatus('456', 'BLOCKED');
     */
    public function changeWalletStatus(string $walletId, string $newStatus): bool;

    /**
     * Cambia el estado de múltiples wallets
     *
     * @param array $walletIds IDs de las wallets
     * @param string $newStatus Nuevo estado
     * @return int Número de wallets actualizadas
     *
     * @example
     * $updated = $repository->changeBulkWalletStatus(['1', '2'], 'INACTIVE');
     */
    public function changeBulkWalletStatus(array $walletIds, string $newStatus): int;

    /**
     * Obtiene el balance total de un usuario en todas sus wallets
     *
     * @param string $userId ID del usuario
     * @param string|null $currencyId ID de moneda específica (opcional)
     * @return float Balance total
     *
     * @example
     * $totalBalance = $repository->getTotalBalanceByUser('123');
     */
    public function getTotalBalanceByUser(string $userId, ?string $currencyId = null): float;

    /**
     * Obtiene estadísticas de wallets por usuario
     *
     * @param string $userId ID del usuario
     * @return array Estadísticas del usuario
     *
     * @example
     * $stats = $repository->getWalletStatsByUser('123');
     * // ['total_wallets' => 3, 'active_wallets' => 2, 'total_balance' => 1500.00]
     */
    public function getWalletStatsByUser(string $userId): array;

    /**
     * Obtiene estadísticas generales de wallets
     *
     * @return array Estadísticas generales
     *
     * @example
     * $generalStats = $repository->getGeneralWalletStats();
     * // ['total_wallets' => 1000, 'active_wallets' => 800, 'total_balance' => 50000.00]
     */
    public function getGeneralWalletStats(): array;

    /**
     * Obtiene wallets con balance mayor a un monto específico
     *
     * @param float $minBalance Balance mínimo
     * @param array $filters Filtros adicionales
     * @return Collection<Wallet> Wallets con balance mayor al especificado
     *
     * @example
     * $richWallets = $repository->getWalletsWithMinBalance(1000.00);
     */
    public function getWalletsWithMinBalance(float $minBalance, array $filters = []): Collection;

    /**
     * Obtiene wallets con balance menor a un monto específico
     *
     * @param float $maxBalance Balance máximo
     * @param array $filters Filtros adicionales
     * @return Collection<Wallet> Wallets con balance menor al especificado
     *
     * @example
     * $lowBalanceWallets = $repository->getWalletsWithMaxBalance(10.00);
     */
    public function getWalletsWithMaxBalance(float $maxBalance, array $filters = []): Collection;

    /**
     * Cuenta wallets por estado
     *
     * @param array $filters Filtros adicionales
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countWalletsByStatus();
     * // ['ACTIVE' => 800, 'INACTIVE' => 150, 'BLOCKED' => 50]
     */
    public function countWalletsByStatus(array $filters = []): array;

    /**
     * Obtiene wallets creadas en un rango de fechas
     *
     * @param string $startDate Fecha de inicio (Y-m-d H:i:s)
     * @param string $endDate Fecha de fin (Y-m-d H:i:s)
     * @param array $filters Filtros adicionales
     * @return Collection<Wallet> Wallets creadas en el rango
     *
     * @example
     * $wallets = $repository->getWalletsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getWalletsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Verifica si un usuario tiene una wallet para una moneda específica
     *
     * @param string $userId ID del usuario
     * @param string $currencyId ID de la moneda
     * @return bool True si existe la wallet
     *
     * @example
     * $hasWallet = $repository->userHasWalletForCurrency('123', 'USD');
     */
    public function userHasWalletForCurrency(string $userId, string $currencyId): bool;
}
