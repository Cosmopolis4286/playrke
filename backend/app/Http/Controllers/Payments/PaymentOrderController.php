<?php

namespace App\Http\Controllers\Payments;

use App\Domains\Payments\Exceptions\PaymentProductNotAvailableException;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Services\PaymentOrderService;
use App\Domains\Payments\Services\QrCodeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\CreatePaymentOrderRequest;
use App\Http\Resources\Payments\PaymentOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Controller delgado — toda la lógica de negocio vive en PaymentOrderService/
 * BlockchainWatcherService/PurchaseService. Ver PAYMENTS_USDT_TRC20_DESIGN.md
 * sección 5.
 *
 * findForUser() en vez de route-model-binding + policy: mismo criterio que
 * MarketplaceController::myOrders() — filtra por user_id directamente en la
 * query, un usuario nunca puede ver la orden de otro (evita IDOR sin
 * necesitar una policy dedicada para un recurso sin acciones de terceros).
 */
class PaymentOrderController extends Controller
{
    public function __construct(
        private readonly PaymentOrderService $orderService,
        private readonly QrCodeService $qrCodeService,
    ) {
    }

    public function page(string $order): InertiaResponse
    {
        $order = $this->findOrFail($order);

        return Inertia::render('Payments/Show', [
            'order' => new PaymentOrderResource($order->load('paymentProduct')),
        ]);
    }

    public function store(CreatePaymentOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->create(
                userId: (string) Auth::id(),
                paymentProductCode: $request->validated('payment_product_code'),
            );

            return (new PaymentOrderResource($order))->response()->setStatusCode(201);
        } catch (PaymentProductNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $order): JsonResponse
    {
        $order = $this->findOrFail($order);

        return (new PaymentOrderResource($order->load('paymentProduct')))->response();
    }

    public function status(string $order): JsonResponse
    {
        $order = $this->findOrFail($order);

        return response()->json([
            'status' => $order->status,
            'expected_amount' => $order->expected_amount,
            'received_amount' => $order->received_amount,
            'expires_at' => $order->expires_at?->toIso8601String(),
        ]);
    }

    public function qr(string $order): Response
    {
        $order = $this->findOrFail($order);

        $png = $this->qrCodeService->pngForAddress($order->deposit_address);

        return response($png, 200)->header('Content-Type', 'image/png');
    }

    private function findOrFail(string $orderId): PaymentOrder
    {
        $order = $this->orderService->findForUser($orderId, (string) Auth::id());

        abort_if(!$order, 404);

        return $order;
    }
}
