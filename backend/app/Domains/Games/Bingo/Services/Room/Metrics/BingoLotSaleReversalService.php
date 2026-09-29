<?php

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Platform\FinancialReconciliationItem;
use App\Services\Jackpot\JackpotLedgerService;
use App\Services\Platform\FinancialReconciliationService;
use App\Services\Platform\PlatformReceivableService;
use App\Services\Platform\PlatformWalletService;
use Illuminate\Support\Facades\Log;

/**
 * Revierte lo que la venta de un lote sumó cuando ese lote se reembolsa
 * (sala cancelada — manual o por mínimo no alcanzado — o pago rechazado).
 *
 * Antes el reembolso devolvía el 100% al jugador pero nada más se tocaba:
 *  - la plataforma se quedaba con la comisión RKE ya acreditada,
 *  - el jackpot se quedaba con el aporte,
 *  - la sala seguía contando esa venta en total_sales/total_cards/comisiones y,
 *    peor, en su prize_pool: en un pago rechazado (la sala sigue activa) los
 *    ganadores cobraban un bote inflado con dinero ya devuelto.
 *
 * Revierte exactamente lo registrado en bingo_card_lots.financial_breakdown al
 * vender (ver RoomMetricsService) — no recalcula con los % actuales de la sala,
 * que pueden haber cambiado después. Lotes previos sin desglose: no se pueden
 * revertir con exactitud → se loguea para conciliación manual.
 *
 * Debe llamarse dentro de la transacción del reembolso (BingoCardLotRefundJob),
 * que ya garantiza una sola ejecución por lote (lote ACTIVE → REFUNDED bajo
 * lock); las reversas de wallet/jackpot además son idempotentes por lote.
 */
final class BingoLotSaleReversalService
{
    public function __construct(
        private readonly PlatformWalletService $platformWallet,
        private readonly JackpotLedgerService $jackpotLedger,
        private readonly PlatformReceivableService $receivables,
        private readonly FinancialReconciliationService $reconciliation,
    ) {}

    /** @return bool false si el lote no tiene desglose (lote previo al fix). */
    public function reverse(BingoCardLot $lot, BingoRoom $room): bool
    {
        $b = $lot->financial_breakdown;
        if (!is_array($b) || ($b['version'] ?? null) !== 1) {
            Log::warning('[BingoLotSaleReversalService] Lote sin desglose financiero — no se revierten métricas, RKE ni jackpot; conciliar manualmente', [
                'lot_id' => (string) $lot->id, 'room_id' => (string) $room->id,
            ]);
            $this->reconciliation->record(
                type: FinancialReconciliationItem::TYPE_LEGACY_LOT_NOT_REVERSED,
                message: "Lote reembolsado sin desglose (venta previa al registro): métricas, RKE y jackpot de la sala #{$room->uuid_code} no se revirtieron.",
                amount: (float) $lot->sale_amount,
                currencyId: $lot->currency_id ? (string) $lot->currency_id : null,
                roomId: (string) $room->id,
                lotId: (string) $lot->id,
                idempotencyKey: "legacy-lot-not-reversed-{$lot->id}",
            );
            return false;
        }

        $this->reverseRoomMetrics($room, $b);
        $this->reverseCreatorSale($room, $b);

        $currencyId = !empty($b['currency_id']) ? (string) $b['currency_id'] : null;

        // RKE que la plataforma debe devolver: el acreditado al vender (venta con
        // billetera) o, en venta fuera del sistema, el ya cobrado al comercio. Si
        // la deuda seguía pendiente, basta con anularla.
        $rkeToReverse = (float) ($b['rke_credited_local'] ?? 0);
        if ((float) ($b['rke_receivable_local'] ?? 0) > 0 && !$this->receivables->cancelForLot((string) $lot->id)) {
            $rkeToReverse += (float) $b['rke_receivable_local'];
        }

        if ($currencyId && $rkeToReverse > 0) {
            $movement = $this->platformWallet->reverseRke(
                $currencyId,
                $rkeToReverse,
                "bingo-lot-rke-reversal-{$lot->id}",
                "Reversión comisión RKE – Sala #{$room->uuid_code} (lote reembolsado)",
            );
            if (!$movement) {
                $this->reconciliation->record(
                    type: FinancialReconciliationItem::TYPE_RKE_REVERSAL_FAILED,
                    message: "No se pudo devolver el RKE de un lote reembolsado (sala #{$room->uuid_code}): la wallet de plataforma no tiene saldo suficiente.",
                    amount: $rkeToReverse,
                    currencyId: $currencyId,
                    roomId: (string) $room->id,
                    lotId: (string) $lot->id,
                    idempotencyKey: "rke-reversal-failed-{$lot->id}",
                );
            }
        }

        if (!empty($b['jackpot_type_id']) && (float) ($b['jackpot_credited_usd'] ?? 0) > 0) {
            $shortfall = $this->jackpotLedger->reverseContribution(
                jackpotTypeId: (string) $b['jackpot_type_id'],
                amount: (float) $b['jackpot_credited_usd'],
                idempotencyKey: "bingo-lot-jackpot-reversal-{$lot->id}",
                roomId: (string) $room->id,
                description: "Reversión aporte jackpot – Sala #{$room->uuid_code} (lote reembolsado)",
            );
            if ($shortfall > 0) {
                $this->reconciliation->record(
                    type: FinancialReconciliationItem::TYPE_JACKPOT_REVERSAL_SHORTFALL,
                    message: "El pozo del jackpot no alcanzaba para devolver el aporte de un lote reembolsado (sala #{$room->uuid_code}). Faltante en USD.",
                    amount: $shortfall,
                    roomId: (string) $room->id,
                    lotId: (string) $lot->id,
                    idempotencyKey: "jackpot-reversal-shortfall-{$lot->id}",
                    details: ['jackpot_type_id' => (string) $b['jackpot_type_id'], 'currency' => 'USD'],
                );
            }
        }

        return true;
    }

