<?php

namespace App\Http\Controllers\Marketplace\Admin;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Domains\Marketplace\Escrow\Services\EscrowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de moderación de Escrows — búsqueda por user/order/escrow_id/status,
 * resolución de disputas. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 27.
 * Mismo patrón que WalletMovementController (filtros server-side reales,
 * authorize() vía Policy, handleServiceJson()).
 */
class EscrowAdminController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const ADMIN_RELATIONS = ['buyer', 'seller', 'playerAsset.assetDefinition', 'currency', 'order', 'resolvedBy'];

    public function __construct(
        private readonly EscrowService $escrowService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Escrow::class);

        return Inertia::render('Marketplace/Admin/Escrows/Index', [
            'title' => 'Escrows del Marketplace',
        ]);
    }

    /**
     * GET /admin/marketplace/escrows/fetch-list?status=&search=&page=
     * search matchea escrow_id (exacto), order_id (exacto), o nombre/email
     * de comprador/vendedor (like) — mismo criterio que WalletMovementRepository::getForAdminPanel().
     */
    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Escrow::class);

        return $this->handleServiceJson(function () use ($request) {
            $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

            $query = Escrow::with(self::ADMIN_RELATIONS)->orderByDesc('created_at');

            if ($status = $request->query('status')) {
                $query->where('status', strtoupper($status));
            }

            if ($search = $request->query('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('order_id', $search)
                        ->orWhereHas('buyer', fn ($u) => $u->where('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"))
                        ->orWhereHas('seller', fn ($u) => $u->where('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                });
            }

            $paginator = $query->paginate($perPage);

            return [
                'data' => collect($paginator->items())->map(fn (Escrow $e) => $this->mapEscrow($e)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                ],
            ];
        });
    }

    public function show(Escrow $escrow): JsonResponse
    {
        $this->authorize('view', $escrow);

        $escrow->load(self::ADMIN_RELATIONS);

        return $this->handleServiceJson(fn () => ['data' => $this->mapEscrow($escrow)]);
    }

    /**
     * POST /admin/marketplace/escrows/{escrow}/resolve
     * body: { resolution: BUYER_WINS | SELLER_WINS | CANCELLED }
     */
    public function resolve(Request $request, Escrow $escrow): JsonResponse
    {
        $this->authorize('resolve', $escrow);

        $request->validate([
            'resolution' => ['required', 'in:BUYER_WINS,SELLER_WINS,CANCELLED'],
        ]);

        return $this->handleServiceJson(function () use ($request, $escrow) {
            $resolved = $this->escrowService->resolve($escrow, $request->input('resolution'), $request->user());

            return ['data' => $this->mapEscrow($resolved->load(self::ADMIN_RELATIONS))];
        });
    }

    private function mapEscrow(Escrow $escrow): array
    {
        return [
            'id'             => (string) $escrow->id,
            'order_id'       => (string) $escrow->order_id,
            'status'         => $escrow->status,
            'amount'         => (float) $escrow->amount,
            'fee_amount'     => (float) $escrow->fee_amount,
            'seller_amount'  => (float) $escrow->seller_amount,
            'currency_symbol' => $escrow->currency->symbol,
            'buyer'          => $this->mapUser($escrow->buyer),
            'seller'         => $this->mapUser($escrow->seller),
            'asset'          => [
                'code' => $escrow->playerAsset->assetDefinition->code,
                'name' => $escrow->playerAsset->assetDefinition->name,
            ],
            'dispute_reason' => $escrow->dispute_reason,
            'resolution'     => $escrow->resolution,
            'resolved_by'    => $escrow->resolvedBy ? $this->mapUser($escrow->resolvedBy) : null,
            'resolved_at'    => $escrow->resolved_at?->toIso8601String(),
            'expires_at'     => $escrow->expires_at?->toIso8601String(),
            'created_at'     => $escrow->created_at->toIso8601String(),
        ];
    }

    private function mapUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id'   => (string) $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->username ?? $user->email),
        ];
    }
}
