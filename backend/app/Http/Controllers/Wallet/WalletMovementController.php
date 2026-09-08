<?php

namespace App\Http\Controllers\Wallet;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\MovementType;
use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Menu\SidebarMenuService;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Domains\Wallet\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WalletMovementController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly SidebarMenuService $sidebarMenuService,
        private readonly WalletFraudService $fraudService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WalletMovement::class);

        $perPage   = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $paginator = $this->movementRepository->getForAdminPanel([], $perPage);

        return Inertia::render('Wallet/Movements/Index', [
            'title'               => 'Movimientos de Billetera',
            'movements'           => collect($paginator->items())->map(fn (WalletMovement $m) => $this->mapMovement($m)),
            'movementTypes'       => MovementType::orderBy('label')->get(['code', 'label', 'description', 'icon', 'color', 'is_credit', 'requires_approval']),
            'meta'                => $this->mapMeta($paginator),
            'initialPendingCount' => $this->sidebarMenuService->pendingWalletMovementsCount(),
        ]);
    }

    /**
     * Lista paginada de movimientos con relaciones user y wallet.currency
     * GET /wallet-movements/fetch?page=1&per_page=20&type=&status=&search=
     */
    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function () use ($request) {
            $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

            $paginator = $this->movementRepository->getForAdminPanel($this->filtersFromRequest($request), $perPage);

            return [
                'data' => collect($paginator->items())->map(fn (WalletMovement $m) => $this->mapMovement($m)),
                'meta' => $this->mapMeta($paginator),
            ];
        });
    }

    /**
     * GET /wallet-movements/stats?type=&status=&search=&date_from=&date_to=
     * KPIs agregados sobre el conjunto filtrado (no solo la página visible):
     * pendientes, tiempo medio de resolución de hoy, volumen aprobado hoy,
     * tasa de rechazo de hoy. Todo derivado de wallet_movements, sin tablas
     * nuevas.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function () use ($request) {
            $filters = $this->filtersFromRequest($request);
            $query   = $this->movementRepository->queryForAdminPanel($filters);

            $pendingCount = (clone $query)->where('status', WalletMovement::STATUS_PENDING)->count();

            $todayProcessed = (clone $query)
                ->whereIn('status', [WalletMovement::STATUS_COMPLETED, WalletMovement::STATUS_REJECTED])
                ->whereDate('processed_at', now()->toDateString());

            $approvedToday = (clone $todayProcessed)->where('status', WalletMovement::STATUS_COMPLETED)->count();
            $rejectedToday = (clone $todayProcessed)->where('status', WalletMovement::STATUS_REJECTED)->count();
            $totalToday    = $approvedToday + $rejectedToday;

            $avgResolutionMinutes = (clone $todayProcessed)
                ->whereNotNull('processed_at')
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, processed_at)'));

            $approvedVolumeToday = (clone $todayProcessed)
                ->where('status', WalletMovement::STATUS_COMPLETED)
                ->sum('amount');

            return [
                'data' => [
                    'pending_count'          => $pendingCount,
                    'approved_today'         => $approvedToday,
                    'rejected_today'         => $rejectedToday,
                    'rejection_rate_today'   => $totalToday > 0 ? round($rejectedToday / $totalToday * 100, 1) : 0.0,
                    'avg_resolution_minutes' => $avgResolutionMinutes !== null ? round((float) $avgResolutionMinutes, 1) : null,
                    'approved_volume_today'  => (float) $approvedVolumeToday,
                ],
            ];
        });
    }

    /**
     * GET /wallet-movements/export?type=&status=&search=&date_from=&date_to=
     * Export CSV del conjunto filtrado completo (no solo la página visible)
     * — mismo patrón que AdminUserController::export().
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        $filters = $this->filtersFromRequest($request);
        $query   = $this->movementRepository->queryForAdminPanel($filters);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Usuario', 'Email', 'Tipo', 'Estado', 'Monto', 'Moneda', 'Referencia', 'Notas admin', 'Procesado por', 'Creado', 'Procesado']);

            $query->chunk(500, function ($movements) use ($handle) {
                foreach ($movements as $m) {
                    fputcsv($handle, [
                        $m->id,
                        trim(($m->user?->first_name ?? '') . ' ' . ($m->user?->last_name ?? '')),
                        $m->user?->email,
                        $m->type,
                        $m->status,
                        $m->amount,
                        $m->wallet?->currency?->iso_code,
                        $m->reference_id,
                        $m->admin_notes,
                        trim(($m->processedBy?->first_name ?? '') . ' ' . ($m->processedBy?->last_name ?? '')),
                        $m->created_at?->format('Y-m-d H:i:s'),
                        $m->processed_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 'movimientos-billetera-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(WalletMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $this->movementRepository->loadForAdminPanel($movement);

        return $this->handleServiceJson(fn () => ['data' => $this->mapMovement($movement, withFraudSignals: true)]);
    }

    /**
     * POST /wallet-movements/{movement}/approve
     * Aprueba un movimiento PENDING. DEPOSIT/WITHDRAWAL usan su lógica
     * específica; cualquier otro tipo con requires_approval=true en el
     * catálogo MovementType usa el flujo genérico.
     */
    public function approve(Request $request, WalletMovement $movement): JsonResponse
    {
        $this->authorize('approve', $movement);

        return $this->handleServiceJson(function () use ($request, $movement) {
            $adminNotes = $request->input('admin_notes');

            $this->assertRequiresApproval($movement);

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT    => $this->walletService->approveDeposit($movement, $adminNotes),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->approveWithdrawal($movement, $adminNotes),
                default => $this->walletService->approveGeneric($movement, $adminNotes),
            };

            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    /**
     * POST /wallet-movements/{movement}/reject
     * Rechaza un movimiento PENDING. DEPOSIT/WITHDRAWAL usan su lógica
     * específica; cualquier otro tipo con requires_approval=true en el
     * catálogo MovementType usa el flujo genérico (reintegra saldo si
     * is_credit=false).
     */
    public function reject(Request $request, WalletMovement $movement): JsonResponse
    {
        $this->authorize('reject', $movement);

        return $this->handleServiceJson(function () use ($request, $movement) {
            $adminNotes = $request->input('admin_notes');

            $this->assertRequiresApproval($movement);

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT    => $this->walletService->rejectDeposit($movement, $adminNotes),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->rejectWithdrawal($movement, $adminNotes),
                default => $this->walletService->rejectGeneric($movement, $adminNotes),
            };

            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array{type: ?string, status: ?string, search: ?string, date_from: ?string, date_to: ?string} */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'type'      => $request->query('type'),
            'status'    => $request->query('status'),
            'search'    => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
        ];
    }

    private function assertRequiresApproval(WalletMovement $movement): void
    {
        $type = MovementType::catalog()->get($movement->type);
        if (! $type || ! $type->requires_approval) {
            throw new \DomainException("El tipo {$movement->type} no requiere aprobación.");
        }
    }

    /** @return array{current_page: int, last_page: int, per_page: int, total: int} */
    private function mapMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];
    }

    private function mapMovement(WalletMovement $m, bool $withFraudSignals = false): array
    {
        return [
            'id'             => (string) $m->id,
            'type'           => $m->type,
            'status'         => $m->status,
            'amount'         => (float) $m->amount,
            'balance_before' => (float) $m->balance_before,
            'balance_after'  => (float) $m->balance_after,
            'description'    => $m->description,
            'reference_id'   => $m->reference_id,
            'receipt_path'   => $m->receipt_path,
            'receipt_url'    => $m->receipt_path ? route('wallet.receipt', ['filename' => basename($m->receipt_path)]) : null,
            'metadata'       => $m->metadata,
            'prize_type'     => $m->prize_type,
            'game_id'        => $m->game_id,
            'admin_notes'    => $m->admin_notes,
            'processed_at'   => $m->processed_at?->toISOString(),
            'processed_by'   => $m->processedBy ? [
                'id'         => (string) $m->processedBy->id,
                'first_name' => $m->processedBy->first_name,
                'last_name'  => $m->processedBy->last_name,
                'email'      => $m->processedBy->email,
            ] : null,
            'created_at' => $m->created_at,
            'currency'   => $m->wallet?->currency ? [
                'iso_code'       => $m->wallet->currency->iso_code,
                'symbol'         => $m->wallet->currency->symbol,
                'decimal_places' => $m->wallet->currency->decimal_places,
            ] : null,
            'user' => $m->user ? [
                'id'         => (string) $m->user->id,
                'first_name' => $m->user->first_name,
                'last_name'  => $m->user->last_name,
                'email'      => $m->user->email,
                'username'   => $m->user->username ?? null,
            ] : null,
            ...($withFraudSignals ? ['fraud_signals' => $this->buildFraudSignals($m)] : []),
        ];
    }

    /**
     * Señales de riesgo mostradas en el detalle del movimiento — solo se
     * calculan al abrir el detalle (show(), nunca en el listado) para no
     * meter N+1 queries en fetchList().
     *
     * @return array{
     *   duplicate_receipt_movement_id: ?string,
     *   deposits_today: ?int,
     *   deposits_today_max: ?int,
     *   recent_movements: array<int, array{id: string, type: string, status: string, amount: float, created_at: \Carbon\Carbon}>,
     *   rejection_rate: ?float,
     * }
     */
    private function buildFraudSignals(WalletMovement $m): array
    {
        $duplicateReceiptId = null;
        if ($m->receipt_hash) {
            $duplicateReceiptId = WalletMovement::where('receipt_hash', $m->receipt_hash)
                ->where('id', '!=', $m->id)
                ->orderByDesc('created_at')
                ->value('id');
        }

        $depositsToday = null;
        $depositsMax   = null;
        if ($m->type === WalletMovement::TYPE_DEPOSIT) {
            $depositsToday = $this->fraudService->depositsTodayCount((string) $m->user_id);
            $depositsMax   = $this->fraudService->maxDepositsPerDay();
        }

        $recentMovements = WalletMovement::where('user_id', $m->user_id)
            ->where('id', '!=', $m->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'type', 'status', 'amount', 'created_at'])
            ->map(fn (WalletMovement $rm) => [
                'id'         => (string) $rm->id,
                'type'       => $rm->type,
                'status'     => $rm->status,
                'amount'     => (float) $rm->amount,
                'created_at' => $rm->created_at,
            ])
            ->all();

        $processedCounts = WalletMovement::where('user_id', $m->user_id)
            ->whereIn('status', [WalletMovement::STATUS_COMPLETED, WalletMovement::STATUS_REJECTED])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = (int) ($processedCounts[WalletMovement::STATUS_COMPLETED] ?? 0);
        $rejected  = (int) ($processedCounts[WalletMovement::STATUS_REJECTED] ?? 0);
        $total     = $completed + $rejected;

        return [
            'duplicate_receipt_movement_id' => $duplicateReceiptId !== null ? (string) $duplicateReceiptId : null,
            'deposits_today'                => $depositsToday,
            'deposits_today_max'            => $depositsMax,
            'recent_movements'              => $recentMovements,
            'rejection_rate'                => $total > 0 ? round($rejected / $total * 100, 1) : null,
        ];
    }
}
