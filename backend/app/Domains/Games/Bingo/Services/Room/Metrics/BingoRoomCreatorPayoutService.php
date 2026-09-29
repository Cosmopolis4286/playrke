<?php

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Platform\FinancialReconciliationItem;
use App\Services\Platform\FinancialReconciliationService;
use Illuminate\Support\Facades\Log;

/**
 * Paga al creador de la sala, al cerrarse (FINISHED), lo que le corresponde de
 * las ventas pagadas CON BILLETERA del jugador:
 *  - su comisión de sala (room_commission), y
 *  - en salas Premio/Servicio puras, los fondos del premio físico que él pone
 *    (prize_funding: el neto que no fue a un bote en dinero).
 *
 * Antes nada de esto se acreditaba: el dinero salía de la billetera del
 * jugador y quedaba en el sistema sin dueño. Las ventas fuera del sistema
 * (vendedor con comprobante / comercio en mano) no se pagan acá: ese dinero
 * ya lo tiene el comercio.
 *
 * Se paga al cierre (no en cada venta) para no tener que revertirle nada si la
 * sala se cancela: en ese caso nunca llega a FINISHED y el reembolso devuelve
 * todo al jugador. Idempotente por sala+moneda. Nunca lanza: un fallo queda
 * como alerta de conciliación y no rompe el cierre de la sala.
 */
final class BingoRoomCreatorPayoutService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly FinancialReconciliationService $reconciliation,
    ) {}

    /** @return array<string, float> Monto acreditado por moneda. */
    public function payoutOnClose(BingoRoom $room): array
    {
        $creatorId = $room->creator_user_id ? (string) $room->creator_user_id : null;
        if (!$creatorId) {
            return [];
        }

        $owed = $this->owedByCurrency($room);
        $paid = [];

        foreach ($owed as $currencyId => $amount) {
            $amount = round($amount, 2);
            if ($amount <= 0) continue;

            $key = "bingo-creator-payout-{$room->id}-{$currencyId}";

            try {
                $wallet = $this->wallets->findActiveWallet($creatorId, (string) $currencyId);
                if (!$wallet) {
                    $this->fail($room, (string) $currencyId, $amount, $key, 'El creador no tiene una billetera activa en esta moneda.');
                    continue;
                }

                $this->wallets->recordCreatorCommission(
                    wallet: $wallet,
                    amount: $amount,
                    description: "Comisión de sala – Sala #{$room->uuid_code}",
                    idempotencyKey: $key,
                    metadata: ['room_id' => (string) $room->id],
                );
                $paid[(string) $currencyId] = $amount;
            } catch (\Throwable $e) {
                Log::error('[BingoRoomCreatorPayoutService] No se pudo pagar al creador', [
                    'room_id' => (string) $room->id, 'currency_id' => $currencyId, 'amount' => $amount, 'error' => $e->getMessage(),
                ]);
                $this->fail($room, (string) $currencyId, $amount, $key, $e->getMessage());
            }
        }

        return $paid;
    }

    /**
     * Comisión + fondos del premio de las ventas con billetera aún vigentes
     * (lotes ACTIVE — los reembolsados ya no cuentan), por moneda local.
     *
     * @return array<string, float>
     */
    public function owedByCurrency(BingoRoom $room): array
    {
        $owed = [];

        BingoCardLot::where('room_id', $room->id)
            ->where('status', 'ACTIVE')
            ->whereNotNull('financial_breakdown')
            ->select(['id', 'financial_breakdown'])
            ->chunkById(500, function ($lots) use (&$owed) {
                foreach ($lots as $lot) {
                    $b = $lot->financial_breakdown;
                    if (($b['channel'] ?? null) !== 'wallet' || empty($b['currency_id'])) continue;

                    $local = (array) ($b['room_local'] ?? []);
                    $cur = (string) $b['currency_id'];
                    $owed[$cur] = ($owed[$cur] ?? 0.0)
                        + (float) ($local['room_commission'] ?? 0)
                        + (float) ($local['prize_funding'] ?? 0);
                }
            });

        return $owed;
    }

    private function fail(BingoRoom $room, string $currencyId, float $amount, string $key, string $reason): void
    {
        $this->reconciliation->record(
            type: FinancialReconciliationItem::TYPE_CREATOR_PAYOUT_FAILED,
            message: "No se pudo pagar al creador su comisión de la sala #{$room->uuid_code}: {$reason}",
            amount: $amount,
            currencyId: $currencyId,
            roomId: (string) $room->id,
            idempotencyKey: "{$key}-failed",
            details: ['creator_user_id' => (string) $room->creator_user_id],
        );
    }
}
