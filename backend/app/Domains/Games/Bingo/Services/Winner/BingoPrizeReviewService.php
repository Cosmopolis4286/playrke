<?php

declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Winner;

use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Common\Services\PrizePayoutRetryPolicy;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Premios de Bingo que el pago automático no puede resolver solo y quedaban
 * sin salida (D4):
 *  - 'suspended': ganador sin billetera activa, o comprador externo (no tiene
 *    billetera; el premio se entrega fuera del sistema — D5);
 *  - 'waiting'/'processing' que agotaron PrizePayoutRetryPolicy::MAX_ATTEMPTS
 *    (bingo:retry-failed-prize-payouts deja de intentarlos).
 *
 * Acciones del admin: reintentar el pago a la billetera, registrar la entrega
 * fuera del sistema (con referencia) o anular el premio (con motivo).
 */
final class BingoPrizeReviewService
{
    public const REASON_EXHAUSTED = 'exhausted';
    public const REASON_EXTERNAL = 'external';
    public const REASON_NO_WALLET = 'no_wallet';

    public function __construct(
        private readonly BingoPrizePayoutService $payoutService,
        private readonly BingoRoomAuditLogger $auditLogger,
    ) {
    }

    /**
     * Anula el premio: no se paga. Solo para premios que no pueden estar ya
     * acreditados (no 'processing').
     */
    public function cancel(string $prizeId, string $actorId, string $reason) : BingoCardPrize
    {
        $prize = DB::transaction(function() use ($prizeId, $actorId, $reason) {
            $prize = $this->lockReviewable($prizeId, allowProcessing: false);

            $prize->prize_status = 'cancelled';
            $prize->prize_notes = $this->appendNote($prize->prize_notes, "Anulado por revisión manual: {$reason}");
            $prize->save();

            $this->audit($prize, BingoRoomAuditLogger::EVENT_PRIZE_CANCELLED, 'Premio anulado en revisión manual', [
                'reason' => $reason,
            ], $actorId);

            return $prize;
        });

        return $prize->fresh();
    }

    /**
     * Registra que el premio se entregó fuera del sistema (efectivo,
     * transferencia, pago móvil), típico de compradores externos: queda un
     * pago 'completed' con la referencia y el premio pasa a 'claimed'.
     */
    public function markDelivered(string $prizeId, string $actorId, string $reference, ?string $notes = null) : BingoCardPrize
    {
        $prize = DB::transaction(function() use ($prizeId, $actorId, $reference, $notes) {
            $prize = $this->lockReviewable($prizeId, allowProcessing: false);

            if ($prize->hasCompletedPayment()) {
                throw new DomainException('Este premio ya tiene un pago registrado.');
            }

            $attributes = $prize->buildPaymentAttributes(null, 'completed', $notes);
            BingoCardPrizePayment::create(array_merge($attributes, [
                'payment_method' => 'seller_cash',
                'payment_proof' => 'manual',
                'transaction_reference' => $reference,
                'processed_at' => now(),
            ]));

            $prize->prize_notes = $this->appendNote($prize->prize_notes, "Entregado fuera del sistema (ref. {$reference})" . ($notes ? ": {$notes}" : ''));
            $prize->markAsClaimed();

            $this->audit($prize, BingoRoomAuditLogger::EVENT_PRIZE_PAYMENT_COMPLETED, 'Premio entregado fuera del sistema (registro manual)', [
                'reference' => $reference,
                'manual' => true,
            ], $actorId);

            return $prize;
        });

        return $prize->fresh();
    }

