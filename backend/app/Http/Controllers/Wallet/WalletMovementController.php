<?php

namespace App\Http\Controllers\Wallet;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\MovementType;
use App\Domains\Wallet\Models\WalletMovement;
use App\Services\Menu\SidebarMenuService;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Domains\Wallet\Services\WalletService;
use App\Traits\StreamsCsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WalletMovementController extends Controller
{
    use StreamsCsvExport;

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
     * — usa StreamsCsvExport, mismo trait que AuditController/
     * BingoReportController/CommerceEconomyReportController (antes este
     * método reimplementaba el streaming manualmente con chunk()).
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        $filters = $this->filtersFromRequest($request);
        $query   = $this->movementRepository->queryForAdminPanel($filters);

        return $this->streamCsvExport(
            'movimientos-billetera',
            [
                'ID', 'Usuario', 'Email', 'Tipo', 'Estado', 'Monto', 'Moneda', 'Referencia',
                'Notas admin', 'Procesado por', 'Asignado a', 'Asignado el',
                'Balance antes', 'Balance después', 'Hash recibo', 'Creado', 'Procesado',
            ],
            $query->cursor(),
            fn (WalletMovement $m) => [
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
                $m->assignedTo ? trim("{$m->assignedTo->first_name} {$m->assignedTo->last_name}") : null,
                $m->assigned_at?->format('Y-m-d H:i:s'),
                $m->balance_before,
                $m->balance_after,
                $m->receipt_hash,
                $m->created_at?->format('Y-m-d H:i:s'),
                $m->processed_at?->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * GET /wallet-movements/export-pdf?type=&status=&search=&date_from=&date_to=
     * Resumen descargable en PDF: totales agregados del período + una
     * muestra acotada de filas de detalle (no todas — para eso está el CSV).
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', WalletMovement::class);

        $filters = $this->filtersFromRequest($request);
        $query   = $this->movementRepository->queryForAdminPanel($filters);

        $totals = [
            'approved' => (clone $query)->where('status', WalletMovement::STATUS_COMPLETED)->count(),
            'rejected' => (clone $query)->where('status', WalletMovement::STATUS_REJECTED)->count(),
            'pending'  => (clone $query)->where('status', WalletMovement::STATUS_PENDING)->count(),
        ];
        $totals['rejection_rate'] = ($totals['approved'] + $totals['rejected']) > 0
            ? round($totals['rejected'] / ($totals['approved'] + $totals['rejected']) * 100, 1)
            : 0.0;

        $amountByCurrency = (clone $query)
            ->where('status', WalletMovement::STATUS_COMPLETED)
            ->join('wallets', 'wallets.id', '=', 'wallet_movements.wallet_id')
            ->join('currencies', 'currencies.id', '=', 'wallets.currency_id')
            ->selectRaw('currencies.iso_code, currencies.symbol, SUM(wallet_movements.amount) as total')
            ->groupBy('currencies.iso_code', 'currencies.symbol')
            ->get();

        $oldestPending = (clone $query)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $pdf = Pdf::loadView('exports.wallet-movements-summary', [
            'filters'          => $filters,
            'totals'           => $totals,
            'amountByCurrency' => $amountByCurrency,
            'rows'             => $oldestPending,
            'generatedAt'      => now(),
        ]);

        return $pdf->download('resumen-billetera-' . now()->format('Y-m-d-His') . '.pdf');
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

    /**
     * POST /wallet-movements/bulk/approve
     * Aprueba varios movimientos PENDING en una sola pasada. Cada uno se
     * autoriza y procesa individualmente (misma lógica que approve()) — un
     * fallo puntual (ya no pendiente, tipo no aprobable, sin permiso,
     * bloqueado por otro admin) no aborta el lote: se omite y se reporta en
     * 'skipped' con motivo humano. Mismo shape de respuesta que
     * AdminUserController::toggleStatusBulk().
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate([
            'ids'         => 'required|array|min:1|max:100',
            'ids.*'       => 'string',
            'admin_notes' => 'nullable|string|max:512',
        ]);

        return $this->handleServiceJson(fn () => $this->processBulk(
            $request->input('ids'),
            $request->input('admin_notes'),
            'approve',
        ));
    }

    /**
     * POST /wallet-movements/bulk/reject
     * Mismo patrón que bulkApprove() pero rechaza (reintegra saldo si aplica).
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $request->validate([
            'ids'         => 'required|array|min:1|max:100',
            'ids.*'       => 'string',
            'admin_notes' => 'nullable|string|max:512',
        ]);

        return $this->handleServiceJson(fn () => $this->processBulk(
            $request->input('ids'),
            $request->input('admin_notes'),
            'reject',
        ));
    }

    /**
     * PUT /wallet-movements/{movement}/assign
     * Auto-asigna el movimiento al admin autenticado ("Tomar caso"). Solo
     * auto-asignación: no acepta user_id — evita el caso ambiguo de
     * asignarle trabajo a otro admin sin que lo pida. Un superadmin puede
     * forzar la reasignación simplemente tomando el caso él mismo.
     */
    public function assign(WalletMovement $movement): JsonResponse
    {
        $this->authorize('assign', $movement);

        return $this->handleServiceJson(function () use ($movement) {
            $updated = $this->walletService->assignMovement($movement);
            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    /**
     * PUT /wallet-movements/{movement}/unassign
     * Libera el caso ("Liberar"). El propio dueño de la asignación o un
     * superadmin pueden liberar.
     */
    public function unassign(WalletMovement $movement): JsonResponse
    {
        $this->authorize('assign', $movement);

        return $this->handleServiceJson(function () use ($movement) {
            $updated = $this->walletService->unassignMovement($movement);
            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array{type: ?string, status: ?string, search: ?string, date_from: ?string, date_to: ?string, fraud_flag: ?string, assigned_to_user_id: ?string} */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'type'                => $request->query('type'),
            'status'              => $request->query('status'),
            'search'              => $request->query('search'),
            'date_from'           => $request->query('date_from'),
            'date_to'             => $request->query('date_to'),
            'fraud_flag'          => $request->query('fraud_flag'),
            'assigned_to_user_id' => $request->query('assigned_to_user_id'),
        ];
    }

    /**
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    private function processBulk(array $ids, ?string $adminNotes, string $action): array
    {
        return DB::transaction(function () use ($ids, $adminNotes, $action) {
            $updated = 0;
            $skipped = [];

            foreach (WalletMovement::whereIn('id', $ids)->get() as $movement) {
                try {
                    if (! Auth::user()->can($action, $movement)) {
                        throw new \DomainException('No autorizado para este movimiento.');
                    }

                    $this->assertRequiresApproval($movement);

                    if ($action === 'approve') {
                        match ($movement->type) {
                            WalletMovement::TYPE_DEPOSIT    => $this->walletService->approveDeposit($movement, $adminNotes),
                            WalletMovement::TYPE_WITHDRAWAL => $this->walletService->approveWithdrawal($movement, $adminNotes),
                            default => $this->walletService->approveGeneric($movement, $adminNotes),
                        };
                    } else {
                        match ($movement->type) {
                            WalletMovement::TYPE_DEPOSIT    => $this->walletService->rejectDeposit($movement, $adminNotes),
                            WalletMovement::TYPE_WITHDRAWAL => $this->walletService->rejectWithdrawal($movement, $adminNotes),
                            default => $this->walletService->rejectGeneric($movement, $adminNotes),
                        };
                    }

                    $updated++;
                } catch (DomainException|\DomainException $e) {
                    // DomainException custom (guardNotLockedByOther(), approve*/reject*
                    // del WalletService) y \DomainException nativo (assertRequiresApproval(),
                    // el check de autorización arriba) — ninguna extiende la otra, así que
                    // ambas ramas son necesarias para que el aislamiento por item funcione
                    // sin importar cuál de los dos tipos dispare el fallo puntual.
                    $skipped[] = [
                        'id'     => (string) $movement->id,
                        'name'   => $this->movementLabel($movement),
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    private function movementLabel(WalletMovement $m): string
    {
        $userName = $m->user ? trim("{$m->user->first_name} {$m->user->last_name}") : null;
        $suffix   = $userName ? " ({$userName})" : '';

        return "{$m->type} #" . substr((string) $m->id, -8) . $suffix;
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
            'assigned_at' => $m->assigned_at?->toISOString(),
            'assigned_to' => $m->assignedTo ? [
                'id'         => (string) $m->assignedTo->id,
                'first_name' => $m->assignedTo->first_name,
                'last_name'  => $m->assignedTo->last_name,
                'email'      => $m->assignedTo->email,
            ] : null,
            // Saldo ACTUAL de la wallet del usuario — distinto de
            // balance_before/balance_after, que son el saldo antes/después de
            // ESTE movimiento puntual. Permite al admin ver de un vistazo si
            // el usuario ya movió el saldo por otro lado desde entonces.
            'wallet_balance'       => (float) ($m->wallet?->balance ?? 0),
            'wallet_bonus_balance' => (float) ($m->wallet?->bonus_balance ?? 0),
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
            ...($withFraudSignals ? [
                'fraud_signals' => $this->buildFraudSignals($m),
                'recent_audits' => $this->recentAudits($m),
                'has_more_audits' => $this->hasMoreAudits($m),
            ] : []),
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
     *   withdrawals_today: ?int,
     *   withdrawals_today_max: ?int,
     *   duplicate_reference: bool,
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

        $withdrawalsToday = null;
        $withdrawalsMax   = null;
        if ($m->type === WalletMovement::TYPE_WITHDRAWAL) {
            $withdrawalsToday = $this->fraudService->withdrawalsTodayCount((string) $m->user_id);
            $withdrawalsMax   = $this->fraudService->maxWithdrawalsPerDay();
        }

        $duplicateReference = $m->reference_id
            ? $this->fraudService->hasDuplicateReferenceInWindow((string) $m->wallet_id, $m->reference_id)
            : false;

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
            'withdrawals_today'             => $withdrawalsToday,
            'withdrawals_today_max'         => $withdrawalsMax,
            'duplicate_reference'           => $duplicateReference,
            'recent_movements'              => $recentMovements,
            'rejection_rate'                => $total > 0 ? round($rejected / $total * 100, 1) : null,
        ];
    }

    // ── Auditoría (D.4) ──────────────────────────────────────────────────────
    // Mismo patrón que AdminUserService::getUserAuditHistory()/mapAuditRow():
    // Audit::ofAuditable() + mapeo simple actor/evento/fecha, con "cargar más"
    // paginado propio en vez de forzar al admin a saltar a /audit.

    private const RECENT_AUDITS_LIMIT = 10;
    private const AUDIT_HISTORY_PAGE_SIZE = 15;

    private function recentAudits(WalletMovement $m): array
    {
        return Audit::ofAuditable(WalletMovement::class, $m->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->limit(self::RECENT_AUDITS_LIMIT)
            ->get()
            ->map(fn (Audit $audit) => $this->mapAuditRow($audit))
            ->all();
    }

    private function hasMoreAudits(WalletMovement $m): bool
    {
        return Audit::ofAuditable(WalletMovement::class, $m->id)->count() > self::RECENT_AUDITS_LIMIT;
    }

    /**
     * GET /wallet-movements/{movement}/audit-history?page=1
     * Historial de auditoría paginado — botón "Cargar más" del bloque de
     * auditoría en el modal de detalle, mismo contrato que
     * AdminUserService::getUserAuditHistory().
     */
    public function auditHistory(Request $request, WalletMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        return $this->handleServiceJson(function () use ($request, $movement) {
            $paginator = Audit::ofAuditable(WalletMovement::class, $movement->id)
                ->with('user:id,first_name,last_name,email')
                ->latest('created_at')
                ->paginate(self::AUDIT_HISTORY_PAGE_SIZE, ['*'], 'page', (int) $request->query('page', 1));

            return [
                'data' => [
                    'rows'         => $paginator->getCollection()->map(fn (Audit $audit) => $this->mapAuditRow($audit))->values(),
                    'has_more'     => $paginator->hasMorePages(),
                    'current_page' => $paginator->currentPage(),
                ],
            ];
        });
    }

    private function mapAuditRow(Audit $audit): array
    {
        return [
            'id'    => (string) $audit->id,
            'date'  => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor' => $audit->user?->full_name ?? 'Sistema',
            'event' => $audit->event,
        ];
    }

    // ── Tendencia histórica (A) ──────────────────────────────────────────────

    /**
     * GET /wallet-movements/trend?type=&status=&search=&date_from=&date_to=&fraud_flag=&assigned_to_user_id=&days=30
     * Serie diaria de aprobados/rechazados/tiempo de resolución media, para
     * el gráfico de tendencia histórica del dashboard. Mismos filtros que
     * stats()/fetchList() (filtersFromRequest()), para que el gráfico
     * refleje exactamente el mismo conjunto que ve el admin en la tabla.
     */
    public function trend(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function () use ($request) {
            $days = min(90, max(7, (int) $request->query('days', 30)));

            return ['data' => $this->movementRepository->historicalTrend($this->filtersFromRequest($request), $days)];
        });
    }
}
