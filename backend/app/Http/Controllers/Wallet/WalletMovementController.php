<?php

namespace App\Http\Controllers\Wallet;

use App\Contracts\WalletMovementRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\MovementType;
use App\Models\WalletMovement;
use App\Services\Menu\SidebarMenuService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WalletMovementController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletMovementRepositoryInterface $movementRepository,
        private readonly SidebarMenuService $sidebarMenuService,
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

            $paginator = $this->movementRepository->getForAdminPanel([
                'type'   => $request->query('type'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ], $perPage);

            return [
                'data' => collect($paginator->items())->map(fn (WalletMovement $m) => $this->mapMovement($m)),
                'meta' => $this->mapMeta($paginator),
            ];
        });
    }

    public function show(WalletMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $this->movementRepository->loadForAdminPanel($movement);

        return $this->handleServiceJson(fn () => ['data' => $this->mapMovement($movement)]);
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

    private function mapMovement(WalletMovement $m): array
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
        ];
    }
}
