<?php

namespace App\Http\Controllers\Payments\Admin;

use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Services\BlockchainWatcherService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel admin de órdenes de pago USDT-TRC20 — listado con filtros y acción
 * de reevaluar manualmente contra la blockchain. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 10 (Fase G). Mismo patrón que
 * EscrowAdminController: filtros server-side reales, authorize() vía Policy,
 * handleServiceJson().
 *
 * reevaluate() nunca puede duplicar una entrega: BlockchainWatcherService
 * ::evaluate() solo avanza estado según lo que observa on-chain, y
 * PurchaseService::deliver() es idempotente por payment_order_id UNIQUE —
 * reevaluar una orden ya CREDITED es un no-op seguro (bloqueado además por
 * la policy, que exige !isTerminal()).
 */
class PaymentOrderAdminController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const ADMIN_RELATIONS = ['user', 'paymentProduct'];

    public function __construct(
        private readonly BlockchainWatcherService $watcher,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', PaymentOrder::class);

        return Inertia::render('Payments/Admin/Index', [
            'title' => 'Órdenes de pago USDT-TRC20',
        ]);
    }

    /**
     * GET /admin/payments/orders/fetch-list?status=&search=&page=
     * search matchea order_id (exacto), deposit_address (exacto), o
     * nombre/email del usuario (like).
     */
    public function fetchList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentOrder::class);

        return $this->handleServiceJson(function () use ($request) {
            $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

            $query = PaymentOrder::with(self::ADMIN_RELATIONS)->orderByDesc('created_at');

            if ($status = $request->query('status')) {
                $query->where('status', strtoupper($status));
            }

            if ($search = $request->query('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('deposit_address', $search)
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%"));
                });
            }

            $paginator = $query->paginate($perPage);

            return [
                'data' => collect($paginator->items())->map(fn (PaymentOrder $o) => $this->mapOrder($o)),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                ],
            ];
        });
    }

    public function show(PaymentOrder $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load([...self::ADMIN_RELATIONS, 'attempts' => fn ($q) => $q->orderByDesc('created_at')]);

        return $this->handleServiceJson(fn () => [
            'data' => [
                ...$this->mapOrder($order),
                'attempts' => $order->attempts->map(fn ($a) => [
                    'tx_hash' => $a->tx_hash,
                    'attempted_action' => $a->attempted_action,
                    'result' => $a->result,
                    'reason' => $a->reason,
                    'created_at' => $a->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * POST /admin/payments/orders/{order}/reevaluate
     */
    public function reevaluate(PaymentOrder $order): JsonResponse
    {
        $this->authorize('reevaluate', $order);

        return $this->handleServiceJson(function () use ($order) {
            $reevaluated = $this->watcher->evaluate($order);

            return ['data' => $this->mapOrder($reevaluated->load(self::ADMIN_RELATIONS))];
        });
    }

    private function mapOrder(PaymentOrder $order): array
    {
        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'network' => $order->network,
            'token' => $order->token,
            'deposit_address' => $order->deposit_address,
            'expected_amount' => (float) $order->expected_amount,
            'received_amount' => $order->received_amount !== null ? (float) $order->received_amount : null,
            'user' => $this->mapUser($order->user),
            'product' => $order->paymentProduct ? [
                'code' => $order->paymentProduct->code,
                'name' => $order->paymentProduct->name,
            ] : null,
            'detected_at' => $order->detected_at?->toIso8601String(),
            'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            'expires_at' => $order->expires_at?->toIso8601String(),
            'created_at' => $order->created_at->toIso8601String(),
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
