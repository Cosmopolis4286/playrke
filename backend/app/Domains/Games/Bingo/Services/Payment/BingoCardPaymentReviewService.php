<?php
declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use Illuminate\Support\Facades\DB;

/**
 * Revisión admin de pagos de cartones de ExternalPlayer (comprador sin
 * cuenta, vía vendedor). Antes de este servicio, BingoCardGenerationService::
 * createCardPayment() marcaba estos pagos COMPLETED automáticamente sin que
 * nadie revisara el comprobante — el vendedor "decía que cobró" y el sistema
 * lo daba por bueno. Ahora nacen PENDING (ver ese método) y quedan ocultos
 * de "mis cartones" (BingoGameService::getUserCartonsForExternalPlayer())
 * hasta que un admin los apruebe o rechace aquí.
 *
 * Mismo patrón que WalletService::approveDeposit()/rejectDeposit().
 */
final class BingoCardPaymentReviewService
{
    /**
     * Aprueba un pago PENDING: los cartones de su lote pasan a ser visibles
     * y jugables de inmediato (no requiere ninguna acción adicional — ya
     * estaban generados, solo estaban ocultos por el filtro de pago pendiente).
     *
     * lockForUpdate() serializa contra reject() concurrente sobre el mismo
     * pago (ej. doble clic, o dos admins procesando la misma cola a la vez)
     * — sin el lock, ambas rutas podían leer 'PENDING' antes de que ninguna
     * comiteara y terminar en un estado inconsistente (pago COMPLETED pero
     * cartones ya CANCELLED por el reject "perdedor", o el reembolso ya
     * despachado pese a que approve() ganó al final).
     */
    public function approve(BingoCardPayment $payment, ?string $adminNotes = null): BingoCardPayment
    {
        return DB::transaction(function () use ($payment, $adminNotes) {
            $locked = BingoCardPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$locked || $locked->payment_status !== 'PENDING') {
                throw new \DomainException('Solo se pueden aprobar pagos en estado PENDING.');
            }

            $locked->update([
                'payment_status' => 'APPROVED',
                'processed_at'   => now(),
                'description'    => trim(($locked->description ?? '') . ' — Aprobado por admin' . ($adminNotes ? ": {$adminNotes}" : '')),
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Rechaza un pago PENDING: el vendedor ya cobró en efectivo fuera de
     * banda antes de esta revisión (por eso los cartones ya existen), así
     * que rechazar no es "cancelar una compra que nunca ocurrió" — es
     * "el comprobante no es válido, hay que reembolsar y anular los
     * cartones". Cancela el lote/cartones y dispara el mismo
     * BingoCardLotRefundJob que ya usa la cancelación de sala completa
     * (acredita al vendedor — quien debe devolver el dinero en efectivo al
     * comprador real, ver ese job para el porqué no puede ir directo al
     * ExternalPlayer, que no tiene wallet propia).
     *
     * Antes de cancelar+reembolsar, verifica que ningún cartón del lote
     * tenga ya un premio en curso, pagado o en disputa
     * (waiting/processing/claimed/disputed) —
     * sin este check, un admin que revisa la cola tarde (después de que el
     * sorteo ya corrió y pagó un premio de ese lote) dispararía un
     * reembolso del sale_amount COMPLETO del lote además del premio ya
     * acreditado: el jugador cobra el premio Y el vendedor recibe de vuelta
     * el 100% de lo pagado por el lote entero, incluido el cartón ganador.
     * En ese caso el rechazo queda bloqueado — requiere resolución manual
     * (contactar al vendedor/comprador) en vez de automatizarse.
     */
    public function reject(BingoCardPayment $payment, ?string $adminNotes = null): BingoCardPayment
    {
        $updated = DB::transaction(function () use ($payment, $adminNotes) {
            $locked = BingoCardPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$locked || $locked->payment_status !== 'PENDING') {
                throw new \DomainException('Solo se pueden rechazar pagos en estado PENDING.');
            }

            if (!$locked->card_lot_id) {
                throw new \DomainException('Este pago no tiene un lote de cartones asociado.');
            }

            $hasActivePrize = BingoCardPrize::where('card_lot_id', $locked->card_lot_id)
                ->whereIn('prize_status', ['waiting', 'processing', 'claimed', 'disputed'])
                ->exists();

            if ($hasActivePrize) {
                throw new \DomainException(
                    'Este lote ya tiene un premio en curso o pagado — no se puede rechazar automáticamente. '
                    . 'Resuelve manualmente el reembolso para no duplicar el pago del premio.'
                );
            }

            $locked->update([
                'payment_status' => 'REJECTED',
                'processed_at'   => now(),
                'description'    => trim(($locked->description ?? '') . ' — Rechazado por admin' . ($adminNotes ? ": {$adminNotes}" : '')),
            ]);

            BingoCard::where('card_lot_id', $locked->card_lot_id)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->update(['status' => BingoCard::STATUS_CANCELLED]);

            return $locked->fresh();
        });

        BingoCardLotRefundJob::dispatch((string) $updated->card_lot_id, (string) $updated->room_id)
            ->onQueue('bingo-refunds');

        return $updated;
    }
}
