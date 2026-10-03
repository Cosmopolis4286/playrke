<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformReceivable;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cuentas por cobrar de la plataforma: lo que un comercio retuvo en ventas
 * fuera del sistema (vendedor con comprobante → jugador externo).
 *
 * En esas ventas el dinero nunca pasa por una wallet: lo cobra la red del
 * comercio en efectivo/transferencia. La red se queda con sus comisiones
 * (vendedor, agencia, comercializadora) y la del creador de la sala; el resto
 * es de la plataforma, que es quien:
 *  - cobra la comisión RKE,
 *  - acumula el jackpot,
 *  - paga los premios del bote a billeteras (incluido el del ganador externo,
 *    que se acredita al comercio para que se lo entregue).
 *
 * Flujo:
 *  - al vender se registra la deuda desglosada (record) — no se acredita nada;
 *  - si el lote se reembolsa y la deuda seguía pendiente, se anula (cancelForLot);
 *  - cuando el comercio paga, un superadmin la marca cobrada (settle) y recién
 *    ahí entra a la wallet de plataforma (RKE como comisión; jackpot+bote como fondeo).
 */
final class PlatformReceivableService
{
    public function __construct(
        private readonly PlatformWalletService $platformWallet,
    ) {}

    /**
     * Idempotente por lote (lot_id único).
     *
     * @param array{rke?: float, jackpot?: float, prize_pool?: float} $parts
     */
    public function record(string $debtorUserId, string $currencyId, array $parts, ?string $roomId, ?string $lotId, ?string $notes = null): ?PlatformReceivable
    {
        $rke     = round(max(0, (float) ($parts['rke'] ?? 0)), 2);
        $jackpot = round(max(0, (float) ($parts['jackpot'] ?? 0)), 2);
        $pool    = round(max(0, (float) ($parts['prize_pool'] ?? 0)), 2);
        $amount  = round($rke + $jackpot + $pool, 2);

        if ($amount <= 0) {
            return null;
        }
        if ($lotId !== null && ($existing = PlatformReceivable::where('lot_id', $lotId)->first())) {
            return $existing;
        }

        return PlatformReceivable::create([
            'source'            => PlatformReceivable::SOURCE_BINGO_SALE,
            'debtor_user_id'    => $debtorUserId,
            'currency_id'       => $currencyId,
            'room_id'           => $roomId,
            'lot_id'            => $lotId,
            'amount'            => $amount,
            'rke_amount'        => $rke,
            'jackpot_amount'    => $jackpot,
            'prize_pool_amount' => $pool,
            'status'            => PlatformReceivable::STATUS_PENDING,
            'notes'             => $notes,
        ]);
    }

    /**
     * Lote reembolsado. Si la deuda seguía pendiente se anula y devuelve null
     * (el comercio todavía tiene todo el dinero: se lo devuelve al comprador).
     * Si ya estaba cobrada, devuelve la deuda para que el caller le reintegre
     * al comercio lo cobrado.
     */
    public function cancelForLot(string $lotId): ?PlatformReceivable
    {
        $receivable = PlatformReceivable::where('lot_id', $lotId)->lockForUpdate()->first();
        if (!$receivable || $receivable->status === PlatformReceivable::STATUS_CANCELLED) {
            return null;
        }
        if ($receivable->status === PlatformReceivable::STATUS_SETTLED) {
            return $receivable;
        }
        $receivable->update(['status' => PlatformReceivable::STATUS_CANCELLED, 'cancelled_at' => now()]);
        return null;
    }

    /**
     * Marca cobradas todas las deudas pendientes de un comercio en una moneda y
     * acredita a la wallet de plataforma: el RKE como comisión, el jackpot y el
     * bote como fondeo de premios.
     *
     * @return array{count: int, amount: float, rke: float, jackpot: float, prize_pool: float}
     */
    public function settle(string $debtorUserId, string $currencyId, string $settledBy): array
    {
        return DB::transaction(function () use ($debtorUserId, $currencyId, $settledBy) {
            $pending = PlatformReceivable::where('debtor_user_id', $debtorUserId)
                ->where('currency_id', $currencyId)
                ->where('status', PlatformReceivable::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            if ($pending->isEmpty()) {
                return ['count' => 0, 'amount' => 0.0, 'rke' => 0.0, 'jackpot' => 0.0, 'prize_pool' => 0.0];
            }

            $rke     = round((float) $pending->sum('rke_amount'), 2);
            $jackpot = round((float) $pending->sum('jackpot_amount'), 2);
            $pool    = round((float) $pending->sum('prize_pool_amount'), 2);

            PlatformReceivable::whereIn('id', $pending->pluck('id'))->update([
                'status'     => PlatformReceivable::STATUS_SETTLED,
                'settled_at' => now(),
                'settled_by' => $settledBy,
            ]);

            $debtorName = User::where('id', $debtorUserId)->value('username') ?? $debtorUserId;
            $count = $pending->count();
            $this->platformWallet->creditRke($currencyId, $rke, "Cobro RKE – {$debtorName} ({$count} venta(s))");
            $this->platformWallet->creditPoolFunding($currencyId, round($jackpot + $pool, 2), "Cobro bote/jackpot – {$debtorName} ({$count} venta(s))");

            return [
                'count'      => $count,
                'amount'     => round($rke + $jackpot + $pool, 2),
                'rke'        => $rke,
                'jackpot'    => $jackpot,
                'prize_pool' => $pool,
            ];
        });
    }

    /**
     * Deudas pendientes agrupadas por comercio y moneda, con su desglose
     * (panel de Conciliación / dashboard del comercio).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pendingSummary(?string $debtorUserId = null): array
    {
        return PlatformReceivable::query()
            ->where('platform_receivables.status', PlatformReceivable::STATUS_PENDING)
            ->when($debtorUserId, fn ($q) => $q->where('debtor_user_id', $debtorUserId))
            ->join('users', 'users.id', '=', 'platform_receivables.debtor_user_id')
            ->join('currencies', 'currencies.id', '=', 'platform_receivables.currency_id')
            ->groupBy('debtor_user_id', 'platform_receivables.currency_id', 'users.username', 'currencies.iso_code', 'currencies.symbol')
            ->selectRaw('debtor_user_id, platform_receivables.currency_id, users.username as debtor, currencies.iso_code as currency, currencies.symbol,
                COUNT(*) as sales, SUM(amount) as total, SUM(rke_amount) as rke, SUM(jackpot_amount) as jackpot,
                SUM(prize_pool_amount) as prize_pool, MIN(platform_receivables.created_at) as oldest')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'debtor_user_id' => (string) $r->debtor_user_id,
                'debtor'         => $r->debtor,
                'currency_id'    => (string) $r->currency_id,
                'currency'       => $r->currency,
                'symbol'         => $r->symbol,
                'sales'          => (int) $r->sales,
                'total'          => round((float) $r->total, 2),
                'rke'            => round((float) $r->rke, 2),
                'jackpot'        => round((float) $r->jackpot, 2),
                'prize_pool'     => round((float) $r->prize_pool, 2),
                'oldest'         => $r->oldest,
            ])
            ->all();
    }
}
