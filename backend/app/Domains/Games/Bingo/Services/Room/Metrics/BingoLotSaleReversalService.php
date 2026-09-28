<?php

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Services\Jackpot\JackpotLedgerService;
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
    ) {}

    /** @return bool false si el lote no tiene desglose (lote previo al fix). */
    public function reverse(BingoCardLot $lot, BingoRoom $room): bool
    {
        $b = $lot->financial_breakdown;
        if (!is_array($b) || ($b['version'] ?? null) !== 1) {
            Log::warning('[BingoLotSaleReversalService] Lote sin desglose financiero — no se revierten métricas, RKE ni jackpot; conciliar manualmente', [
                'lot_id' => (string) $lot->id, 'room_id' => (string) $room->id,
            ]);
            return false;
        }

        $this->reverseRoomMetrics($room, $b);
        $this->reverseCreatorSale($room, $b);

        $currencyId = $b['currency_id'] ?? null;
        if ($currencyId && (float) ($b['rke_credited_local'] ?? 0) > 0) {
            $this->platformWallet->reverseRke(
                (string) $currencyId,
                (float) $b['rke_credited_local'],
                "bingo-lot-rke-reversal-{$lot->id}",
                "Reversión comisión RKE – Sala #{$room->uuid_code} (lote reembolsado)",
            );
        }

        if (!empty($b['jackpot_type_id']) && (float) ($b['jackpot_credited_usd'] ?? 0) > 0) {
            $this->jackpotLedger->reverseContribution(
                jackpotTypeId: (string) $b['jackpot_type_id'],
                amount: (float) $b['jackpot_credited_usd'],
                idempotencyKey: "bingo-lot-jackpot-reversal-{$lot->id}",
                roomId: (string) $room->id,
                description: "Reversión aporte jackpot – Sala #{$room->uuid_code} (lote reembolsado)",
            );
        }

        return true;
    }

    private function reverseRoomMetrics(BingoRoom $room, array $b): void
    {
        $locked = BingoRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
        $sub = fn ($current, $amount) => round(max(0, (float) ($current ?? 0) - (float) ($amount ?? 0)), 3);

        $usd = $b['room_usd'] ?? [];
        $update = [
            'total_sales'       => $sub($locked->total_sales, $usd['total_sales'] ?? 0),
            'total_cards'       => max(0, (int) ($locked->total_cards ?? 0) - (int) ($usd['total_cards'] ?? 0)),
            'rke_commission'    => $sub($locked->rke_commission, $usd['rke_commission'] ?? 0),
            'room_commission'   => $sub($locked->room_commission, $usd['room_commission'] ?? 0),
            'seller_commission' => $sub($locked->seller_commission, $usd['seller_commission'] ?? 0),
            'prize_pool'        => $sub($locked->prize_pool, $usd['prize_pool'] ?? 0),
            'full_house_prize'  => $sub($locked->full_house_prize, $usd['prize_pool'] ?? 0),
        ];

        $currencyId = $b['currency_id'] ?? null;
        $local = $b['room_local'] ?? null;
        if ($currencyId && is_array($local)) {
            $metrics = (array) ($locked->metrics_by_currency ?? []);
            $current = (array) ($metrics[$currencyId] ?? []);
            foreach (['total_sales', 'prize_pool', 'prize_funding', 'rke_commission', 'room_commission', 'seller_commission', 'jackpot_contribution'] as $key) {
                if (array_key_exists($key, $current) || (float) ($local[$key] ?? 0) > 0) {
                    $current[$key] = $sub($current[$key] ?? 0, $local[$key] ?? 0);
                }
            }
            $current['full_house_prize'] = $sub($current['full_house_prize'] ?? 0, $local['prize_pool'] ?? 0);
            $current['total_cards'] = max(0, (int) ($current['total_cards'] ?? 0) - (int) ($local['total_cards'] ?? 0));
            $metrics[$currencyId] = $current;
            $update['metrics_by_currency'] = $metrics;
        }

        $locked->update($update);
        $room->fill($locked->only(array_keys($update)));
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

        $sub = fn ($current, $amount) => round(max(0, (float) ($current ?? 0) - (float) ($amount ?? 0)), 3);
        $sale->update([
            'sale_amount'          => $sub($sale->sale_amount, $cs['sale_amount']),
            'rke_commission'       => $sub($sale->rke_commission, $cs['rke_commission']),
            'room_commission'      => $sub($sale->room_commission, $cs['room_commission']),
            'seller_commission'    => $sub($sale->seller_commission, $cs['seller_commission']),
            'jackpot_contribution' => $sub($sale->jackpot_contribution, $cs['jackpot_contribution']),
            'prize_pool_amount'    => $sub($sale->prize_pool_amount, $cs['prize_pool_amount']),
        ]);
    }
}
