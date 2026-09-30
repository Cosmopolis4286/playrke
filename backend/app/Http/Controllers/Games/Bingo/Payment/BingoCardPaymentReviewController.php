<?php

namespace App\Http\Controllers\Games\Bingo\Payment;

use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use App\Http\Controllers\Controller;
use App\Models\Roles\Commerce;
use App\Services\Roles\RoleUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Revisión de pagos PENDING de ExternalPlayer — ver
 * BingoCardPaymentReviewService para el porqué existe este gate.
 *
 * Quién revisa qué (reviewableSellerUserIds()):
 * - admin/superadmin: todos los pagos.
 * - commerce: solo los de SU red comercial (Commerce::networkUserIds()) —
 *   el dinero de esas ventas es suyo.
 * - cualquier otro rol (seller, subsidiary, customer…): ninguno.
 *
 * Antes la cola listaba TODOS los pagos de la plataforma y approve/reject no
 * verificaban nada más que el permiso de ruta (games.bingo, que tiene el
 * seller): un vendedor podía aprobar sus propias ventas y saltarse la
 * revisión, y cualquier comercio aprobar/rechazar pagos de otros.
 */
final class BingoCardPaymentReviewController extends Controller
{
    public function __construct(
        private readonly BingoCardPaymentReviewService $reviewService,
        private readonly RoleUserService $roleUserService,
    ) {}

    /**
     * user_ids de vendedores cuyos pagos puede revisar el usuario actual, o
     * null si puede revisarlos todos (admin/superadmin).
     *
     * @return string[]|null
     */
    private function reviewableSellerUserIds(): ?array
    {
        $userId = (string) Auth::id();

        return match ($this->roleUserService->getActiveUserRolePrefix((int) $userId)) {
            'superadmin', 'admin' => null,
            'commerce'            => Commerce::where('user_id', $userId)->first()?->networkUserIds() ?? [],
            default               => [],
        };
    }

    private function assertCanReview(BingoCardPayment $payment): void
    {
        $allowed = $this->reviewableSellerUserIds();

        abort_if($allowed !== null && !in_array((string) $payment->seller_user_id, $allowed, true), 403);
    }

    /**
     * Listado de pagos PENDING pendientes de revisión (cola de trabajo).
     */
    public function index(): Response
    {
        $allowed = $this->reviewableSellerUserIds();

        $paginator = BingoCardPayment::where('payment_status', 'PENDING')
            ->when($allowed !== null, fn ($q) => $q->whereIn('seller_user_id', $allowed))
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
            // cash → sin comprobante: el revisor confirma que recibió el efectivo.
            'payment_method'     => $p->payment_method,
            'payment_method_label' => match ($p->payment_method) {
                'cash', 'seller_cash' => 'Efectivo',
                'mobile_payment'      => 'Pago móvil',
                'bank_transfer'       => 'Transferencia',
                default               => (string) $p->payment_method,
            },
            // Ruta autenticada (proof()), no URL pública del disco.
            'payment_proof_url'  => $p->payment_proof ? route('bingo-card-payments.proof', ['payment' => $p]) : null,
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

    /**
     * Imagen del comprobante, servida desde el disco privado solo a quien
     * puede revisar ese pago. no-store: no debe quedar en cachés intermedias.
     */
    public function proof(BingoCardPayment $payment): SymfonyResponse
    {
        $this->assertCanReview($payment);

        $disk = Storage::disk(BingoCardPayment::PROOF_DISK);

        abort_unless($payment->payment_proof && $disk->exists($payment->payment_proof), 404);

        return $disk->response($payment->payment_proof, null, [
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, BingoCardPayment $payment): JsonResponse
    {
        $this->assertCanReview($payment);

        return $this->handleServiceJson(function () use ($request, $payment) {
            $updated = $this->reviewService->approve($payment, $request->input('admin_notes'));

            return ['data' => $updated];
        });
    }

    public function reject(Request $request, BingoCardPayment $payment): JsonResponse
    {
        $this->assertCanReview($payment);

        return $this->handleServiceJson(function () use ($request, $payment) {
            $updated = $this->reviewService->reject($payment, $request->input('admin_notes'));

            return ['data' => $updated];
        });
    }
}
