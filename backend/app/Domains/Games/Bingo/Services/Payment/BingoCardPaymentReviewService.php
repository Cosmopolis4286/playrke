<?php
declare(strict_types=1);

namespace App\Domains\Games\Bingo\Services\Payment;

use App\Domains\Games\Bingo\Jobs\BingoCardLotRefundJob;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use Illuminate\Support\Facades\Auth;
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
     */
    public function approve(BingoCardPayment $payment, ?string $adminNotes = null): BingoCardPayment
    {
        if ($payment->payment_status !== 'PENDING') {
            throw new \DomainException('Solo se pueden aprobar pagos en estado PENDING.');
        }

        $payment->update([
            'payment_status' => 'COMPLETED',
            'processed_at'   => now(),
            'description'    => trim(($payment->description ?? '') . ' — Aprobado por admin' . ($adminNotes ? ": {$adminNotes}" : '')),
        ]);

        return $payment->fresh();
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
     */
    public function reject(BingoCardPayment $payment, ?string $adminNotes = null): BingoCardPayment
    {
        if ($payment->payment_status !== 'PENDING') {
            throw new \DomainException('Solo se pueden rechazar pagos en estado PENDING.');
        }

        $updated = DB::transaction(function () use ($payment, $adminNotes) {
            $payment->update([
                'payment_status' => 'FAILED',
                'processed_at'   => now(),
                'description'    => trim(($payment->description ?? '') . ' — Rechazado por admin' . ($adminNotes ? ": {$adminNotes}" : '')),
            ]);

            BingoCard::where('card_lot_id', $payment->card_lot_id)
                ->where('status', BingoCard::STATUS_ACTIVE)
                ->update(['status' => BingoCard::STATUS_CANCELLED]);

            return $payment->fresh();
        });

        if ($payment->card_lot_id) {
            BingoCardLotRefundJob::dispatch((string) $payment->card_lot_id, (string) $payment->room_id)
                ->onQueue('bingo-refunds');
        }

        return $updated;
    }
}
