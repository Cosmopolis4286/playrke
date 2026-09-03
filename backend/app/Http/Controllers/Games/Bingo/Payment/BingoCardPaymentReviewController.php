<?php

namespace App\Http\Controllers\Games\Bingo\Payment;

use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revisión admin de pagos PENDING de ExternalPlayer — ver
 * BingoCardPaymentReviewService para el porqué existe este gate (antes se
 * marcaban COMPLETED automáticamente sin revisión de comprobante).
 */
final class BingoCardPaymentReviewController extends Controller
{
    public function __construct(
        private readonly BingoCardPaymentReviewService $reviewService,
    ) {}

    /**
     * Listado de pagos PENDING pendientes de revisión (cola de trabajo admin).
     */
    public function index(): Response
    {
        $paginator = BingoCardPayment::where('payment_status', 'PENDING')
            ->with(['room:id,uuid_code', 'seller:id,username,first_name', 'externalPlayer:id,first_name,last_name,phone', 'cardLot:id,card_count'])
            ->latest('created_at')
            ->paginate(20);

        $payments = collect($paginator->items())->map(fn (BingoCardPayment $p) => [
            'id'                 => (string) $p->id,
            'room_uuid_code'     => $p->room?->uuid_code,
            'seller_name'        => $p->seller?->first_name ?: $p->seller?->username,
            'external_player'    => $p->externalPlayer ? [
                'name'  => trim("{$p->externalPlayer->first_name} {$p->externalPlayer->last_name}"),
                'phone' => $p->externalPlayer->phone,
            ] : null,
            'card_count'         => $p->cardLot?->card_count,
            'payment_amount'     => (float) $p->payment_amount,
            'transaction_reference' => $p->transaction_reference,
            'payment_proof_url'  => $p->payment_proof ? \Illuminate\Support\Facades\Storage::disk('public')->url($p->payment_proof) : null,
            'created_at'         => $p->created_at?->toIso8601String(),
        ])->values();

        // Reembolsos con fallos — puramente informativo, complementa la
        // notificación puntual que ya recibe superadmin al abandonar (ver
        // RetryFailedBingoRefundsCommand::notifyAbandonedRefund()). No hay
        // acción manual aquí: el comando programado (cada 5 min, ver
        // routes/console.php) sigue siendo el único mecanismo de reintento.
        $refundFailures = BingoLotRefundFailure::whereIn('status', ['PENDING', 'ABANDONED'])
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (BingoLotRefundFailure $f) => [
                'id'                => (string) $f->id,
                'room_uuid_code'    => $f->room?->uuid_code,
                'amount'            => (float) $f->amount,
                'status'            => $f->status,
                'attempts'          => $f->attempts,
                'error_message'     => $f->error_message,
                'last_attempted_at' => $f->last_attempted_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Roles/Admin/BingoCardPayments/Index', [
            'title'          => 'Pagos de Bingo pendientes de revisión',
            'payments'       => $payments,
            'refundFailures' => $refundFailures,
            'meta'           => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function approve(Request $request, BingoCardPayment $payment): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request, $payment) {
            $updated = $this->reviewService->approve($payment, $request->input('admin_notes'));

            return ['data' => $updated];
        });
    }

    public function reject(Request $request, BingoCardPayment $payment): JsonResponse
    {
        return $this->handleServiceJson(function () use ($request, $payment) {
            $updated = $this->reviewService->reject($payment, $request->input('admin_notes'));

            return ['data' => $updated];
        });
    }
}
