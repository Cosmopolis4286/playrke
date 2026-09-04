<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Repositories;

use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de wallets
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * las wallets de usuarios, incluyendo consultas complejas y estadísticas.
 *
 * @author Sistema de Gestión de Wallets
 *
 * @version 1.0
 */
class WalletRepository extends BaseRepository implements WalletRepositoryInterface
{
    public function __construct(Wallet $model)
    {
        $this->model = $model;
    }

    /**
     * Cambia el estado de múltiples wallets
     */
    public function changeBulkWalletStatus(array $walletIds, string $newStatus) : int
    {
        return $this->model->whereIn('id', $walletIds)
            ->update(['status' => $newStatus]);
    }

    /**
     * Cambia el estado de una wallet
     */
    public function changeWalletStatus(string $walletId, string $newStatus) : bool
    {
        return $this->model->where('id', $walletId)
            ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Consume hasta $amount de la wallet priorizando bonus_balance sobre
     * balance. El lockForUpdate() sobre la fila impide que otra transacción
     * concurrente lea o escriba esta wallet antes del commit, cerrando la
     * ventana entre "leo cuánto bono hay" y "lo resto" (mismo patrón que
     * findOwnedWallet() en WalletService).
     *
     * @return array{bonus_spent: float, balance_spent: float}
     *
     * @throws WalletDomainException si bonus_balance + balance < $amount
     */
    public function consumeBonusFirst(string $walletId, float $amount) : array
    {
        $wallet = $this->model->where('id', $walletId)->lockForUpdate()->firstOrFail();

        $bonusAvailable = (float) $wallet->bonus_balance;
        $balanceAvailable = (float) $wallet->balance;

        if (($bonusAvailable + $balanceAvailable) < $amount) {
            throw WalletDomainException::insufficientCombinedBalance();
        }

        $bonusSpent = min($bonusAvailable, $amount);
        $balanceSpent = round($amount - $bonusSpent, 2);

        if ($bonusSpent > 0) {
            $this->model->where('id', $walletId)->decrement('bonus_balance', $bonusSpent);
        }
        if ($balanceSpent > 0) {
            $this->model->where('id', $walletId)->decrement('balance', $balanceSpent);
        }

        return ['bonus_spent' => $bonusSpent, 'balance_spent' => $balanceSpent];
    }

    /**
     * Cuenta wallets por estado
     */
    public function countWalletsByStatus(array $filters = []) : array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        $counts = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['ACTIVE', 'INACTIVE', 'BLOCKED'];
        foreach ($validStatuses as $status) {
            if (! isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Decrementa el balance de una wallet
     */
    public function decrementBalance(string $walletId, float $amount) : bool
    {
        return $this->model->where('id', $walletId)
            ->decrement('balance', $amount) > 0;
    }

    /**
     * Decrementa el saldo bono (no retirable) de una wallet
     */
    public function decrementBonusBalance(string $walletId, float $amount) : bool
    {
        return $this->model->where('id', $walletId)
            ->decrement('bonus_balance', $amount) > 0;
    }

    /**
     * Elimina todas las wallets asociadas a un usuario
     *
     * @return int Cantidad de registros eliminados
     */
    public function deleteByUserId(string $userId) : int
    {
        return $this->model
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Busca una wallet específica por usuario y moneda
     */
    public function findWalletByUserAndCurrency(string $userId, string $currencyId) : ?Wallet
    {
        return $this->model->where('user_id', $userId)
            ->where('currency_id', $currencyId)
            ->with(['user', 'currency'])
            ->first();
    }

    /**
     * Obtiene wallets activas de un usuario
     */
    public function getActiveWalletsByUser(string $userId, array $columns = ['*']) : Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->select($columns)
            ->with(['currency'])
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Obtiene wallets bloqueadas
     */
    public function getBlockedWallets(array $filters = [], array $columns = ['*']) : Collection
    {
        $query = $this->model->where('status', 'BLOCKED');

        // Aplicar filtros adicionales
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->select($columns)
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene estadísticas generales de wallets
     *
     * Excluye las wallets de cuentas internas del sistema (ej. playrke_platform)
     * para que los totales reflejen solo el dinero de usuarios reales.
     */
    public function getGeneralWalletStats() : array
    {
        $stats = $this->model
            ->whereHas('user', fn ($q) => $q->where('is_system_account', false))
            ->selectRaw('
                                COUNT(*) as total_wallets,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_wallets,
                                SUM(CASE WHEN status = "BLOCKED" THEN 1 ELSE 0 END) as blocked_wallets,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_wallets,
                                SUM(balance) as total_balance,
                                AVG(balance) as avg_balance,
                                MAX(balance) as max_balance,
                                MIN(balance) as min_balance
                            ')
            ->first();

        return [
            'total_wallets' => $stats->total_wallets ?? 0,
            'active_wallets' => $stats->active_wallets ?? 0,
            'blocked_wallets' => $stats->blocked_wallets ?? 0,
            'inactive_wallets' => $stats->inactive_wallets ?? 0,
            'total_balance' => floatval($stats->total_balance ?? 0),
            'average_balance' => floatval($stats->avg_balance ?? 0),
            'max_balance' => floatval($stats->max_balance ?? 0),
            'min_balance' => floatval($stats->min_balance ?? 0),
        ];
    }

    /**
     * Obtiene el balance total de un usuario en todas sus wallets
     */
    public function getTotalBalanceByUser(string $userId, ?string $currencyId = null) : float
    {
        $query = $this->model->where('user_id', $userId)
            ->where('status', 'ACTIVE');

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        return floatval($query->sum('balance'));
    }

    /**
     * Obtiene wallets por moneda
     */
    public function getWalletsByCurrency(string $currencyId, array $filters = []) : Collection
    {
        $query = $this->model->where('currency_id', $currencyId);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Obtiene wallets creadas en un rango de fechas
     */
    public function getWalletsByDateRange(string $startDate, string $endDate, array $filters = []) : Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene wallets por estado
     */
    public function getWalletsByStatus(string $status, array $filters = []) : Collection
    {
        $query = $this->model->where('status', $status);

        // Aplicar filtros adicionales
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Obtiene todas las wallets de un usuario específico
     */
    public function getWalletsByUser(string $userId, array $filters = []) : Collection
    {
        $query = $this->model->where('user_id', $userId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (isset($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Obtiene estadísticas de wallets por usuario
     */
    public function getWalletStatsByUser(string $userId) : array
    {
        $stats = $this->model->where('user_id', $userId)
            ->selectRaw('
                                COUNT(*) as total_wallets,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_wallets,
                                SUM(CASE WHEN status = "BLOCKED" THEN 1 ELSE 0 END) as blocked_wallets,
                                SUM(CASE WHEN status = "ACTIVE" THEN balance ELSE 0 END) as total_active_balance,
                                SUM(balance) as total_balance,
                                AVG(balance) as avg_balance
                            ')
            ->first();

        return [
            'total_wallets' => $stats->total_wallets ?? 0,
            'active_wallets' => $stats->active_wallets ?? 0,
            'blocked_wallets' => $stats->blocked_wallets ?? 0,
            'inactive_wallets' => ($stats->total_wallets ?? 0) - ($stats->active_wallets ?? 0) - ($stats->blocked_wallets ?? 0),
            'total_active_balance' => floatval($stats->total_active_balance ?? 0),
            'total_balance' => floatval($stats->total_balance ?? 0),
            'average_balance' => floatval($stats->avg_balance ?? 0),
        ];
    }

    /**
     * Obtiene wallets con balance menor a un monto específico
     */
    public function getWalletsWithMaxBalance(float $maxBalance, array $filters = []) : Collection
    {
        $query = $this->model->where('balance', '<=', $maxBalance);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('balance', 'asc')
            ->get();
    }

    /**
     * Obtiene wallets con balance mayor a un monto específico
     */
    public function getWalletsWithMinBalance(float $minBalance, array $filters = []) : Collection
    {
        $query = $this->model->where('balance', '>=', $minBalance);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->with(['user', 'currency'])
            ->orderBy('balance', 'desc')
            ->get();
    }

    /**
     * Incrementa el balance de una wallet
     */
    public function incrementBalance(string $walletId, float $amount) : bool
    {
        return $this->model->where('id', $walletId)
            ->increment('balance', $amount) > 0;
    }

    /**
     * Incrementa el saldo bono (no retirable) de una wallet
     */
    public function incrementBonusBalance(string $walletId, float $amount) : bool
    {
        return $this->model->where('id', $walletId)
            ->increment('bonus_balance', $amount) > 0;
    }

    /**
     * Actualiza el balance de una wallet
     */
    public function updateBalance(string $walletId, float $newBalance) : bool
    {
        return $this->model->where('id', $walletId)
            ->update(['balance' => $newBalance]) > 0;
    }

    /**
     * Verifica si un usuario tiene una wallet para una moneda específica
     */
    public function userHasWalletForCurrency(string $userId, string $currencyId) : bool
    {
        return $this->model->where('user_id', $userId)
            ->where('currency_id', $currencyId)
            ->exists();
    }
}
