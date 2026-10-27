<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pagos de cartón (comprador externo, vía vendedor) que siguen PENDIENTES
 * cerca del inicio del sorteo o cuando ya inició. Mientras están pendientes
 * sus cartones no se ven ni pueden ganar (BingoGameService /
 * BingoWinnerDetectionService), pero el comprador ya pagó.
 *
 *  1. Aviso previo: al cerrar la venta del vendedor (alertMinutes() antes
 *     del inicio, ya no entran pagos nuevos) se avisa a la comercializadora
 *     que revisa (una vez por pago).
 *  2. Al iniciar: cada pago pendiente se marca (pending_at_start_at) y se
 *     aplica la regla de la comercializadora de la red del vendedor
 *     (commerces.pending_payment_policy):
 *       - reject_refund: se rechaza con reembolso (mismo camino que el
 *         rechazo manual: anula los cartones y el vendedor devuelve el dinero);
 *       - keep_review:  sigue en la cola, marcado "pendiente tras el inicio";
 *         aprobarlo exige confirmar que el comprador ya perdió bolas.
 *
 * Idempotente: cada paso solo toca pagos sin su marca. Lo ejecuta
 * HandlePendingBingoPaymentsAtStartCommand cada minuto.
 */
final class BingoPendingPaymentStartService
{
    public const AUTO_REJECT_NOTE = 'Rechazado automáticamente: el sorteo inició sin que se aprobara el pago.';
    /** Solo se avisa del inicio si la sala inició hace menos de esto. */
    private const NOTIFY_STARTED_WITHIN_SECONDS = 3600;

    public function __construct(
        private readonly BingoCardPaymentReviewService $review,
        private readonly NotificationService $notifications,
    ) {
    }

    /** Mismo corte que la venta del vendedor (BingoCardGenerateService::sellerSalesCutoffMinutes). */
    public function alertMinutes() : int
    {
        return max(1, (int) config('app.bingo_seller_sales_cutoff_minutes', 5));
    }

    /** Aviso previo a la comercializadora: su sala está por iniciar con pagos sin revisar. */
    public function alertUpcoming(int $now) : int
    {
        $payments = $this->pending()
            ->whereNull('start_alert_sent_at')
            ->whereNull('pending_at_start_at')
            ->whereHas('room', fn (Builder $r) => $r
                ->whereHas('status', fn ($s) => $s->where('code', 'ACTIVE'))
                ->whereHas('config', fn ($c) => $c->whereBetween('unix_date', [$now, $now + $this->alertMinutes() * 60])))
            ->with(['room:id,uuid_code', 'room.config:id,room_id,unix_date'])
            ->get();

        foreach ($this->groupByReviewer($payments) as $reviewerUserId => $byRoom) {
            foreach ($byRoom as $roomPayments) {
                $room = $roomPayments->first()->room;
                $minutes = max(1, (int) ceil(((int) $room->config->unix_date - $now) / 60));
                $this->notify(
                    $reviewerUserId,
                    'Pagos por revisar antes del sorteo',
                    "La sala #{$room->uuid_code} inicia en {$minutes} min y tiene {$roomPayments->count()} pago(s) sin revisar. "
                    . 'Si no los apruebas, esos compradores no podrán jugar.',
                );
            }
        }

        if ($payments->isNotEmpty()) {
            BingoCardPayment::whereIn('id', $payments->pluck('id'))->update(['start_alert_sent_at' => now()]);
        }

        return $payments->count();
    }

