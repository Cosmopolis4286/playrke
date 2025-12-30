<?php

namespace App\Repositories\Payments\Colombia;

use App\Models\Payments\Colombia\PaymentAccount;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Payments\Colombia\Contracts\PaymentAccountRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de métodos de pago de Colombia.
 *
 * Implementa las operaciones específicas para el modelo PaymentAccount,
 * incluyendo validaciones por gateway, gestión de cuentas y estadísticas.
 */
class PaymentAccountRepository extends BaseRepository implements PaymentAccountRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param PaymentAccount $model Instancia del modelo PaymentAccount
     */
    public function __construct(PaymentAccount $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByGateway(string $gateway): Collection
    {
        return $this->model->where('gateway', $gateway)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDocumentNumber(string $documentNumber): Collection
    {
        return $this->model->where('document_number', $documentNumber)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByAccountNumber(string $accountNumber): Collection
    {
        return $this->model->where('account_number', $accountNumber)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(bool $isActive): Collection
    {
        return $this->model->where('is_active', $isActive)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveAccounts(): Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveAccounts(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function findByAllParameters(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber
    ): ?PaymentAccount {
        return $this->model->where([
            'gateway' => $gateway,
            'user_name' => $userName,
            'account_number' => $accountNumber,
            'document_number' => $documentNumber,
        ])->first();
    }

    /**
     * {@inheritDoc}
     */
    public function existsAccount(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber,
        ?string $excludeUserId = null
    ): bool {
        $query = $this->model->where([
            'gateway' => $gateway,
            'user_name' => $userName,
            'account_number' => $accountNumber,
            'document_number' => $documentNumber,
        ]);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createAccount(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber,
        string $userId,
        bool $isActive = true
    ): PaymentAccount {
        return $this->model->create([
            'gateway' => $gateway,
            'user_name' => $userName,
            'account_number' => $accountNumber,
            'document_number' => $documentNumber,
            'user_id' => $userId,
            'is_active' => $isActive
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $accountId, bool $isActive): bool
    {
        return $this->model->where('id', $accountId)
            ->update(['is_active' => $isActive]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateAccount(string $accountId): bool
    {
        return $this->updateStatus($accountId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateAccount(string $accountId): bool
    {
        return $this->updateStatus($accountId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function updateAccountInfo(string $accountId, array $data): bool
    {
        return $this->model->where('id', $accountId)->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAccount(string $accountId): bool
    {
        return $this->model->where('id', $accountId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByUser(string $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $accountIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $accountIds)
            ->update(['is_active' => $isActive]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('is_active', true)->count();
        $inactive = $this->model->where('is_active', false)->count();
        $uniqueUsers = $this->model->distinct('user_id')->count();
        $bancolombiaCount = $this->model->where('gateway', 'BANCOLOMBIA')->count();
        $daviplataCount = $this->model->where('gateway', 'DAVIPLATA')->count();
        $nequiCount = $this->model->where('gateway', 'NEQUI')->count();

        return [
            'total_accounts' => $total,
            'active_accounts' => $active,
            'inactive_accounts' => $inactive,
            'unique_users' => $uniqueUsers,
            'accounts_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'gateway_distribution' => [
                'BANCOLOMBIA' => $bancolombiaCount,
                'DAVIPLATA' => $daviplataCount,
                'NEQUI' => $nequiCount
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getGatewayStatistics(string $gateway): array
    {
        $total = $this->model->where('gateway', $gateway)->count();
        $active = $this->model->where('gateway', $gateway)
            ->where('is_active', true)->count();
        $uniqueUsers = $this->model->where('gateway', $gateway)
            ->distinct('user_id')->count();

        return [
            'total_accounts' => $total,
            'active_accounts' => $active,
            'inactive_accounts' => $total - $active,
            'unique_users' => $uniqueUsers,
            'accounts_per_user' => $uniqueUsers > 0 ? round($total / $uniqueUsers, 2) : 0,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStatistics(string $userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $active = $this->model->where('user_id', $userId)
            ->where('is_active', true)->count();
        $gateways = $this->model->where('user_id', $userId)
            ->distinct('gateway')->pluck('gateway');

        return [
            'total_accounts' => $total,
            'active_accounts' => $active,
            'inactive_accounts' => $total - $active,
            'unique_gateways' => $gateways->count(),
            'gateways_used' => $gateways->toArray(),
            'has_bancolombia' => $gateways->contains('BANCOLOMBIA'),
            'has_daviplata' => $gateways->contains('DAVIPLATA'),
            'has_nequi' => $gateways->contains('NEQUI')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentAccounts(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByUserName(string $searchTerm): Collection
    {
        return $this->model->where('user_name', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueGateways(): Collection
    {
        return $this->model->distinct()->pluck('gateway');
    }

    /**
     * {@inheritDoc}
     */
    public function hasActiveAccountForGateway(string $userId, string $gateway): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('gateway', $gateway)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryAccountForGateway(string $userId, string $gateway): ?PaymentAccount
    {
        return $this->model->where('user_id', $userId)
            ->where('gateway', $gateway)
            ->where('is_active', true)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function isAccountUsedByOtherUser(
        string $accountNumber,
        string $gateway,
        ?string $excludeUserId = null
    ): bool {
        $query = $this->model->where('account_number', $accountNumber)
            ->where('gateway', $gateway);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }
}
