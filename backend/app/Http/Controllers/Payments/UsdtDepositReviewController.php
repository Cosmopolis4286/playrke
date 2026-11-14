<?php

namespace App\Http\Controllers\Payments;

use App\Domains\Payments\Exceptions\UsdtDepositException;
use App\Domains\Payments\Models\UsdtDepositOrder;
use App\Domains\Payments\Models\UsdtInboundTransfer;
use App\Domains\Payments\Services\UsdtDepositService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Tesorería: depósitos USDT que no se acreditaron solos (pago tardío o
 * reportado con txid) y transferencias recibidas sin orden que coincida.
 * Se verifica el txid en un explorador (Tronscan) antes de aprobar.
 */
class UsdtDepositReviewController extends Controller
{
    public function __construct(private readonly UsdtDepositService $deposits)
    {
    }

    public function approve(Request $request, string $order) : JsonResponse
    {
        $data = $request->validate(['amount' => ['nullable', 'numeric', 'min:0.01', 'max:1000000']]);

        try {
            $updated = $this->deposits->approve(
                UsdtDepositOrder::findOrFail($order),
                $request->user(),
                isset($data['amount']) ? (float) $data['amount'] : null,
            );
        } catch (UsdtDepositException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }

        return response()->json(['success' => true, 'message' => 'Depósito acreditado.', 'data' => UsdtDepositController::present($updated)]);
    }

    /** Transferencia sin dueño que tesorería resolvió por fuera (ej. devuelta). */
    public function ignoreTransfer(Request $request, string $transfer) : JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:255']]);

        $affected = UsdtInboundTransfer::whereKey($transfer)
            ->where('status', UsdtInboundTransfer::STATUS_UNMATCHED)
            ->update(['status' => UsdtInboundTransfer::STATUS_IGNORED, 'note' => $data['note']]);

        return $affected > 0
            ? response()->json(['success' => true, 'message' => 'Transferencia archivada.'])
            : response()->json(['success' => false, 'message' => 'La transferencia ya no está pendiente.'], 409);
    }

    public function index() : InertiaResponse
    {
        $orders = UsdtDepositOrder::with('user:id,first_name,last_name,username,email')
            ->where('status', UsdtDepositOrder::STATUS_MANUAL_REVIEW)
            ->oldest()
            ->limit(200)
            ->get()
            ->map(fn (UsdtDepositOrder $order) => UsdtDepositController::present($order) + [
                'review_reason' => $order->review_reason,
                'from_address' => $order->from_address,
                'user' => [
                    'id' => (string) $order->user_id,
                    'name' => trim(($order->user?->first_name ?? '') . ' ' . ($order->user?->last_name ?? '')) ?: $order->user?->username,
                    'email' => $order->user?->email,
                ],
            ]);

        $unmatched = UsdtInboundTransfer::where('status', UsdtInboundTransfer::STATUS_UNMATCHED)
            ->latest('block_time')
            ->limit(200)
            ->get(['id', 'tx_hash', 'from_address', 'amount', 'block_time'])
            ->map(fn (UsdtInboundTransfer $t) => [
                'id' => (string) $t->id,
                'tx_hash' => $t->tx_hash,
                'from_address' => $t->from_address,
                'amount' => $t->amount,
                'block_time' => $t->block_time?->toIso8601String(),
            ]);

        return Inertia::render('Wallet/UsdtDepositReview/Index', [
            'orders' => $orders,
            'unmatched' => $unmatched,
            'receivingAddress' => config('payments.usdt_deposits.receiving_address'),
        ]);
    }

    public function reject(Request $request, string $order) : JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $updated = $this->deposits->reject(UsdtDepositOrder::findOrFail($order), $request->user(), $data['reason']);
        } catch (UsdtDepositException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }

        return response()->json(['success' => true, 'message' => 'Reporte rechazado.', 'data' => UsdtDepositController::present($updated)]);
    }
}