    /** @return array{rejected: int, kept: int, failed: int} */
    public function handleStarted(int $now) : array
    {
        // Iniciada = ya en juego/terminada, o su hora programada ya pasó.
        $payments = $this->pending()
            ->whereNull('pending_at_start_at')
            ->whereHas('room', fn (Builder $r) => $r->where(fn ($q) => $q
                ->whereHas('status', fn ($s) => $s->whereIn('code', ['IN_PROGRESS', 'FINISHED']))
                ->orWhereHas('config', fn ($c) => $c->whereNotNull('unix_date')->where('unix_date', '<=', $now))))
            ->with(['room:id,uuid_code', 'room.config:id,room_id,unix_date'])
            ->get();

        $result = ['rejected' => 0, 'kept' => 0, 'failed' => 0];

        foreach ($this->groupByReviewer($payments) as $reviewerUserId => $byRoom) {
            $policy = Commerce::where('user_id', $reviewerUserId)->value('pending_payment_policy') ?? Commerce::PENDING_POLICY_KEEP;

            foreach ($byRoom as $roomPayments) {
                $rejected = $kept = $failed = 0;

                foreach ($roomPayments as $payment) {
                    BingoCardPayment::whereKey($payment->id)->update(['pending_at_start_at' => now()]);

                    if ($policy !== Commerce::PENDING_POLICY_REJECT) {
                        $kept++;

                        continue;
                    }

                    try {
                        $this->review->reject($payment->fresh(), self::AUTO_REJECT_NOTE);
                        $rejected++;
                    } catch (Throwable $e) {
                        // Ej. el lote ya tiene un premio: queda en revisión para resolver a mano.
                        $failed++;
                        Log::warning('[BingoPendingPaymentStart] No se pudo rechazar automáticamente', [
                            'payment_id' => (string) $payment->id, 'error' => $e->getMessage(),
                        ]);
                    }
                }

                $result['rejected'] += $rejected;
                $result['kept'] += $kept;
                $result['failed'] += $failed;

                // Salas que iniciaron hace rato (ej. pagos viejos al desplegar
                // esto): se marcan y se aplica la regla, pero sin avisar.
                $room = $roomPayments->first()->room;
                $unixDate = $room?->config?->unix_date;
                if ($unixDate === null || (int) $unixDate >= $now - self::NOTIFY_STARTED_WITHIN_SECONDS) {
                    $this->notify($reviewerUserId, 'Sorteo iniciado con pagos sin revisar', $this->startedMessage($room?->uuid_code, $rejected, $kept, $failed));
                }
            }
        }

        return $result;
    }

    /** @return array{alerted: int, rejected: int, kept: int, failed: int} */
    public function run(?int $now = null) : array
    {
        $now ??= now()->timestamp;

        return ['alerted' => $this->alertUpcoming($now)] + $this->handleStarted($now);
    }

    /**
     * Agrupa por comercializadora que revisa (la de la red del vendedor) y sala.
     *
     * @param Collection<int, BingoCardPayment> $payments
     *
     * @return array<string, Collection<string, Collection<int, BingoCardPayment>>>
     */
    private function groupByReviewer(Collection $payments) : array
    {
        $grouped = [];

        foreach ($payments as $payment) {
            $reviewer = $this->reviewerUserId($payment);
            if ($reviewer === null) {
                continue;
            }
            $grouped[$reviewer][(string) $payment->room_id][] = $payment;
        }

        return array_map(
            fn (array $byRoom) => collect($byRoom)->map(fn (array $list) => collect($list)),
            $grouped,
        );
    }

    private function notify(string|int $userId, string $title, string $message) : void
    {
        $userId = (string) $userId;

        try {
            $this->notifications->create($userId, 'GAME_ALERT', $title, $message, ['url' => '/bingo/card-payments']);
        } catch (Throwable $e) {
            Log::warning('[BingoPendingPaymentStart] No se pudo notificar', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function pending() : Builder
    {
        return BingoCardPayment::query()
            ->where('payment_status', 'PENDING')
            ->whereNotNull('card_lot_id');
    }

    /** user_id de la comercializadora de la red del vendedor (snapshot del lote, o la actual). */
    private function reviewerUserId(BingoCardPayment $payment) : ?string
    {
        $commerceId = BingoCardLot::whereKey($payment->card_lot_id)->value('commerce_id');
        if ($commerceId) {
            $userId = Commerce::whereKey($commerceId)->value('user_id');
            if ($userId) {
                return (string) $userId;
            }
        }

        $commerce = Seller::where('user_id', $payment->seller_user_id)->first()?->resolveCommerce();

        return $commerce?->user_id ? (string) $commerce->user_id : null;
    }

    private function startedMessage(?string $roomCode, int $rejected, int $kept, int $failed) : string
    {
        $parts = [];
        if ($rejected) {
            $parts[] = "{$rejected} rechazado(s) con reembolso — el vendedor debe devolver el dinero";
        }
        if ($kept) {
            $parts[] = "{$kept} siguen en revisión — si los apruebas, el comprador ya perdió las bolas cantadas";
        }
        if ($failed) {
            $parts[] = "{$failed} no se pudieron rechazar (tienen premio) y quedan en revisión";
        }

        return "La sala #{$roomCode} inició con pagos sin revisar: " . implode('; ', $parts) . '.';
    }
}
