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
            $filters,
        )->paginate($perPage);
    }

    public function getLatestByWallet(string $walletId, int $limit = 10) : Collection
    {
        return $this->model
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Serie diaria (aprobados/rechazados/resolución media) para el gráfico
     * de tendencia histórica. Se agrupa por DATE(processed_at) — no
     * created_at — porque "aprobados/rechazados por día" debe reflejar
     * cuándo se resolvió, mismo criterio que stats() usa para "hoy"
     * (whereDate('processed_at', ...)). Huecos rellenados día a día, mismo
     * patrón que BuildsSalesTrend::buildSalesTrend().
     *
     * El eje de días respeta date_from/date_to cuando el admin los filtró en
     * la tabla — antes SIEMPRE mostraba "los últimos $days días desde hoy"
     * sin importar el rango elegido: $filters SÍ se aplicaba como condición
     * WHERE adicional (ver applyFilters()), pero el rango de fechas del EJE
     * X del gráfico ignoraba por completo date_from/date_to. Acotado a 90
     * días máximo (igual que el límite de $days) para no generar un gráfico
     * de miles de puntos si el admin filtra un rango muy amplio.
     */
    public function historicalTrend(array $filters, int $days = 30) : array
    {
        [$startDate, $daysToRender] = $this->resolveTrendRange($filters, $days);

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
        for ($i = 0; $i < $daysToRender; $i++) {
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

    public function loadForAdminPanel(WalletMovement $movement) : WalletMovement
    {
        return $movement->load(self::ADMIN_PANEL_RELATIONS);
    }

    /**
     * Mismo filtrado que getForAdminPanel() pero sin paginar — usado por
     * export CSV (todo el resultado filtrado) y por las stats del header
     * (agregados sobre el conjunto completo, no solo la página visible).
     */
    public function queryForAdminPanel(array $filters) : Builder
    {
        return $this->applyFilters(
            $this->model->with(self::ADMIN_PANEL_RELATIONS)->orderByDesc('created_at'),
            $filters,
        );
    }

    public function record(array $data) : WalletMovement
    {
        return $this->model->create($data);
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
    private function applyFilters(Builder $query, array $filters) : Builder
    {
        if (! empty($filters['type'])) {
            $query->where('type', strtoupper($filters['type']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            // Además de los datos del usuario, acepta el ID del movimiento
            // (completo, o los últimos 8 dígitos que muestra la tabla) y la
            // referencia bancaria — es lo que el soporte suele tener a mano
            // cuando un jugador reclama ("mi depósito ref. 123456").
            $query->where(function(Builder $q) use ($search) {
                $q->whereHas('user', fn ($u) => $u
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('reference_id', 'like', "%{$search}%");

                $digits = ltrim($search, '#');
                if (ctype_digit($digits)) {
                    strlen($digits) >= 15
                        ? $q->orWhere('wallet_movements.id', $digits)
                        : $q->orWhereRaw('CAST(wallet_movements.id AS CHAR) LIKE ?', ["%{$digits}"]);
                }
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['fraud_flag']) && $filters['fraud_flag'] === 'duplicate_receipt') {
            $query->whereNotNull('receipt_hash')
                ->whereExists(function($sub) {
                    $sub->selectRaw('1')
                        ->from('wallet_movements as wm2')
                        ->whereColumn('wm2.receipt_hash', 'wallet_movements.receipt_hash')
                        ->whereColumn('wm2.id', '!=', 'wallet_movements.id');
                });
        }

        if (! empty($filters['assigned_to_user_id'])) {
            $query->where(
                'assigned_to_user_id',
                $filters['assigned_to_user_id'] === 'mine' ? Auth::id() : $filters['assigned_to_user_id'],
            );
        }

        return $query;
    }

    /**
     * Resuelve el rango [fecha de inicio, cantidad de días] del eje del
     * gráfico: si hay date_from/date_to, ese rango exacto (acotado a 90 días
     * de ancho); si no, los últimos $days días desde hoy (comportamiento
     * previo, para cuando la tabla no tiene filtro de fecha activo).
     *
     * @return array{0: Carbon, 1: int}
     */
    private function resolveTrendRange(array $filters, int $days) : array
    {
        $dateFrom = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $dateTo = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->startOfDay() : null;

        if (! $dateFrom && ! $dateTo) {
            return [Carbon::now()->subDays($days - 1)->startOfDay(), $days];
        }

        // Solo uno de los dos presente: el otro extremo es "hasta hoy" o
        // "desde ese mismo día" — mismo criterio permisivo que
        // applyFilters()/whereDate() al aplicar cada bound por separado.
        $dateFrom ??= $dateTo;
        $dateTo ??= Carbon::now()->startOfDay();

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $rangeDays = min(90, $dateFrom->diffInDays($dateTo) + 1);

        return [$dateFrom, $rangeDays];
    }
}
