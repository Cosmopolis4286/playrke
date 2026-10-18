<?php

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Platform\FinancialReconciliationItem;
use App\Models\Platform\PlatformReceivable;
use App\Models\User;
use App\Services\Platform\FinancialReconciliationService;
use App\Services\Platform\PlatformWalletService;
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
 *
 * Venta cruzada (vendedor de OTRA red en una sala PÚBLICA, cobrada fuera del
 * sistema): la comisión del creador la retuvo el comercio vendedor y quedó en
 * su deuda (PlatformReceivable.creator_amount). Se le paga al creador cuando
 * la deuda está cobrada Y la sala cerró, lo que ocurra último:
 * payoutOnClose() paga las ya cobradas y PlatformReceivableService::settle()
 * paga las de salas ya cerradas (payCustodiedShares). Idempotente por deuda.
 *
 * Salas de la plataforma (creadas por admin/superadmin): no hay un creador-
 * usuario; lo suyo va a la billetera de plataforma.
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

        $paid = [];

        foreach ($this->owedByCurrency($room) as $currencyId => $amount) {
            $amount = round($amount, 2);
            if ($amount <= 0) continue;

            $key = "bingo-creator-payout-{$room->id}-{$currencyId}";
            if ($this->payTo($room, $creatorId, (string) $currencyId, $amount, $key, "Comisión de sala – Sala #{$room->uuid_code}")) {
                $paid[(string) $currencyId] = $amount;
            }
        }

        // Ventas cruzadas ya cobradas a la red vendedora: la sala acaba de cerrar.
        foreach ($this->payCustodiedShares($room) as $currencyId => $amount) {
            $paid[$currencyId] = round(($paid[$currencyId] ?? 0) + $amount, 2);
        }

        return $paid;
    }

    /**
     * Paga al creador las comisiones de venta cruzada custodiadas en deudas YA
     * COBRADAS de esta sala, si la sala cerró (FINISHED). Marca cada deuda con
     * creator_paid_at; idempotente por deuda (no repaga si se reintenta).
     *
     * @return array<string, float> Monto pagado por moneda.
     */
    public function payCustodiedShares(BingoRoom $room): array
    {
        $room->loadMissing('status');
        if ($room->status?->code !== 'FINISHED') {
            return [];
        }

        $paid = [];

        PlatformReceivable::where('room_id', $room->id)
            ->where('status', PlatformReceivable::STATUS_SETTLED)
            ->where('creator_amount', '>', 0)
            ->whereNull('creator_paid_at')
            ->get(['id', 'currency_id', 'creator_amount', 'creator_payee_user_id'])
            ->each(function (PlatformReceivable $r) use ($room, &$paid) {
                $payee  = $r->creator_payee_user_id ? (string) $r->creator_payee_user_id : (string) $room->creator_user_id;
                $amount = round((float) $r->creator_amount, 2);
                $key    = "bingo-creator-custody-{$r->id}";

                if ($this->payTo($room, $payee, (string) $r->currency_id, $amount, $key, "Comisión de sala (venta cruzada) – Sala #{$room->uuid_code}")) {
                    $r->forceFill(['creator_paid_at' => now()])->save();
                    $paid[(string) $r->currency_id] = round(($paid[(string) $r->currency_id] ?? 0) + $amount, 2);
                }
            });

        return $paid;
    }

    /**
     * Acredita $amount al creador: a su billetera, o a la de plataforma si la
     * sala es de la plataforma (creador admin/superadmin). Nunca lanza: un
     * fallo queda como alerta de conciliación. true si se acreditó.
     */
    private function payTo(BingoRoom $room, string $payeeUserId, string $currencyId, float $amount, string $key, string $description): bool
    {
        try {
            if ($this->isPlatformUser($payeeUserId)) {
                $movement = app(PlatformWalletService::class)->creditCreatorShare($currencyId, $amount, $key, $description);
                if (!$movement) {
                    $this->fail($room, $currencyId, $amount, $key, 'No hay billetera de plataforma en esta moneda.');
                    return false;
                }

                return true;
            }

            $wallet = $this->wallets->findActiveWallet($payeeUserId, $currencyId);
            if (!$wallet) {
                $this->fail($room, $currencyId, $amount, $key, 'El creador no tiene una billetera activa en esta moneda.');
                return false;
            }

            $this->wallets->recordCreatorCommission(
                wallet: $wallet,
                amount: $amount,
                description: $description,
                idempotencyKey: $key,
                metadata: ['room_id' => (string) $room->id],
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('[BingoRoomCreatorPayoutService] No se pudo pagar al creador', [
                'room_id' => (string) $room->id, 'currency_id' => $currencyId, 'amount' => $amount, 'error' => $e->getMessage(),
            ]);
            $this->fail($room, $currencyId, $amount, $key, $e->getMessage());

            return false;
        }
    }

    /** Sala de la plataforma: su creador es un admin/superadmin. */
    private function isPlatformUser(string $userId): bool
    {
        return (bool) User::find($userId)?->hasRole(['admin', 'superadmin']);
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
