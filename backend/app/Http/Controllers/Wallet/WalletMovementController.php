<?php

namespace App\Http\Controllers\Wallet;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Domains\Wallet\Services\WalletService;
use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\MovementType;
use App\Services\Auth\RoleAuthorizationService;
use App\Services\Menu\SidebarMenuService;
use App\Traits\StreamsCsvExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class WalletMovementController extends Controller
{
    use StreamsCsvExport;
    private const APPROVE_NOTES_RULES = ['nullable', 'string', 'max:512'];
    private const AUDIT_HISTORY_PAGE_SIZE = 15;

    /** Umbral (horas) a partir del cual un PENDING se considera crítico — mismo que el banner de escalamiento. */
    private const CRITICAL_PENDING_HOURS = 24;

    private const DEFAULT_PER_PAGE = 20;

    // ── Auditoría (D.4) ──────────────────────────────────────────────────────
    // Mismo patrón que AdminUserService::getUserAuditHistory()/mapAuditRow():
    // Audit::ofAuditable() + mapeo simple actor/evento/fecha, con "cargar más"
    // paginado propio en vez de forzar al admin a saltar a /audit.

    private const RECENT_AUDITS_LIMIT = 10;

    /**
     * Motivo obligatorio al rechazar: el jugador recibe player_message en la
     * notificación de resultado (WalletService::broadcastAndNotifyResult()),
     * así que un rechazo sin motivo le llega sin ninguna explicación.
     */
    private const PLAYER_MESSAGE_RULES = ['required', 'string', 'min:5', 'max:512'];

    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly SidebarMenuService $sidebarMenuService,
        private readonly WalletFraudService $fraudService,
        private readonly RoleAuthorizationService $roleAuthorizationService,
    ) {
    }

    /**
     * POST /wallet-movements/{movement}/approve
     * Aprueba un movimiento PENDING. DEPOSIT/WITHDRAWAL usan su lógica
     * específica; cualquier otro tipo con requires_approval=true en el
     * catálogo MovementType usa el flujo genérico.
     */
    public function approve(Request $request, WalletMovement $movement) : JsonResponse
    {
        $this->authorize('approve', $movement);

        $request->validate(['admin_notes' => self::APPROVE_NOTES_RULES]);

        return $this->handleServiceJson(function() use ($request, $movement) {
            $adminNotes = $request->input('admin_notes');

            $this->assertRequiresApproval($movement);

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT => $this->walletService->approveDeposit($movement, $adminNotes),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->approveWithdrawal($movement, $adminNotes),
                default => $this->walletService->approveGeneric($movement, $adminNotes),
            };

            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    /**
     * PUT /wallet-movements/{movement}/assign
     * Auto-asigna el movimiento al admin autenticado ("Tomar caso"). Solo
     * auto-asignación: no acepta user_id — evita el caso ambiguo de
     * asignarle trabajo a otro admin sin que lo pida. Un superadmin puede
     * forzar la reasignación simplemente tomando el caso él mismo.
     */
    public function assign(WalletMovement $movement) : JsonResponse
    {
        $this->authorize('assign', $movement);

        return $this->handleServiceJson(function() use ($movement) {
            $updated = $this->walletService->assignMovement($movement);
            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    /**
     * GET /wallet-movements/{movement}/audit-history?page=1
     * Historial de auditoría paginado — botón "Cargar más" del bloque de
     * auditoría en el modal de detalle, mismo contrato que
     * AdminUserService::getUserAuditHistory().
     */
    public function auditHistory(Request $request, WalletMovement $movement) : JsonResponse
    {
        $this->authorize('view', $movement);

        return $this->handleServiceJson(function() use ($request, $movement) {
            $paginator = Audit::ofAuditable(WalletMovement::class, $movement->id)
                ->with('user:id,first_name,last_name,email')
                ->latest('created_at')
                ->paginate(self::AUDIT_HISTORY_PAGE_SIZE, ['*'], 'page', (int) $request->query('page', 1));

            return [
                'data' => [
                    'rows' => $paginator->getCollection()->map(fn (Audit $audit) => $this->mapAuditRow($audit))->values(),
                    'has_more' => $paginator->hasMorePages(),
                    'current_page' => $paginator->currentPage(),
                ],
            ];
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
    public function bulkApprove(Request $request) : JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'string',
            'admin_notes' => self::APPROVE_NOTES_RULES,
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
    public function bulkReject(Request $request) : JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'string',
            'admin_notes' => self::APPROVE_NOTES_RULES,
            'player_message' => self::PLAYER_MESSAGE_RULES,
        ], [
            'player_message.required' => 'Indica el motivo que recibirá cada jugador.',
            'player_message.min' => 'El motivo para los jugadores debe tener al menos :min caracteres.',
        ]);

        return $this->handleServiceJson(fn () => $this->processBulk(
            $request->input('ids'),
            trim((string) $request->input('admin_notes', '')) ?: null,
            'reject',
            trim((string) $request->input('player_message')),
        ));
    }

    /**
     * GET /wallet-movements/export?type=&status=&search=&date_from=&date_to=
     * Export CSV del conjunto filtrado completo (no solo la página visible)
     * — usa StreamsCsvExport, mismo trait que AuditController/
     * BingoReportController/CommerceEconomyReportController (antes este
     * método reimplementaba el streaming manualmente con chunk()).
     */
    public function export(Request $request) : StreamedResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        $filters = $this->filtersFromRequest($request);
        $query = $this->movementRepository->queryForAdminPanel($filters);

        return $this->streamCsvExport(
            'movimientos-billetera',
            [
                'ID', 'Usuario', 'Email', 'Tipo', 'Estado', 'Monto', 'Moneda', 'Referencia',
                'Notas admin', 'Procesado por', 'Asignado a', 'Asignado el',
                'Balance antes', 'Balance después', 'Hash recibo', 'Creado', 'Procesado',
            ],
            // cursor() ignora el with() de ADMIN_PANEL_RELATIONS (no puede
            // eager-loadear sobre un stream fila a fila) → 5 queries extra por
            // fila (user/wallet/currency/processedBy/assignedTo). lazyByIdDesc
            // pagina por keyset en bloques de 500 y SÍ eager-loadea cada bloque;
            // el ID Snowflake es monotónico en el tiempo, así que el orden
            // equivale al orderByDesc('created_at') original.
            $query->reorder()->lazyByIdDesc(500),
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
            ],
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
        $query = $this->movementRepository->queryForAdminPanel($filters);

        $totals = [
            'approved' => (clone $query)->where('status', WalletMovement::STATUS_COMPLETED)->count(),
            'rejected' => (clone $query)->where('status', WalletMovement::STATUS_REJECTED)->count(),
            'pending' => (clone $query)->where('status', WalletMovement::STATUS_PENDING)->count(),
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
            'filters' => $filters,
            'totals' => $totals,
            'amountByCurrency' => $amountByCurrency,
            'rows' => $oldestPending,
            'generatedAt' => now(),
        ]);

        return $pdf->download('resumen-billetera-' . now()->format('Y-m-d-His') . '.pdf');
    }

    /**
     * Lista paginada de movimientos con relaciones user y wallet.currency
     * GET /wallet-movements/fetch?page=1&per_page=20&type=&status=&search=
     */
    public function fetchList(Request $request) : JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function() use ($request) {
            $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

            $paginator = $this->movementRepository->getForAdminPanel($this->filtersFromRequest($request), $perPage);

            return [
                'data' => collect($paginator->items())->map(fn (WalletMovement $m) => $this->mapMovement($m)),
                'meta' => $this->mapMeta($paginator),
            ];
        });
    }

    public function index(Request $request) : Response
    {
        $this->authorize('viewAny', WalletMovement::class);

        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);
        $paginator = $this->movementRepository->getForAdminPanel([], $perPage);

        return Inertia::render('Wallet/Movements/Index', [
            'title' => 'Movimientos de Billetera',
            'movements' => collect($paginator->items())->map(fn (WalletMovement $m) => $this->mapMovement($m)),
            'movementTypes' => MovementType::orderBy('label')->get(['code', 'label', 'description', 'icon', 'color', 'is_credit', 'requires_approval']),
            'meta' => $this->mapMeta($paginator),
            'initialPendingCount' => $this->sidebarMenuService->pendingWalletMovementsCount(),
            // Oculta aprobar/rechazar/tomar caso/lote a perfiles de solo
            // lectura — el backend igual lo bloquea por ruta (multiPermission).
            'canManage' => $this->roleAuthorizationService->hasAnyPermission($request->user(), ['wallet.movements.manage']),
        ]);
    }

    /**
     * POST /wallet-movements/{movement}/reject
     * Rechaza un movimiento PENDING. DEPOSIT/WITHDRAWAL usan su lógica
     * específica; cualquier otro tipo con requires_approval=true en el
     * catálogo MovementType usa el flujo genérico (reintegra saldo si
     * is_credit=false).
     */
    public function reject(Request $request, WalletMovement $movement) : JsonResponse
    {
        $this->authorize('reject', $movement);

        $request->validate([
            'admin_notes' => self::APPROVE_NOTES_RULES,
            'player_message' => self::PLAYER_MESSAGE_RULES,
        ], [
            'player_message.required' => 'Indica el motivo que recibirá el jugador.',
            'player_message.min' => 'El motivo para el jugador debe tener al menos :min caracteres.',
        ]);

        return $this->handleServiceJson(function() use ($request, $movement) {
            $adminNotes = trim((string) $request->input('admin_notes', '')) ?: null;
            $playerMessage = trim((string) $request->input('player_message'));

            $this->assertRequiresApproval($movement);

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT => $this->walletService->rejectDeposit($movement, $adminNotes, $playerMessage),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->rejectWithdrawal($movement, $adminNotes, $playerMessage),
                default => $this->walletService->rejectGeneric($movement, $adminNotes, $playerMessage),
            };

            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    public function show(WalletMovement $movement) : JsonResponse
    {
        $this->authorize('view', $movement);

        $this->movementRepository->loadForAdminPanel($movement);

        return $this->handleServiceJson(fn () => ['data' => $this->mapMovement($movement, withFraudSignals: true)]);
    }

    /**
     * GET /wallet-movements/stats?type=&status=&search=&date_from=&date_to=
     * KPIs agregados sobre el conjunto filtrado (no solo la página visible):
     * pendientes, tiempo medio de resolución de hoy, volumen aprobado hoy,
     * tasa de rechazo de hoy. Todo derivado de wallet_movements, sin tablas
     * nuevas.
     */
    public function stats(Request $request) : JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function() use ($request) {
            $filters = $this->filtersFromRequest($request);
            $query = $this->movementRepository->queryForAdminPanel($filters);

            $pendingQuery = (clone $query)->where('status', WalletMovement::STATUS_PENDING);
            $pendingCount = (clone $pendingQuery)->count();

            // SLA de la cola: cuánto lleva esperando el pendiente más antiguo y
            // cuántos ya pasaron el umbral crítico — sin esto el admin solo ve
            // "N pendientes", no si alguno está a punto de escalar.
            $oldestPendingAt = (clone $pendingQuery)->reorder()->min('created_at');
            $criticalPendingCount = (clone $pendingQuery)
                ->where('created_at', '<=', now()->subHours(self::CRITICAL_PENDING_HOURS))
                ->count();

            $todayProcessed = (clone $query)
                ->whereIn('status', [WalletMovement::STATUS_COMPLETED, WalletMovement::STATUS_REJECTED])
                ->whereDate('processed_at', now()->toDateString());

            $approvedToday = (clone $todayProcessed)->where('status', WalletMovement::STATUS_COMPLETED)->count();
            $rejectedToday = (clone $todayProcessed)->where('status', WalletMovement::STATUS_REJECTED)->count();
            $totalToday = $approvedToday + $rejectedToday;

            $avgResolutionMinutes = (clone $todayProcessed)
                ->whereNotNull('processed_at')
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, processed_at)'));

            $approvedVolumeToday = (clone $todayProcessed)
                ->where('status', WalletMovement::STATUS_COMPLETED)
                ->sum('amount');

            return [
                'data' => [
                    'pending_count' => $pendingCount,
                    'critical_pending_count' => $criticalPendingCount,
                    'oldest_pending_at' => $oldestPendingAt ? \Carbon\Carbon::parse($oldestPendingAt)->toISOString() : null,
                    'approved_today' => $approvedToday,
                    'rejected_today' => $rejectedToday,
                    'rejection_rate_today' => $totalToday > 0 ? round($rejectedToday / $totalToday * 100, 1) : 0.0,
                    'avg_resolution_minutes' => $avgResolutionMinutes !== null ? round((float) $avgResolutionMinutes, 1) : null,
                    'approved_volume_today' => (float) $approvedVolumeToday,
                ],
            ];
        });
    }

    // ── Tendencia histórica (A) ──────────────────────────────────────────────

    /**
     * GET /wallet-movements/trend?type=&status=&search=&date_from=&date_to=&fraud_flag=&assigned_to_user_id=&days=30
     * Serie diaria de aprobados/rechazados/tiempo de resolución media, para
     * el gráfico de tendencia histórica del dashboard. Mismos filtros que
     * stats()/fetchList() (filtersFromRequest()), para que el gráfico
     * refleje exactamente el mismo conjunto que ve el admin en la tabla.
     */
    public function trend(Request $request) : JsonResponse
    {
        $this->authorize('viewAny', WalletMovement::class);

        return $this->handleServiceJson(function() use ($request) {
            $days = min(90, max(7, (int) $request->query('days', 30)));

            return ['data' => $this->movementRepository->historicalTrend($this->filtersFromRequest($request), $days)];
        });
    }

    /**
     * PUT /wallet-movements/{movement}/unassign
     * Libera el caso ("Liberar"). El propio dueño de la asignación o un
     * superadmin pueden liberar.
     */
    public function unassign(WalletMovement $movement) : JsonResponse
    {
        $this->authorize('assign', $movement);

        return $this->handleServiceJson(function() use ($movement) {
            $updated = $this->walletService->unassignMovement($movement);
            $this->movementRepository->loadForAdminPanel($updated);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    private function assertRequiresApproval(WalletMovement $movement) : void
    {
        $type = MovementType::catalog()->get($movement->type);
        if (! $type || ! $type->requires_approval) {
            throw new \DomainException("El tipo {$movement->type} no requiere aprobación.");
        }
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
    private function buildFraudSignals(WalletMovement $m) : array
    {
        $duplicateReceiptId = null;
        if ($m->receipt_hash) {
            $duplicateReceiptId = WalletMovement::where('receipt_hash', $m->receipt_hash)
                ->where('id', '!=', $m->id)
                ->orderByDesc('created_at')
                ->value('id');
        }

        $depositsToday = null;
        $depositsMax = null;
        if ($m->type === WalletMovement::TYPE_DEPOSIT) {
            $depositsToday = $this->fraudService->depositsTodayCount((string) $m->user_id);
            $depositsMax = $this->fraudService->maxDepositsPerDay();
        }

        $withdrawalsToday = null;
        $withdrawalsMax = null;
        if ($m->type === WalletMovement::TYPE_WITHDRAWAL) {
            $withdrawalsToday = $this->fraudService->withdrawalsTodayCount((string) $m->user_id);
            $withdrawalsMax = $this->fraudService->maxWithdrawalsPerDay();
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
                'id' => (string) $rm->id,
                'type' => $rm->type,
                'status' => $rm->status,
                'amount' => (float) $rm->amount,
                'created_at' => $rm->created_at,
            ])
            ->all();

        $processedCounts = WalletMovement::where('user_id', $m->user_id)
            ->whereIn('status', [WalletMovement::STATUS_COMPLETED, WalletMovement::STATUS_REJECTED])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = (int) ($processedCounts[WalletMovement::STATUS_COMPLETED] ?? 0);
        $rejected = (int) ($processedCounts[WalletMovement::STATUS_REJECTED] ?? 0);
        $total = $completed + $rejected;

        return [
            'duplicate_receipt_movement_id' => $duplicateReceiptId !== null ? (string) $duplicateReceiptId : null,
            'deposits_today' => $depositsToday,
            'deposits_today_max' => $depositsMax,
            'withdrawals_today' => $withdrawalsToday,
            'withdrawals_today_max' => $withdrawalsMax,
            'duplicate_reference' => $duplicateReference,
            'recent_movements' => $recentMovements,
            'rejection_rate' => $total > 0 ? round($rejected / $total * 100, 1) : null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array{type: ?string, status: ?string, search: ?string, date_from: ?string, date_to: ?string, fraud_flag: ?string, assigned_to_user_id: ?string} */
    private function filtersFromRequest(Request $request) : array
    {
        return [
            'type' => $request->query('type'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'fraud_flag' => $request->query('fraud_flag'),
            'assigned_to_user_id' => $request->query('assigned_to_user_id'),
        ];
    }

    private function hasMoreAudits(WalletMovement $m) : bool
    {
        return Audit::ofAuditable(WalletMovement::class, $m->id)->count() > self::RECENT_AUDITS_LIMIT;
    }

    private function mapAuditRow(Audit $audit) : array
    {
        return [
            'id' => (string) $audit->id,
            'date' => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor' => $audit->user?->full_name ?? 'Sistema',
            'event' => $audit->event,
        ];
    }

    /** @return array{current_page: int, last_page: int, per_page: int, total: int} */
    private function mapMeta(LengthAwarePaginator $paginator) : array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function mapMovement(WalletMovement $m, bool $withFraudSignals = false) : array
    {
        return [
            'id' => (string) $m->id,
            'type' => $m->type,
            'status' => $m->status,
            'amount' => (float) $m->amount,
            'balance_before' => (float) $m->balance_before,
            'balance_after' => (float) $m->balance_after,
            'description' => $m->description,
            'reference_id' => $m->reference_id,
            'receipt_path' => $m->receipt_path,
            'receipt_url' => $m->receipt_path ? route('wallet.receipt', ['filename' => basename($m->receipt_path)]) : null,
            'metadata' => $m->metadata,
            'prize_type' => $m->prize_type,
            'game_id' => $m->game_id,
            'admin_notes' => $m->admin_notes,
            'player_message' => $m->player_message,
            'processed_at' => $m->processed_at?->toISOString(),
            'processed_by' => $m->processedBy ? [
                'id' => (string) $m->processedBy->id,
                'first_name' => $m->processedBy->first_name,
                'last_name' => $m->processedBy->last_name,
                'email' => $m->processedBy->email,
            ] : null,
            'assigned_at' => $m->assigned_at?->toISOString(),
            'assigned_to' => $m->assignedTo ? [
                'id' => (string) $m->assignedTo->id,
                'first_name' => $m->assignedTo->first_name,
                'last_name' => $m->assignedTo->last_name,
                'email' => $m->assignedTo->email,
            ] : null,
            // Saldo ACTUAL de la wallet del usuario — distinto de
            // balance_before/balance_after, que son el saldo antes/después de
            // ESTE movimiento puntual. Permite al admin ver de un vistazo si
            // el usuario ya movió el saldo por otro lado desde entonces.
            'wallet_balance' => (float) ($m->wallet?->balance ?? 0),
            'wallet_bonus_balance' => (float) ($m->wallet?->bonus_balance ?? 0),
            'created_at' => $m->created_at,
            'currency' => $m->wallet?->currency ? [
                'iso_code' => $m->wallet->currency->iso_code,
                'symbol' => $m->wallet->currency->symbol,
                'decimal_places' => $m->wallet->currency->decimal_places,
            ] : null,
            'user' => $m->user ? [
                'id' => (string) $m->user->id,
                'first_name' => $m->user->first_name,
                'last_name' => $m->user->last_name,
                'email' => $m->user->email,
                'username' => $m->user->username ?? null,
            ] : null,
            ...($withFraudSignals ? [
                'fraud_signals' => $this->buildFraudSignals($m),
                'recent_audits' => $this->recentAudits($m),
                'has_more_audits' => $this->hasMoreAudits($m),
            ] : []),
        ];
    }

    private function movementLabel(WalletMovement $m) : string
    {
        $userName = $m->user ? trim("{$m->user->first_name} {$m->user->last_name}") : null;
        $suffix = $userName ? " ({$userName})" : '';

        return "{$m->type} #" . substr((string) $m->id, -8) . $suffix;
    }

    /**
     * @return array{updated: int, skipped: array<int, array{id: string, name: string, reason: string}>}
     */
    /**
     * Sin DB::transaction() externa (antes envolvía todo el lote): cada
     * approve*()/reject*() del WalletService ya es atómico por sí mismo (su
     * propia transacción + lockForUpdate), y además dispara broadcast +
     * notificación al jugador justo después de SU commit. Con una transacción
     * externa esos commits eran solo savepoints: (a) el jugador recibía
     * "depósito aprobado" antes del commit real, y si un item posterior lanzaba
     * una excepción no-dominio todo el lote hacía rollback con las
     * notificaciones ya enviadas; (b) los locks de hasta 100 filas quedaban
     * retenidos hasta el final del lote. Ahora cada item confirma por separado,
     * que es justo el aislamiento por item que promete el contrato `skipped`.
     */
    private function processBulk(array $ids, ?string $adminNotes, string $action, ?string $playerMessage = null) : array
    {
        $ids = array_values(array_unique(array_map('strval', $ids)));
        $updated = 0;
        $skipped = [];

        $movements = WalletMovement::with('user:id,first_name,last_name')->whereIn('id', $ids)->get();

        // IDs que ya no existen (o nunca existieron) — antes desaparecían en
        // silencio del reporte y el admin veía "N-1 procesados" sin saber por qué.
        $foundIds = $movements->map(fn (WalletMovement $m) => (string) $m->id)->all();
        foreach (array_diff($ids, $foundIds) as $missingId) {
            $skipped[] = [
                'id' => $missingId,
                'name' => '#' . substr($missingId, -8),
                'reason' => 'El movimiento no existe.',
            ];
        }

        foreach ($movements as $movement) {
            try {
                if (! Auth::user()->can($action, $movement)) {
                    throw new \DomainException('No autorizado para este movimiento (ya no está pendiente o su tipo no requiere aprobación).');
                }

                $this->assertRequiresApproval($movement);

                if ($action === 'approve') {
                    match ($movement->type) {
                        WalletMovement::TYPE_DEPOSIT => $this->walletService->approveDeposit($movement, $adminNotes),
                        WalletMovement::TYPE_WITHDRAWAL => $this->walletService->approveWithdrawal($movement, $adminNotes),
                        default => $this->walletService->approveGeneric($movement, $adminNotes),
                    };
                } else {
                    match ($movement->type) {
                        WalletMovement::TYPE_DEPOSIT => $this->walletService->rejectDeposit($movement, $adminNotes, $playerMessage),
                        WalletMovement::TYPE_WITHDRAWAL => $this->walletService->rejectWithdrawal($movement, $adminNotes, $playerMessage),
                        default => $this->walletService->rejectGeneric($movement, $adminNotes, $playerMessage),
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
                    'id' => (string) $movement->id,
                    'name' => $this->movementLabel($movement),
                    'reason' => $e->getMessage(),
                ];
            } catch (Throwable $e) {
                // Fallo inesperado en UN item: sin transacción externa, los items
                // previos ya están confirmados — abortar con 500 haría creer al
                // admin que no se procesó nada. Se reporta y se sigue con el resto.
                Log::error('[WalletMovementController] Fallo inesperado en lote', [
                    'action' => $action,
                    'movement_id' => (string) $movement->id,
                    'error' => $e->getMessage(),
                ]);

                $skipped[] = [
                    'id' => (string) $movement->id,
                    'name' => $this->movementLabel($movement),
                    'reason' => 'Error inesperado al procesarlo — revisa el detalle e inténtalo individualmente.',
                ];
            }
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    private function recentAudits(WalletMovement $m) : array
    {
        return Audit::ofAuditable(WalletMovement::class, $m->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->limit(self::RECENT_AUDITS_LIMIT)
            ->get()
            ->map(fn (Audit $audit) => $this->mapAuditRow($audit))
            ->all();
    }
}
