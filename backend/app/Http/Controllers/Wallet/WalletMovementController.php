<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\WalletMovement;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WalletMovementController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function index(): Response
    {
        $this->authorize('viewAny', WalletMovement::class);

        $paginator = WalletMovement::with([
            'wallet:id,currency_id',
            'wallet.currency:id,iso_code,symbol,decimal_places',
            'user:id,first_name,last_name,email,username',
            'processedBy:id,first_name,last_name,email',
        ])
        ->orderByDesc('created_at')
        ->paginate(20);

        return Inertia::render('Wallet/Movements/Index', [
            'title'     => 'Movimientos de Billetera',
            'movements' => collect($paginator->items())->map(fn(WalletMovement $m) => $this->mapMovement($m)),
            'meta'      => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
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
            $perPage = (int) $request->query('per_page', 20);
            $type    = $request->query('type');
            $status  = $request->query('status');
            $search  = $request->query('search');

            $query = WalletMovement::with([
                'wallet:id,currency_id',
                'wallet.currency:id,iso_code,symbol,decimal_places',
                'user:id,first_name,last_name,email,username',
                'processedBy:id,first_name,last_name,email',
            ])
            ->orderByDesc('created_at');

            if ($type)   $query->where('type', strtoupper($type));
            if ($status) $query->where('status', strtoupper($status));
            if ($search) {
                $query->whereHas('user', fn($q) =>
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                );
            }

            $paginator = $query->paginate($perPage);

            return [
                'data' => collect($paginator->items())->map(fn(WalletMovement $m) => $this->mapMovement($m)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ];
        });
    }

    public function show(WalletMovement $movement): JsonResponse
    {
        $this->authorize('view', $movement);

        $movement->load([
            'wallet:id,currency_id',
            'wallet.currency:id,iso_code,symbol,decimal_places',
            'user:id,first_name,last_name,email,username',
            'processedBy:id,first_name,last_name,email',
        ]);

        return $this->handleServiceJson(fn() => ['data' => $this->mapMovement($movement)]);
    }

    /**
     * POST /wallet-movements/{movement}/approve
     * Aprueba un DEPOSIT o WITHDRAWAL PENDING.
     */
    public function approve(Request $request, WalletMovement $movement): JsonResponse
    {
        $this->authorize('approve', $movement);

        return $this->handleServiceJson(function () use ($request, $movement) {
            $adminNotes = $request->input('admin_notes');

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT    => $this->walletService->approveDeposit($movement, $adminNotes),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->approveWithdrawal($movement, $adminNotes),
                default => throw new \DomainException("No se puede aprobar un movimiento de tipo {$movement->type}."),
            };

            $updated->load([
                'wallet:id,currency_id',
                'wallet.currency:id,iso_code,symbol,decimal_places',
                'user:id,first_name,last_name,email,username',
                'processedBy:id,first_name,last_name,email',
            ]);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    /**
     * POST /wallet-movements/{movement}/reject
     * Rechaza un DEPOSIT o WITHDRAWAL PENDING.
     * En retiros: devuelve el saldo al usuario.
     */
    public function reject(Request $request, WalletMovement $movement): JsonResponse
    {
        $this->authorize('reject', $movement);

        return $this->handleServiceJson(function () use ($request, $movement) {
            $adminNotes = $request->input('admin_notes');

            $updated = match ($movement->type) {
                WalletMovement::TYPE_DEPOSIT    => $this->walletService->rejectDeposit($movement, $adminNotes),
                WalletMovement::TYPE_WITHDRAWAL => $this->walletService->rejectWithdrawal($movement, $adminNotes),
                default => throw new \DomainException("No se puede rechazar un movimiento de tipo {$movement->type}."),
            };

            $updated->load([
                'wallet:id,currency_id',
                'wallet.currency:id,iso_code,symbol,decimal_places',
                'user:id,first_name,last_name,email,username',
                'processedBy:id,first_name,last_name,email',
            ]);

            return ['data' => $this->mapMovement($updated)];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
            'receipt_url'    => $m->receipt_path ? '/storage/' . $m->receipt_path : null,
            'metadata'       => $m->metadata,
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
