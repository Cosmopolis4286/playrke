<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Repositories;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WalletMovementRepository extends BaseRepository implements WalletMovementRepositoryInterface
{
    private const ADMIN_PANEL_RELATIONS = [
        'wallet:id,currency_id',
        'wallet.currency:id,iso_code,symbol,decimal_places',
        'user:id,first_name,last_name,email,username',
        'processedBy:id,first_name,last_name,email',
    ];

    public function __construct(WalletMovement $model)
    {
        $this->model = $model;
    }

    public function getByUser(string $userId, int $perPage = 20) : LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByWallet(string $walletId, int $perPage = 20) : LengthAwarePaginator
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getForAdminPanel(array $filters, int $perPage = 20) : LengthAwarePaginator
    {
        $query = $this->model
            ->with(self::ADMIN_PANEL_RELATIONS)
            ->orderByDesc('created_at');

        if (! empty($filters['type'])) {
            $query->where('type', strtoupper($filters['type']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Mismo filtrado que getForAdminPanel() pero sin paginar — usado por
     * export CSV (todo el resultado filtrado) y por las stats del header
     * (agregados sobre el conjunto completo, no solo la página visible).
     */
    public function queryForAdminPanel(array $filters): Builder
    {
        $query = $this->model->with(self::ADMIN_PANEL_RELATIONS)->orderByDesc('created_at');

        if (! empty($filters['type'])) {
            $query->where('type', strtoupper($filters['type']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function getLatestByWallet(string $walletId, int $limit = 10) : Collection
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function loadForAdminPanel(WalletMovement $movement) : WalletMovement
    {
        return $movement->load(self::ADMIN_PANEL_RELATIONS);
    }

    public function record(array $data) : WalletMovement
    {
        return $this->model->create($data);
    }
}