    /**
     * Resta a la sala cada monto del desglose. Genérico por clave: cualquier
     * columna nueva que RoomMetricsService agregue al desglose (ej. comisiones
     * de agencia/comercializadora) se revierte sin tocar este servicio.
     * prize_pool también se descuenta de full_house_prize (se suman juntos).
     */
    private function reverseRoomMetrics(BingoRoom $room, array $b): void
    {
        $locked = BingoRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();

        $update = $this->subtractAll($locked->getAttributes(), (array) ($b['room_usd'] ?? []));

        $currencyId = $b['currency_id'] ?? null;
        $local = $b['room_local'] ?? null;
        if ($currencyId && is_array($local)) {
            $metrics = (array) ($locked->metrics_by_currency ?? []);
            $metrics[$currencyId] = array_merge(
                (array) ($metrics[$currencyId] ?? []),
                $this->subtractAll((array) ($metrics[$currencyId] ?? []), $local),
            );
            $update['metrics_by_currency'] = $metrics;
        }

        $locked->update($update);
        $room->fill($locked->only(array_keys($update)));
    }

    /**
     * @param array<string, mixed> $current Valores actuales (fila o entrada JSON)
     * @param array<string, mixed> $amounts Montos a restar
     * @return array<string, int|float> Solo las claves afectadas, sin bajar de 0
     */
    private function subtractAll(array $current, array $amounts): array
    {
        $out = [];
        foreach ($amounts as $key => $amount) {
            if (!is_numeric($amount) || (float) $amount == 0.0) continue;
            $out[$key] = $key === 'total_cards'
                ? max(0, (int) ($current[$key] ?? 0) - (int) $amount)
                : round(max(0, (float) ($current[$key] ?? 0) - (float) $amount), 3);
        }
        if (isset($amounts['prize_pool']) && (float) $amounts['prize_pool'] != 0.0) {
            $out['full_house_prize'] = round(max(0, (float) ($current['full_house_prize'] ?? 0) - (float) $amounts['prize_pool']), 3);
        }
        return $out;
    }

    private function reverseCreatorSale(BingoRoom $room, array $b): void
    {
        $cs = $b['creator_sale'] ?? null;
        $currencyId = $b['currency_id'] ?? null;
        if (!is_array($cs) || !$currencyId) {
            return;
        }

        $sale = BingoCreatorSale::where('room_id', $room->id)
            ->where('creator_user_id', $cs['creator_user_id'])
            ->where('currency_id', $currencyId)
            ->lockForUpdate()
            ->first();
        if (!$sale) {
            return;
        }

        // Genérico por clave (ver reverseRoomMetrics): creator_user_id no es monto.
        unset($cs['creator_user_id']);
        $sale->update($this->subtractAll($sale->getAttributes(), $cs));
    }
}
