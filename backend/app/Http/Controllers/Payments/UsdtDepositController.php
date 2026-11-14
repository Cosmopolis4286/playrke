<?php

namespace App\Http\Controllers\Payments;

use App\Domains\Payments\Exceptions\UsdtDepositException;
use App\Domains\Payments\Models\UsdtDepositOrder;
use App\Domains\Payments\Services\QrCodeService;
use App\Domains\Payments\Services\UsdtDepositService;
use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Depósito USDT-TRC20 del jugador: pide un monto, paga el monto único a la
 * dirección mostrada (QR) y el watcher lo acredita. Si algo no cuadra,
 * reporta el txid y tesorería lo revisa.
 */
class UsdtDepositController extends Controller
{
    /** @return array<string, mixed> */
    public static function present(UsdtDepositOrder $order) : array
    {
        return [
            'id' => (string) $order->id,
            'status' => $order->status,
            'requested_amount' => $order->requested_amount,
            'pay_amount' => $order->payAmount(),
            'deposit_address' => $order->deposit_address,
            'tx_hash' => $order->tx_hash,
            'received_amount' => $order->received_amount,
            'credited_amount' => $order->credited_amount,
            'review_reason' => $order->status === UsdtDepositOrder::STATUS_REJECTED ? $order->review_reason : null,
            'expires_at' => $order->expires_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    public function __construct(
        private readonly UsdtDepositService $deposits,
        private readonly QrCodeService $qr,
    ) {
    }

    public function claim(Request $request, string $order) : JsonResponse
    {
        $data = $request->validate(['tx_hash' => ['required', 'string', 'max:100']]);

        try {
            $updated = $this->deposits->claim($this->findOrFail($order), $data['tx_hash']);
        } catch (DomainException $e) {
            return $this->error($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recibimos tu reporte. Tesorería verificará el pago y lo acreditará.',
            'data' => self::present($updated),
        ]);
    }

    public function page() : InertiaResponse
    {
        $latest = UsdtDepositOrder::where('user_id', (string) Auth::id())
            ->whereIn('status', [UsdtDepositOrder::STATUS_PENDING, UsdtDepositOrder::STATUS_CONFIRMING, UsdtDepositOrder::STATUS_MANUAL_REVIEW])
            ->latest()
            ->first();

        return Inertia::render('Wallet/UsdtDeposit', [
            'enabled' => UsdtDepositService::isEnabled(),
            'network' => 'TRC20 (TRON)',
            'windowMinutes' => (int) config('payments.usdt_deposits.window_minutes', 30),
            'order' => $latest ? self::present($latest) : null,
        ]);
    }

    public function qr(string $order) : Response
    {
        $png = $this->qr->pngForAddress($this->findOrFail($order)->deposit_address);

        return response($png, 200)->header('Content-Type', 'image/png');
    }

    public function show(string $order) : JsonResponse
    {
        return response()->json(['success' => true, 'data' => self::present($this->findOrFail($order))]);
    }

    public function store(Request $request) : JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:1000000']]);

        try {
            $order = $this->deposits->create($request->user(), (float) $data['amount']);
        } catch (DomainException $e) {
            return $this->error($e);
        }

        return response()->json(['success' => true, 'data' => self::present($order)], 201);
    }

    private function error(DomainException $e) : JsonResponse
    {
        $status = match (true) {
            $e instanceof UsdtDepositException, $e instanceof WalletDomainException => $e->httpStatus(),
            default => 422,
        };

        return response()->json(['success' => false, 'message' => $e->getMessage()], $status);
    }

    private function findOrFail(string $orderId) : UsdtDepositOrder
    {
        $order = $this->deposits->findForUser($orderId, (string) Auth::id());
        abort_if(! $order, 404);

        return $order;
    }
}
