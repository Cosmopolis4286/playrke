<?php

namespace App\Repositories\Wallets\Crypto;

use App\Models\Directories\Crypto\Binance\BinanceAccounts;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Wallets\Crypto\Contracts\BinanceAccountsRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para operaciones con cuentas Binance
 * Maneja todas las consultas complejas relacionadas con BinanceAccounts
 */
class BinanceAccountsRepository extends BaseRepository implements BinanceAccountsRepositoryContract
{
    public function __construct(BinanceAccounts $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener cuentas por tipo
     *
     * @param string $accountType Tipo de cuenta (E/P/B)
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getByAccountType(string $accountType, array $columns = ['*']): Collection
    {
        return $this->model->where('account_type', strtoupper($accountType))
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Obtener cuentas por usuario
     *
     * @param string $userId ID del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getByUserId(string $userId, array $columns = ['*']): Collection
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Obtener cuentas activas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getActive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('ACTIVE', $columns);
    }

    /**
     * Obtener cuentas inactivas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getInactive(array $columns = ['*']): Collection
    {
        return $this->getByStatus('INACTIVE', $columns);
    }

    /**
     * Obtener cuentas bloqueadas
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getBlocked(array $columns = ['*']): Collection
    {
        return $this->getByStatus('BLOCKED', $columns);
    }

    /**
     * Verificar si existe una cuenta
     *
     * @param string $accountType Tipo de cuenta
     * @param string $dataValue Valor de los datos
     * @param string|null $excludeUserId ID de usuario a excluir de la búsqueda
     * @return bool
     */
    public function existsAccount(string $accountType, string $dataValue, ?string $excludeUserId = null): bool
    {
        $query = $this->model->where('account_type', strtoupper($accountType))
            ->where('data_value', $dataValue);

        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Buscar cuenta por valor de datos
     *
     * @param string $dataValue Valor de los datos
     * @param array $columns Columnas a seleccionar
     * @return BinanceAccounts|null
     */
    public function findByDataValue(string $dataValue, array $columns = ['*']): ?BinanceAccounts
    {
        return $this->model->where('data_value', $dataValue)->first($columns);
    }

    /**
     * Buscar cuentas por valor parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function searchByDataValue(string $searchTerm, array $columns = ['*']): Collection
    {
        return $this->model->where('data_value', 'like', "%{$searchTerm}%")
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Obtener cuentas de email
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getEmailAccounts(array $columns = ['*']): Collection
    {
        return $this->getByAccountType('E', $columns);
    }

    /**
     * Obtener cuentas de teléfono
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getPhoneAccounts(array $columns = ['*']): Collection
    {
        return $this->getByAccountType('P', $columns);
    }

    /**
     * Obtener cuentas por ID de Binance
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getBinanceIdAccounts(array $columns = ['*']): Collection
    {
        return $this->getByAccountType('B', $columns);
    }

    /**
     * Obtener cuentas activas por usuario
     *
     * @param string $userId ID del usuario
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getActiveByUserId(string $userId, array $columns = ['*']): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Contar cuentas por tipo
     *
     * @return array Array con conteo por tipo
     */
    public function getCountByType(): array
    {
        $counts = $this->model->selectRaw('account_type, COUNT(*) as count')
            ->groupBy('account_type')
            ->pluck('count', 'account_type')
            ->toArray();

        return [
            'E' => $counts['E'] ?? 0,
            'P' => $counts['P'] ?? 0,
            'B' => $counts['B'] ?? 0,
            'total' => array_sum($counts)
        ];
    }

    /**
     * Contar cuentas por estado
     *
     * @return array Array con conteo por estado
     */
    public function getCountByStatus(): array
    {
        $counts = $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'ACTIVE' => $counts['ACTIVE'] ?? 0,
            'INACTIVE' => $counts['INACTIVE'] ?? 0,
            'BLOCKED' => $counts['BLOCKED'] ?? 0,
            'total' => array_sum($counts)
        ];
    }

    /**
     * Obtener cuentas con usuario eager loaded
     *
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getAllWithUser(array $columns = ['*']): Collection
    {
        return $this->model->with('user')
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Cambiar estado de cuenta
     *
     * @param string $accountId ID de la cuenta
     * @param string $status Nuevo estado
     * @return bool
     */
    public function changeStatus(string $accountId, string $status): bool
    {
        return $this->model->where('id', $accountId)
            ->update(['status' => strtoupper($status)]) > 0;
    }

    /**
     * Obtener tipos de cuenta disponibles
     *
     * @param string|null $excludeType Tipo a excluir
     * @return array
     */
    public function getAccountTypes(?string $excludeType = null): array
    {
        $types = BinanceAccounts::TYPES;

        if ($excludeType) {
            unset($types[$excludeType]);
        }

        return $types;
    }

    /**
     * Obtener cuentas por múltiples usuarios
     *
     * @param array $userIds Array de IDs de usuarios
     * @param array $columns Columnas a seleccionar
     * @return Collection<BinanceAccounts>
     */
    public function getByMultipleUsers(array $userIds, array $columns = ['*']): Collection
    {
        return $this->model->whereIn('user_id', $userIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Verificar si un usuario tiene cuentas
     *
     * @param string $userId ID del usuario
     * @return bool
     */
    public function userHasAccounts(string $userId): bool
    {
        return $this->model->where('user_id', $userId)->exists();
    }

    /**
     * Obtener estadísticas de cuentas por usuario
     *
     * @param string $userId ID del usuario
     * @return array
     */
    public function getUserAccountStats(string $userId): array
    {
        $total = $this->model->where('user_id', $userId)->count();
        $active = $this->model->where('user_id', $userId)->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('user_id', $userId)->where('status', 'INACTIVE')->count();
        $blocked = $this->model->where('user_id', $userId)->where('status', 'BLOCKED')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'blocked' => $blocked,
            'percentage_active' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    // =================================
    // Métodos legacy mantenidos temporalmente
    // =================================

    /**
     * Obtener cuenta por ID (método legacy)
     *
     * @param string $id ID de la cuenta
     * @param array $fields Campos a seleccionar
     * @return BinanceAccounts|null
     */
    public function getById(string $id, array $fields = ['*']): ?BinanceAccounts
    {
        return $this->find($id, $fields);
    }
}