    /**
     * Cola de revisión, del más antiguo al más reciente.
     *
     * @return Collection<int, array{prize: BingoCardPrize, reason: string, attempts: int, last_error: ?string}>
     */
    public function pending(int $limit = 100) : Collection
    {
        $failedCounts = DB::table('bingo_card_prize_payments')
            ->where('payment_status', 'failed')
            ->whereNull('deleted_at')
            ->groupBy('card_prize_id')
            ->selectRaw('card_prize_id, COUNT(*) as attempts')
            ->pluck('attempts', 'card_prize_id')
            ->mapWithKeys(fn ($n, $id) => [(string) $id => (int) $n]);

        $exhaustedIds = $failedCounts->filter(fn (int $n) => $n >= PrizePayoutRetryPolicy::MAX_ATTEMPTS)->keys()->all();

        return BingoCardPrize::query()
            ->with(['room:id,uuid_code', 'currency:id,iso_code,symbol', 'creatorUser:id,first_name,last_name,username', 'externalPlayer:id,first_name,last_name'])
            ->where(function($q) use ($exhaustedIds) {
                $q->where('prize_status', 'suspended');
                if ($exhaustedIds !== []) {
                    $q->orWhere(fn ($w) => $w->whereIn('prize_status', ['waiting', 'processing'])->whereIn('id', $exhaustedIds));
                }
            })
            ->oldest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (BingoCardPrize $prize) => [
                'prize' => $prize,
                'reason' => $this->reasonFor($prize, $failedCounts->get((string) $prize->id, 0)),
                'attempts' => $failedCounts->get((string) $prize->id, 0),
                'last_error' => $prize->payments()->where('payment_status', 'failed')->latest('created_at')->value('payment_notes'),
            ]);
    }

    /**
     * Vuelve a intentar acreditar el premio a la billetera del ganador (p. ej.
     * después de que activó su billetera o se corrigió la falla). No aplica a
     * compradores externos: no tienen billetera.
     */
    public function retry(string $prizeId, string $actorId) : BingoCardPrize
    {
        $prize = DB::transaction(function() use ($prizeId) {
            $prize = $this->lockReviewable($prizeId, allowProcessing: true);

            if ($prize->hasExternalBeneficiary()) {
                throw new DomainException('El ganador es un comprador externo sin billetera: registra la entrega fuera del sistema.');
            }

            if ($prize->isSuspended()) {
                // GamePrizeService::payout() solo paga desde waiting/processing.
                $prize->prize_status = 'waiting';
                $prize->save();
            }

            return $prize;
        });

        // Fuera de la transacción: payout() abre la suya con lockForUpdate e
        // idempotencia propias (y vuelve a 'suspended' si sigue sin billetera).
        $this->payoutService->payout($prize);

        $prize->refresh();
        $this->audit($prize, BingoRoomAuditLogger::EVENT_STATUS_CHANGED, 'Reintento manual del pago del premio', [
            'result' => $prize->prize_status,
        ], $actorId);

        return $prize;
    }

    private function appendNote(?string $current, string $note) : string
    {
        $line = '[' . now()->format('Y-m-d H:i') . '] ' . $note;

        return $current ? $current . "\n" . $line : $line;
    }

    private function audit(BingoCardPrize $prize, string $event, string $description, array $metadata, string $actorId) : void
    {
        $prize->loadMissing('room');
        if (! $prize->room) {
            return;
        }

        $this->auditLogger->log($prize->room, $event, 'admin', $description, $metadata + [
            'card_prize_id' => (string) $prize->id,
        ], $actorId);
    }

    /** Bloquea el premio y verifica que esté en la cola de revisión. */
    private function lockReviewable(string $prizeId, bool $allowProcessing) : BingoCardPrize
    {
        $prize = BingoCardPrize::whereKey($prizeId)->lockForUpdate()->firstOrFail();

        if ($prize->isSuspended()) {
            return $prize;
        }

        if ($prize->isWaiting() || ($allowProcessing && $prize->isProcessing())) {
            $attempts = $prize->payments()->where('payment_status', 'failed')->count();
            if ($attempts >= PrizePayoutRetryPolicy::MAX_ATTEMPTS) {
                return $prize;
            }
        }

        if ($prize->isProcessing()) {
            throw new DomainException('El pago del premio está en curso: puede que ya se haya acreditado. Reintenta el pago para resolverlo.');
        }

        throw new DomainException('Este premio no está pendiente de revisión.');
    }

    private function reasonFor(BingoCardPrize $prize, int $attempts) : string
    {
        if ($prize->isSuspended()) {
            return $prize->hasExternalBeneficiary() ? self::REASON_EXTERNAL : self::REASON_NO_WALLET;
        }

        return self::REASON_EXHAUSTED;
    }
}
