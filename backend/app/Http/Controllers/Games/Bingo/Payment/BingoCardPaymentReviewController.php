<?php

namespace App\Http\Controllers\Games\Bingo\Payment;

use App\Domains\Games\Bingo\Models\Card\BingoLotRefundFailure;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Services\Payment\BingoCardPaymentReviewService;
use App\Http\Controllers\Controller;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Services\Roles\Dashboard\NetworkDashboardService;
use App\Services\Roles\RoleUserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
    /** Tope de pagos en la cola (se filtra y busca en el cliente). */
    private const MAX_QUEUE = 500;

    public function __construct(
        private readonly BingoCardPaymentReviewService $reviewService,
        private readonly RoleUserService $roleUserService,
    ) {
    }

    public function approve(Request $request, BingoCardPayment $payment) : JsonResponse
    {
        $this->assertCanReview($payment);

        return $this->handleServiceJson(function() use ($request, $payment) {
            $updated = $this->reviewService->approve($payment, $request->input('admin_notes'), $request->boolean('confirm_late'));

            return ['data' => $updated];
        });
    }

    /**
     * Listado de pagos PENDING pendientes de revisión (cola de trabajo),
     * ordenado por urgencia: primero las salas que inician antes (las de
     * inicio manual, sin hora, al final) y dentro de cada una el más viejo.
     *
     * Va completa (hasta MAX_QUEUE) para que la tabla filtre y busque en el
     * cliente: es una cola de trabajo, no un histórico.
     */
    public function index(NetworkDashboardService $network) : Response
    {
        $allowed = $this->reviewableSellerUserIds();

        $pending = fn () => BingoCardPayment::query()
            ->where('bingo_card_payments.payment_status', 'PENDING')
            ->when($allowed !== null, fn ($q) => $q->whereIn('bingo_card_payments.seller_user_id', $allowed));

        $items = $pending()
            ->select('bingo_card_payments.*')
            ->leftJoin('bingo_rooms_config as rc', 'rc.room_id', '=', 'bingo_card_payments.room_id')
            ->orderByRaw('CASE WHEN rc.unix_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('rc.unix_date')
            ->orderBy('bingo_card_payments.created_at')
            ->with([
                'room:id,uuid_code,status_id',
                'room.status:id,code,label',
                'room.config:id,room_id,unix_date',
                'seller:id,username,first_name,last_name',
                'externalPlayer:id,first_name,last_name,phone',
                'cardLot:id,card_count,commerce_id',
                'currency',
            ])
            ->limit(self::MAX_QUEUE)
            ->get();

        // Vendedor → agencia/comercializadora, y regla de cada comercializadora.
        $sellers = Seller::whereIn('user_id', $items->pluck('seller_user_id')->filter()->unique())
            ->with(['subsidiary:id,name,commerce_id', 'subsidiary.commerce:id,user_id,name,pending_payment_policy', 'managerCommerce:id,user_id,name,pending_payment_policy'])
            ->get()
            ->keyBy(fn (Seller $s) => (string) $s->user_id);

        $now = now()->timestamp;

        $payments = $items->map(function(BingoCardPayment $p) use ($sellers, $now) {
            $seller = $sellers->get((string) $p->seller_user_id);
            $commerce = $seller?->resolveCommerce();
            $startsAt = $p->room?->config?->unix_date ? (int) $p->room->config->unix_date : null;

            return [
                'id' => (string) $p->id,
                'room_uuid_code' => $p->room?->uuid_code,
                'room_short_code' => $p->room?->uuid_code ? strtoupper(substr((string) $p->room->uuid_code, 0, 8)) : null,
                'room_status' => $p->room?->status?->code,
                'room_status_label' => $p->room?->status?->label ?? $p->room?->status?->code,
                'room_starts_at' => $startsAt ? Carbon::createFromTimestamp($startsAt)->toIso8601String() : null,
                'room_started' => (bool) $p->room?->hasStarted($now),
                'seller_name' => $seller?->name
                    ?: (trim("{$p->seller?->first_name} {$p->seller?->last_name}") ?: $p->seller?->username),
                'seller_network' => $seller?->subsidiary_id ? $seller->subsidiary?->name : null,
                'commerce_name' => $commerce?->name,
                'pending_policy' => $commerce?->pending_payment_policy ?? Commerce::PENDING_POLICY_KEEP,
                'external_player' => $p->externalPlayer ? [
                    'name' => trim("{$p->externalPlayer->first_name} {$p->externalPlayer->last_name}"),
                    'phone' => $p->externalPlayer->phone,
                ] : null,
                'card_count' => $p->cardLot?->card_count,
                'payment_amount' => (float) $p->payment_amount,
                'currency_code' => $p->currency?->iso_code,
                'amount_label' => $p->currency
                    ? trim(($p->currency->symbol ?? '') . ' ' . $p->currency->formatAmount((float) $p->payment_amount))
                    : number_format((float) $p->payment_amount, 2, ',', '.'),
                'transaction_reference' => $p->transaction_reference,
                // cash → sin comprobante: el revisor confirma que recibió el efectivo.
                'payment_method' => $p->payment_method,
                'payment_method_label' => match ($p->payment_method) {
                    'cash', 'seller_cash' => 'Efectivo',
                    'mobile_payment' => 'Pago móvil',
                    'bank_transfer' => 'Transferencia',
                    default => (string) $p->payment_method,
                },
                // Ruta autenticada (proof()), no URL pública del disco.
                'payment_proof_url' => $p->payment_proof ? route('bingo-card-payments.proof', ['payment' => $p]) : null,
                'created_at' => $p->created_at?->toIso8601String(),
                'pending_at_start_at' => $p->pending_at_start_at?->toIso8601String(),
            ];
        })->values();

        // Reembolsos con fallos — puramente informativo, complementa la
        // notificación puntual que ya recibe superadmin al abandonar (ver
        // RetryFailedBingoRefundsCommand::notifyAbandonedRefund()). No hay
        // acción manual aquí: el comando programado (cada 5 min, ver
        // routes/console.php) sigue siendo el único mecanismo de reintento.
        // Comercializadora: solo los de su red (el reembolso se acredita al vendedor).
        $refundFailures = BingoLotRefundFailure::whereIn('status', ['PENDING', 'ABANDONED'])
            ->when($allowed !== null, fn ($q) => $q->whereIn('user_id', $allowed))
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (BingoLotRefundFailure $f) => [
                'id' => (string) $f->id,
                'room_uuid_code' => $f->room?->uuid_code,
                'amount' => (float) $f->amount,
                'amount_label' => $network->formatAmount($f->currency_id, (float) $f->amount),
                'status' => $f->status,
                'attempts' => $f->attempts,
                'error_message' => $f->error_message,
                'last_attempted_at' => $f->last_attempted_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Roles/Admin/BingoCardPayments/Index', [
            'title' => 'Pagos de Bingo pendientes de revisión',
            'payments' => $payments,
            'refundFailures' => $refundFailures,
            // Regla editable solo por la comercializadora (la suya); admin ve la de cada fila.
            'policy' => $this->ownCommerce()?->pending_payment_policy,
            'summary' => [
                'total' => $total = $pending()->count(),
                // Moneda principal + "N más" (nunca se suman monedas distintas).
                'amount' => $network->currencySummary($pending(), 'payment_amount')['amount'],
                'truncated' => $total > self::MAX_QUEUE,
            ],
        ]);
    }

    /**
     * Imagen del comprobante, servida desde el disco privado solo a quien
     * puede revisar ese pago. no-store: no debe quedar en cachés intermedias.
     */
    public function proof(BingoCardPayment $payment) : SymfonyResponse
    {
        $this->assertCanReview($payment);

        $disk = Storage::disk(BingoCardPayment::PROOF_DISK);

        abort_unless($payment->payment_proof && $disk->exists($payment->payment_proof), 404);

        return $disk->response($payment->payment_proof, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reject(Request $request, BingoCardPayment $payment) : JsonResponse
    {
        $this->assertCanReview($payment);

        return $this->handleServiceJson(function() use ($request, $payment) {
            $updated = $this->reviewService->reject($payment, $request->input('admin_notes'));

            return ['data' => $updated];
        });
    }

    /**
     * Regla de la comercializadora para los pagos que siguen pendientes
     * cuando inicia el sorteo (BingoPendingPaymentStartService):
     * rechazarlos con reembolso o mantenerlos en revisión.
     */
    public function updatePolicy(Request $request) : JsonResponse
    {
        $commerce = $this->ownCommerce();
        abort_unless($commerce, 403);

        $data = $request->validate([
            'policy' => ['required', Rule::in([Commerce::PENDING_POLICY_REJECT, Commerce::PENDING_POLICY_KEEP])],
        ]);

        return $this->handleServiceJson(function() use ($commerce, $data) {
            $commerce->update(['pending_payment_policy' => $data['policy']]);

            return ['policy' => $commerce->pending_payment_policy];
        });
    }

    private function assertCanReview(BingoCardPayment $payment) : void
    {
        $allowed = $this->reviewableSellerUserIds();

        abort_if($allowed !== null && ! in_array((string) $payment->seller_user_id, $allowed, true), 403);
    }

    private function ownCommerce() : ?Commerce
    {
        $userId = (int) Auth::id();

        return $this->roleUserService->getActiveUserRolePrefix($userId) === 'commerce'
            ? Commerce::where('user_id', $userId)->first()
            : null;
    }

    /**
     * user_ids de vendedores cuyos pagos puede revisar el usuario actual, o
     * null si puede revisarlos todos (admin/superadmin).
     *
     * @return string[]|null
     */
    private function reviewableSellerUserIds() : ?array
    {
        $userId = (string) Auth::id();

        return match ($this->roleUserService->getActiveUserRolePrefix((int) $userId)) {
            'superadmin', 'admin' => null,
            'commerce' => Commerce::where('user_id', $userId)->first()?->networkUserIds() ?? [],
            default => [],
        };
    }
}
