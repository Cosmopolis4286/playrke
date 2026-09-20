<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Repositories;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class WalletMovementRepository extends BaseRepository implements WalletMovementRepositoryInterface
{
    private const ADMIN_PANEL_RELATIONS = [
        'wallet:id,currency_id,balance,bonus_balance',
        'wallet.currency:id,iso_code,symbol,decimal_places',
        'user:id,first_name,last_name,email,username',
        'processedBy:id,first_name,last_name,email',
        'assignedTo:id,first_name,last_name,email',
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
        return $this->applyFilters(
            $this->model->with(self::ADMIN_PANEL_RELATIONS)->orderByDesc('created_at'),
            $filters
        )->paginate($perPage);
    }

    /**
     * Mismo filtrado que getForAdminPanel() pero sin paginar — usado por
     * export CSV (todo el resultado filtrado) y por las stats del header
     * (agregados sobre el conjunto completo, no solo la página visible).
     */
    public function queryForAdminPanel(array $filters): Builder
    {
        return $this->applyFilters(
            $this->model->with(self::ADMIN_PANEL_RELATIONS)->orderByDesc('created_at'),
            $filters
        );
    }

    /**
     * Filtros compartidos del panel admin — antes duplicados línea por línea
     * entre getForAdminPanel() (paginado) y queryForAdminPanel() (stats y
     * export).
     *
     * 'fraud_flag' solo cubre la señal de riesgo barata de expresar como
     * predicado SQL (receipt_hash duplicado, vía self-join) — las otras 3
     * señales de WalletMovementController::buildFraudSignals() (velocidad de
     * depósitos, historial reciente, tasa de rechazo histórica) requieren
     * agregación por usuario o un servicio cacheado, y se quedan como
     * informativas solo en el detalle (show()), no como filtro de listado,
     * para no meter una agregación costosa sobre miles de filas en cada
     * fetchList().
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
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

        if (! empty($filters['fraud_flag']) && $filters['fraud_flag'] === 'duplicate_receipt') {
            $query->whereNotNull('receipt_hash')
                ->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('wallet_movements as wm2')
                        ->whereColumn('wm2.receipt_hash', 'wallet_movements.receipt_hash')
                        ->whereColumn('wm2.id', '!=', 'wallet_movements.id');
                });
        }

        if (! empty($filters['assigned_to_user_id'])) {
            $query->where(
                'assigned_to_user_id',
                $filters['assigned_to_user_id'] === 'mine' ? Auth::id() : $filters['assigned_to_user_id']
            );
        }

        return $query;
    }

    /**
     * Serie diaria (aprobados/rechazados/resolución media) para el gráfico
     * de tendencia histórica. Se agrupa por DATE(processed_at) — no
     * created_at — porque "aprobados/rechazados por día" debe reflejar
     * cuándo se resolvió, mismo criterio que stats() usa para "hoy"
     * (whereDate('processed_at', ...)). Huecos rellenados día a día, mismo
     * patrón que BuildsSalesTrend::buildSalesTrend().
     */
    public function historicalTrend(array $filters, int $days = 30) : array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $base = $this->applyFilters($this->model->newQuery(), $filters)
            ->where('created_at', '>=', $startDate);

        $approved = (clone $base)
            ->where('status', WalletMovement::STATUS_COMPLETED)
            ->whereNotNull('processed_at')
            ->selectRaw('DATE(processed_at) as date, COUNT(*) as count, AVG(TIMESTAMPDIFF(MINUTE, created_at, processed_at)) as avg_minutes')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $rejected = (clone $base)
            ->where('status', WalletMovement::STATUS_REJECTED)
            ->whereNotNull('processed_at')
            ->selectRaw('DATE(processed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            $approvedRow = $approved->get($date);

            $trend[] = [
                'date' => $date,
                'approved' => (int) ($approvedRow->count ?? 0),
                'rejected' => (int) ($rejected[$date] ?? 0),
                'avg_resolution_minutes' => $approvedRow?->avg_minutes !== null
                    ? round((float) $approvedRow->avg_minutes, 1)
                    : null,
            ];
        }

        return $trend;
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
